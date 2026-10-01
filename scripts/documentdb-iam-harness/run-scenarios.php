<?php

/**
 * Drives the ext-mongodb client against mock-services.php and asserts what the
 * client did: which credentials it signed with, when it asked the ECS
 * endpoint, and how it failed. Each scenario runs in a fresh PHP process,
 * because libmongoc keeps its credential cache per process.
 *
 * Usage: php run-scenarios.php <state-dir> <mongod-port>   (parent)
 *        php run-scenarios.php <state-dir> <mongod-port> child <scenario>
 */

declare(strict_types=1);

$stateDir = $argv[1];
$port = (int) $argv[2];
const RELATIVE_URI = '/v2/credentials/harness-task-role';

function setControl(string $stateDir, array $control, bool $merge = false): void
{
    static $resets = 0;
    $current = $merge ? (json_decode((string) @file_get_contents($stateDir . '/ctl.json'), true) ?: []) : ['reset' => ++$resets];
    file_put_contents($stateDir . '/ctl.json', json_encode($control + $current));
}

function connect(string $port, string $stateDir, string $userinfo = ''): array
{
    static $n = 0;
    ++$n;
    // A distinct appname gives every call its own client, so every call opens
    // and authenticates a new connection instead of reusing a pooled one.
    $dsn = sprintf(
        'mongodb://%s127.0.0.1:%s/app?authSource=%%24external&authMechanism=MONGODB-AWS'
        . '&retryWrites=false&readPreference=secondaryPreferred&appname=harness%d'
        . '&serverSelectionTimeoutMS=4000&connectTimeoutMS=4000',
        $userinfo,
        $port,
        $n
    );
    try {
        $manager = new MongoDB\Driver\Manager($dsn);
        $manager->executeCommand('app', new MongoDB\Driver\Command(['ping' => 1]));

        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'class' => (new ReflectionClass($e))->getShortName(), 'msg' => $e->getMessage()];
    }
}

if (($argv[3] ?? '') === 'child') {
    $results = [];
    switch ($argv[4]) {
        case 'connect':
        case 'precedence-env':
        case 'ecs-down':
        case 'no-source':
        case 'full-uri':
            $results[] = connect((string) $port, $stateDir);
            break;
        case 'precedence-uri':
            $results[] = connect((string) $port, $stateDir, 'AKIAURIKEY:uri-secret@');
            break;
        case 'reuse':
            array_push($results, connect((string) $port, $stateDir), connect((string) $port, $stateDir), connect((string) $port, $stateDir));
            break;
        case 'refresh':
            $results[] = connect((string) $port, $stateDir);
            $results[] = connect((string) $port, $stateDir);
            sleep(3);
            $results[] = connect((string) $port, $stateDir);
            break;
        case 'wrong-role':
            $results[] = connect((string) $port, $stateDir);
            setControl($stateDir, ['accept_akid' => 'ASIAHARNESS2'], true);
            $results[] = connect((string) $port, $stateDir);
            break;
    }
    echo json_encode($results), "\n";
    exit(0);
}

function events(string $stateDir, string $kind): array
{
    $lines = @file($stateDir . '/events.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $all = array_map(static fn (string $l): array => json_decode($l, true), $lines);

    return array_values(array_filter($all, static fn (array $e): bool => $e['ev'] === $kind));
}

function runChild(string $stateDir, int $port, string $scenario, array $env): array
{
    @unlink($stateDir . '/events.log');
    $process = proc_open(
        [PHP_BINARY, __FILE__, $stateDir, (string) $port, 'child', $scenario],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        ['PATH' => (string) getenv('PATH')] + $env
    );
    $out = stream_get_contents($pipes[1]);
    proc_close($process);
    $decoded = json_decode(trim((string) $out), true);

    return is_array($decoded) ? $decoded : [['ok' => false, 'class' => 'ChildFailed', 'msg' => (string) $out]];
}

$failures = 0;
function check(string $label, bool $condition, string $detail = ''): void
{
    global $failures;
    echo ($condition ? 'PASS' : 'FAIL') . ' ' . $label . ($condition ? '' : ' :: ' . $detail) . "\n";
    $failures += $condition ? 0 : 1;
}

$ecsEnv = ['AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' => RELATIVE_URI];

// 1. TEST connect: only the ECS task credentials exist; no static key is set.
setControl($stateDir, ['expiry_seconds' => 3600, 'accept_akid' => 'ASIAHARNESS1']);
$r = runChild($stateDir, $port, 'connect', $ecsEnv);
$ecs = events($stateDir, 'ecs');
$auth = events($stateDir, 'auth');
check('connect: ping succeeds with no static AWS key in the environment', $r[0]['ok'] === true, json_encode($r));
check('connect: client asked the ECS endpoint once at the relative URI', count($ecs) === 1 && $ecs[0]['request'] === 'GET ' . RELATIVE_URI . ' HTTP/1.0', json_encode($ecs));
check('connect: client signed with the ECS key and sent the ECS session token', count($auth) >= 1 && $auth[0]['akid'] === 'ASIAHARNESS1' && $auth[0]['token'] === 'harness-token-1', json_encode($auth));

// 2. Wrong role: the server rejects the signed identity, then a retry fetches again.
setControl($stateDir, ['expiry_seconds' => 3600, 'accept_akid' => 'ASIAOTHERROLE']);
$r = runChild($stateDir, $port, 'wrong-role', $ecsEnv);
$ecs = events($stateDir, 'ecs');
$auth = events($stateDir, 'auth');
check('wrong role: the first connection fails closed', $r[0]['ok'] === false, json_encode($r));
check('wrong role: failure is an authentication error', $r[0]['ok'] === false && preg_match('/Authentication failed|authenticat/i', $r[0]['msg']) === 1, json_encode($r));
check('wrong role: the rejected identity is recorded', count($auth) >= 1 && $auth[0]['rejected'] === true, json_encode($auth));
check('wrong role: a failed auth clears the cache, so the retry fetched again (2 ECS calls)', count($ecs) === 2, json_encode($ecs));
check('wrong role: the retry signed with the new key and succeeded', count($auth) >= 2 && $auth[1]['akid'] === 'ASIAHARNESS2' && $r[1]['ok'] === true, json_encode([$auth, $r]));

// 3a. Cache reuse: a long-lived credential is fetched once for three clients.
setControl($stateDir, ['expiry_seconds' => 3600]);
$r = runChild($stateDir, $port, 'reuse', $ecsEnv);
$ecs = events($stateDir, 'ecs');
check('reuse: three new connections, one ECS call', count($ecs) === 1 && !in_array(false, array_column($r, 'ok'), true), json_encode([$ecs, $r]));

// 3b. Refresh: a credential 2 s inside the 5-minute window is refetched after it lapses.
setControl($stateDir, ['expiry_seconds' => 302]);
$r = runChild($stateDir, $port, 'refresh', $ecsEnv);
$ecs = events($stateDir, 'ecs');
$auth = events($stateDir, 'auth');
check('refresh: all three connections succeed', !in_array(false, array_column($r, 'ok'), true), json_encode($r));
check('refresh: the second connection reused the cache (still 1 ECS call before expiry)', count($auth) >= 3 && $auth[1]['akid'] === $auth[0]['akid'], json_encode($auth));
check('refresh: after expiry the client refetched (2 ECS calls) and signed with the new key', count($ecs) === 2 && $auth[2]['akid'] === 'ASIAHARNESS2', json_encode([$ecs, $auth]));

// 4. Precedence: environment keys beat the ECS endpoint.
setControl($stateDir, ['expiry_seconds' => 3600]);
$r = runChild($stateDir, $port, 'precedence-env', $ecsEnv + ['AWS_ACCESS_KEY_ID' => 'AKIAENVKEY', 'AWS_SECRET_ACCESS_KEY' => 'env-secret']);
check('precedence: env keys are used and the ECS endpoint is never called', events($stateDir, 'auth')[0]['akid'] === 'AKIAENVKEY' && events($stateDir, 'ecs') === [], json_encode([events($stateDir, 'auth'), events($stateDir, 'ecs')]));

// 5. Precedence: URI userinfo (a password in the DSN) is used as static AWS keys.
$r = runChild($stateDir, $port, 'precedence-uri', $ecsEnv);
check('precedence: DSN userinfo is taken as static AWS keys, ahead of ECS', events($stateDir, 'auth')[0]['akid'] === 'AKIAURIKEY' && events($stateDir, 'ecs') === [], json_encode([events($stateDir, 'auth'), events($stateDir, 'ecs')]));

// 6. ECS endpoint failing: fail closed, no signature is sent.
setControl($stateDir, ['ecs_mode' => 'error']);
$r = runChild($stateDir, $port, 'ecs-down', $ecsEnv);
check('ecs down: the endpoint was asked, the connection fails closed and nothing is signed', $r[0]['ok'] === false && count(events($stateDir, 'ecs')) === 1 && events($stateDir, 'auth') === [], json_encode([$r, events($stateDir, 'ecs'), events($stateDir, 'auth')]));
echo 'INFO ecs down message: ' . ($r[0]['msg'] ?? '') . "\n";

// 7. AWS_CONTAINER_CREDENTIALS_FULL_URI is not read by libmongoc 2.4.0.
setControl($stateDir, ['expiry_seconds' => 3600]);
$r = runChild($stateDir, $port, 'full-uri', [
    'AWS_CONTAINER_CREDENTIALS_FULL_URI' => 'http://169.254.170.2' . RELATIVE_URI,
    'AWS_CONTAINER_AUTHORIZATION_TOKEN' => 'harness-bearer',
]);
check('full uri: ignored; the ECS endpoint is not called and the connection fails', $r[0]['ok'] === false && events($stateDir, 'ecs') === [], json_encode([$r, events($stateDir, 'ecs')]));
echo 'INFO full uri message: ' . ($r[0]['msg'] ?? '') . "\n";

echo $failures === 0 ? "ALL PASS\n" : "FAILURES: $failures\n";
exit($failures === 0 ? 0 : 1);
