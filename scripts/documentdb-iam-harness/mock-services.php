<?php

/**
 * Local stand-ins for the two endpoints a MONGODB-AWS client talks to.
 *
 * It listens on the ECS task-credentials endpoint (169.254.170.2:80) and on a
 * mongod-like TCP port. The mongod side answers the handshake and the
 * MONGODB-AWS SASL conversation, records the access key id the client signed
 * with and, depending on the control file, accepts or rejects it. It never
 * validates a signature, because only a real STS can do that.
 *
 * Control file (JSON, re-read on every request):
 *   reset           when its value changes, the key counter restarts at 1
 *   expiry_seconds  lifetime the ECS endpoint reports (default 3600)
 *   ecs_mode        "ok" | "error" (HTTP 500)
 *   accept_akid     when set, the mongod side rejects any other access key id
 *
 * Usage: php mock-services.php <state-dir> <mongod-port>
 */

declare(strict_types=1);

$stateDir = $argv[1];
$mongoPort = (int) $argv[2];
$ecsPath = '/v2/credentials/harness-task-role';
$counter = 0;
$lastReset = null;
$streams = [];
$kinds = [];

function control(string $stateDir): array
{
    $raw = @file_get_contents($stateDir . '/ctl.json');
    $decoded = $raw === false ? null : json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function record(string $stateDir, array $event): void
{
    file_put_contents(
        $stateDir . '/events.log',
        json_encode(['t' => microtime(true)] + $event) . "\n",
        FILE_APPEND
    );
}

function bson(array $document): string
{
    return (string) MongoDB\BSON\Document::fromPHP($document);
}

function opMsgReply(int $responseTo, array $document): string
{
    $body = pack('V', 0) . "\x00" . bson($document);

    return pack('VVVV', 16 + strlen($body), 1, $responseTo, 2013) . $body;
}

function opReply(int $responseTo, array $document): string
{
    $body = pack('V', 8) . pack('P', 0) . pack('V', 0) . pack('V', 1) . bson($document);

    return pack('VVVV', 16 + strlen($body), 1, $responseTo, 1) . $body;
}

function helloReply(): array
{
    return [
        'helloOk' => true,
        'ismaster' => true,
        'isWritablePrimary' => true,
        'maxBsonObjectSize' => 16777216,
        'maxMessageSizeBytes' => 48000000,
        'maxWriteBatchSize' => 100000,
        'logicalSessionTimeoutMinutes' => 30,
        'minWireVersion' => 0,
        'maxWireVersion' => 21,
        'readOnly' => false,
        'ok' => 1,
    ];
}

function accessKeyIdOf(string $authorization): string
{
    return preg_match('#Credential=([^/]+)/#', $authorization, $m) === 1 ? $m[1] : '';
}

/** @param array<string,mixed> $command */
function mongodAnswer(string $stateDir, array $command): array
{
    $name = (string) array_key_first($command);
    switch (strtolower($name)) {
        case 'saslstart':
            $payload = $command['payload']->getData();
            $first = MongoDB\BSON\Document::fromBSON($payload)->toPHP(['root' => 'array', 'document' => 'array']);
            $clientNonce = $first['r']->getData();
            $reply = bson([
                's' => new MongoDB\BSON\Binary($clientNonce . random_bytes(32), 0),
                'h' => 'sts.amazonaws.com',
            ]);

            return [
                'conversationId' => 1,
                'done' => false,
                'payload' => new MongoDB\BSON\Binary($reply, 0),
                'ok' => 1,
            ];
        case 'saslcontinue':
            $payload = $command['payload']->getData();
            $second = MongoDB\BSON\Document::fromBSON($payload)->toPHP(['root' => 'array', 'document' => 'array']);
            $akid = accessKeyIdOf($second['a']);
            $expected = control($stateDir)['accept_akid'] ?? null;
            $rejected = is_string($expected) && $expected !== $akid;
            record($stateDir, [
                'ev' => 'auth',
                'akid' => $akid,
                'token' => $second['t'] ?? null,
                'rejected' => $rejected,
            ]);
            if ($rejected) {
                return ['ok' => 0, 'code' => 18, 'codeName' => 'AuthenticationFailed', 'errmsg' => 'Authentication failed.'];
            }

            return ['conversationId' => 1, 'done' => true, 'payload' => new MongoDB\BSON\Binary('', 0), 'ok' => 1];
        case 'hello':
        case 'ismaster':
            return helloReply();
        default:
            return ['ok' => 1];
    }
}

/** @return list<string> replies to write for the complete messages in $buffer */
function drain(string $stateDir, string &$buffer): array
{
    $replies = [];
    while (strlen($buffer) >= 16) {
        $length = unpack('V', $buffer)[1];
        if (strlen($buffer) < $length) {
            break;
        }
        $message = substr($buffer, 0, $length);
        $buffer = substr($buffer, $length);
        $header = unpack('VmessageLength/VrequestId/VresponseTo/Vopcode', $message);
        if ($header['opcode'] === 2004) {
            $cursor = 20;
            $cursor = strpos($message, "\0", $cursor) + 1 + 8;
            $command = MongoDB\BSON\Document::fromBSON(substr($message, $cursor))
                ->toPHP(['root' => 'array', 'document' => 'array']);
            $replies[] = opReply($header['requestId'], mongodAnswer($stateDir, $command));
        } elseif ($header['opcode'] === 2013) {
            $command = MongoDB\BSON\Document::fromBSON(substr($message, 21))
                ->toPHP(['root' => 'array', 'document' => 'array']);
            $replies[] = opMsgReply($header['requestId'], mongodAnswer($stateDir, $command));
        }
    }

    return $replies;
}

$ecs = stream_socket_server('tcp://169.254.170.2:80', $errno, $errstr);
$mongod = stream_socket_server('tcp://127.0.0.1:' . $mongoPort, $errno, $errstr);
if ($ecs === false || $mongod === false) {
    fwrite(STDERR, "cannot listen: $errstr\n");
    exit(1);
}
file_put_contents($stateDir . '/ready', '1');

$buffers = [];
while (true) {
    $read = array_merge([$ecs, $mongod], $streams);
    $write = $except = [];
    if (stream_select($read, $write, $except, 1) < 1) {
        continue;
    }
    foreach ($read as $socket) {
        if ($socket === $ecs || $socket === $mongod) {
            $client = stream_socket_accept($socket, 0);
            if ($client !== false) {
                $id = (int) $client;
                $streams[$id] = $client;
                $kinds[$id] = $socket === $ecs ? 'ecs' : 'mongod';
                $buffers[$id] = '';
            }
            continue;
        }
        $id = (int) $socket;
        $chunk = fread($socket, 65536);
        if ($chunk === '' || $chunk === false) {
            fclose($socket);
            unset($streams[$id], $kinds[$id], $buffers[$id]);
            continue;
        }
        $buffers[$id] .= $chunk;
        if ($kinds[$id] === 'ecs') {
            if (!str_contains($buffers[$id], "\r\n\r\n")) {
                continue;
            }
            $requestLine = strtok($buffers[$id], "\r\n");
            $ctl = control($stateDir);
            if (($ctl['ecs_mode'] ?? 'ok') === 'error' || $requestLine !== 'GET ' . $ecsPath . ' HTTP/1.0') {
                $status = '500 Internal Server Error';
                $body = '{}';
                record($stateDir, ['ev' => 'ecs', 'request' => $requestLine, 'status' => 500]);
            } else {
                if (($ctl['reset'] ?? null) !== $lastReset) {
                    $lastReset = $ctl['reset'] ?? null;
                    $counter = 0;
                }
                ++$counter;
                $status = '200 OK';
                $body = json_encode([
                    'AccessKeyId' => 'ASIAHARNESS' . $counter,
                    'SecretAccessKey' => 'harness-secret-' . $counter,
                    'Token' => 'harness-token-' . $counter,
                    'Expiration' => gmdate('Y-m-d\TH:i:s\Z', time() + (int) ($ctl['expiry_seconds'] ?? 3600)),
                ]);
                record($stateDir, ['ev' => 'ecs', 'request' => $requestLine, 'status' => 200, 'akid' => 'ASIAHARNESS' . $counter]);
            }
            fwrite($socket, "HTTP/1.0 $status\r\nContent-Type: application/json\r\nContent-Length: "
                . strlen($body) . "\r\nConnection: close\r\n\r\n" . $body);
            fclose($socket);
            unset($streams[$id], $kinds[$id], $buffers[$id]);
            continue;
        }
        foreach (drain($stateDir, $buffers[$id]) as $reply) {
            fwrite($socket, $reply);
        }
    }
}
