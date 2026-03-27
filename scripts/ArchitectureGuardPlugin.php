<?php

declare(strict_types=1);

namespace App\Psalm;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use App\User\Domain\Event\SessionRevokedEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;

use const DIRECTORY_SEPARATOR;

use function in_array;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\ClassMethod;
use Psalm\CodeLocation;
use Psalm\Issue\ForbiddenCode;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function sprintf;
use function str_contains;
use function strtolower;

final class ArchitectureGuardPlugin implements
    AfterExpressionAnalysisInterface,
    AfterFunctionLikeAnalysisInterface
{
    private const SOURCE_DIRECTORY = DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    private const OAUTH_DIRECTORY = self::SOURCE_DIRECTORY . 'OAuth' . DIRECTORY_SEPARATOR;
    private const FACTORY_DIRECTORY = DIRECTORY_SEPARATOR . 'Factory' . DIRECTORY_SEPARATOR;
    private const CONSTRUCTOR_DEFAULT_MESSAGE =
        'Inject dependencies instead of instantiating them in constructor defaults.';

    private const OAUTH_PROVIDER_TYPES = [
        OAuthProvider::class,
        OAuthProviderInterface::class,
    ];
    private const PREFERRED_FACTORY_MAP = [
        OAuthProvider::class => [
            'OAuthProvider',
            'OAuthProvider::fromString()',
        ],
        OAuthProviderCollection::class => [
            'OAuthProviderCollection',
            'OAuthProviderCollectionFactory',
        ],
        UserSignedInEvent::class => [
            'UserSignedInEvent',
            'SignInEventFactory',
        ],
        SignInFailedEvent::class => [
            'SignInFailedEvent',
            'SignInEventFactory',
        ],
        AccountLockedOutEvent::class => [
            'AccountLockedOutEvent',
            'SignInEventFactory',
        ],
        SessionRevokedEvent::class => [
            'SessionRevokedEvent',
            'SessionRevocationEventFactory',
        ],
        AllSessionsRevokedEvent::class => [
            'AllSessionsRevokedEvent',
            'SessionRevocationEventFactory',
        ],
        TwoFactorEnabledEvent::class => [
            'TwoFactorEnabledEvent',
            'TwoFactorEventFactory',
        ],
        TwoFactorDisabledEvent::class => [
            'TwoFactorDisabledEvent',
            'TwoFactorEventFactory',
        ],
        TwoFactorCompletedEvent::class => [
            'TwoFactorCompletedEvent',
            'TwoFactorEventFactory',
        ],
        TwoFactorFailedEvent::class => [
            'TwoFactorFailedEvent',
            'TwoFactorEventFactory',
        ],
        RecoveryCodeUsedEvent::class => [
            'RecoveryCodeUsedEvent',
            'TwoFactorEventFactory',
        ],
        RefreshTokenRotatedEvent::class => [
            'RefreshTokenRotatedEvent',
            'RefreshTokenEventFactory',
        ],
        RefreshTokenTheftDetectedEvent::class => [
            'RefreshTokenTheftDetectedEvent',
            'RefreshTokenEventFactory',
        ],
    ];

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expression = $event->getExpr();
        $message = self::preferredFactoryMessage($event, $expression);
        if ($message === null) {
            return null;
        }

        self::reportExpressionIssue(
            $event,
            $expression->class,
            $message
        );

        return null;
    }

    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $filePath = $event->getStatementsSource()->getFilePath();
        if (!self::isProductionSource($filePath)) {
            return null;
        }

        $statement = $event->getStmt();

        self::reportConstructorDefaultInstantiations($event, $statement);

        if (self::isOAuthSource($filePath) && !self::isFactorySource($filePath)) {
            self::reportOAuthProviderArrayCollections($event, $statement);
        }

        return null;
    }

    private static function reportConstructorDefaultInstantiations(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        if (!self::isConstructor($statement)) {
            return;
        }

        foreach ($statement->params as $parameter) {
            if (!$parameter->default instanceof Expr\New_) {
                continue;
            }

            self::reportFunctionLikeIssue(
                $event,
                $parameter->default,
                self::CONSTRUCTOR_DEFAULT_MESSAGE
            );
        }
    }

    private static function reportOAuthProviderArrayCollections(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        self::reportOAuthProviderArrayParameters($event, $statement);
        self::reportOAuthProviderArrayReturnType($event, $statement);
    }

    private static function reportOAuthProviderArrayParameters(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        foreach ($statement->getParams() as $index => $parameter) {
            if (!self::parameterContainsOAuthProviderArray($event, $index)) {
                continue;
            }

            self::reportFunctionLikeIssue(
                $event,
                $parameter,
                self::parameterCollectionMessage()
            );
        }
    }

    private static function reportOAuthProviderArrayReturnType(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        if (!self::containsOAuthProviderArray($event->getFunctionlikeStorage()->return_type)) {
            return;
        }

        $returnType = $statement->getReturnType();
        if ($returnType === null) {
            return;
        }

        self::reportFunctionLikeIssue(
            $event,
            $returnType,
            self::returnCollectionMessage()
        );
    }

    private static function containsOAuthProviderArray(?Union $type): bool
    {
        if ($type === null) {
            return false;
        }

        foreach ($type->getAtomicTypes() as $atomicType) {
            if (self::matchesOAuthProviderCollectionAtomic($atomicType)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesOAuthProviderCollectionAtomic(Atomic $atomicType): bool
    {
        if ($atomicType instanceof TArray || $atomicType instanceof TIterable) {
            return self::containsOAuthProviderType($atomicType->type_params[1]);
        }

        return $atomicType instanceof TKeyedArray
            && $atomicType->fallback_params !== null
            && self::containsOAuthProviderType($atomicType->fallback_params[1]);
    }

    private static function containsOAuthProviderType(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomicType) {
            if (!$atomicType instanceof TNamedObject) {
                continue;
            }

            if (in_array($atomicType->value, self::OAUTH_PROVIDER_TYPES, true)) {
                return true;
            }
        }

        return false;
    }

    private static function parameterContainsOAuthProviderArray(
        AfterFunctionLikeAnalysisEvent $event,
        int $index,
    ): bool {
        $storageParameter = $event->getFunctionlikeStorage()->params[$index] ?? null;

        return $storageParameter !== null
            && self::containsOAuthProviderArray($storageParameter->type);
    }

    private static function preferredFactoryMessage(
        AfterExpressionAnalysisEvent $event,
        Expr $expression,
    ): ?string {
        if (!self::isProductionSource($event->getStatementsSource()->getFilePath())) {
            return null;
        }

        if (!$expression instanceof Expr\New_ || !$expression->class instanceof Node\Name) {
            return null;
        }

        $resolvedName = (string) $expression->class->getAttribute('resolvedName');
        if ($resolvedName === '') {
            return null;
        }

        if ($event->getContext()->self === $resolvedName) {
            return null;
        }

        if (self::isFactoryContext($event->getContext()->self)) {
            return null;
        }

        return self::preferredFactoryMessageForResolvedName($resolvedName);
    }

    private static function isFactoryContext(?string $className): bool
    {
        return $className !== null && str_contains($className, '\\Factory\\');
    }

    private static function isConstructor(Node\FunctionLike $statement): bool
    {
        return $statement instanceof ClassMethod
            && strtolower($statement->name->name) === '__construct';
    }

    private static function parameterCollectionMessage(): string
    {
        return sprintf(
            'Use %s instead of %s.',
            'OAuthProviderCollection',
            'bare array, list, or iterable collections of OAuth providers'
        );
    }

    private static function returnCollectionMessage(): string
    {
        return sprintf(
            'Return %s instead of %s.',
            'OAuthProviderCollection',
            'bare OAuth provider arrays, lists, or iterables'
        );
    }

    private static function preferredFactoryMessageForResolvedName(
        string $resolvedName
    ): ?string {
        $preferredFactory = self::PREFERRED_FACTORY_MAP[$resolvedName] ?? null;
        if ($preferredFactory === null) {
            return null;
        }

        return self::instantiateViaFactoryMessage(
            $preferredFactory[0],
            $preferredFactory[1]
        );
    }

    private static function instantiateViaFactoryMessage(
        string $className,
        string $factoryName
    ): string {
        return sprintf(
            'Instantiate %s via %s in production code.',
            $className,
            $factoryName
        );
    }

    private static function reportExpressionIssue(
        AfterExpressionAnalysisEvent $event,
        Node $node,
        string $message,
    ): void {
        IssueBuffer::maybeAdd(
            new ForbiddenCode(
                $message,
                new CodeLocation($event->getStatementsSource(), $node)
            ),
            $event->getStatementsSource()->getSuppressedIssues()
        );
    }

    private static function reportFunctionLikeIssue(
        AfterFunctionLikeAnalysisEvent $event,
        Node $node,
        string $message,
    ): void {
        IssueBuffer::maybeAdd(
            new ForbiddenCode(
                $message,
                new CodeLocation($event->getStatementsSource(), $node)
            ),
            $event->getFunctionlikeStorage()->suppressed_issues
        );
    }

    private static function isOAuthSource(string $filePath): bool
    {
        return str_contains($filePath, self::OAUTH_DIRECTORY);
    }

    private static function isFactorySource(string $filePath): bool
    {
        return str_contains($filePath, self::FACTORY_DIRECTORY);
    }

    private static function isProductionSource(string $filePath): bool
    {
        return str_contains($filePath, self::SOURCE_DIRECTORY);
    }
}
