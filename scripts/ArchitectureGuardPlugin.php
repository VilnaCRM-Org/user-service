<?php

declare(strict_types=1);

namespace App\Psalm;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
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
use Psalm\Type\Atomic\TMixed;
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
    private const FACTORY_DIRECTORY = DIRECTORY_SEPARATOR . 'Factory' . DIRECTORY_SEPARATOR;
    private const COLLECTION_DIRECTORY = DIRECTORY_SEPARATOR . 'Collection' . DIRECTORY_SEPARATOR;
    private const DOCTRINE_TYPE_DIRECTORY = DIRECTORY_SEPARATOR . 'DoctrineType' . DIRECTORY_SEPARATOR;
    private const CONSTRUCTOR_DEFAULT_MESSAGE =
        'Inject dependencies instead of instantiating them in constructor defaults.';
    private const BARE_ARRAY_MESSAGE =
        'Use a typed array (e.g. list<string>) or a typed collection class instead of untyped array.';

    /**
     * Maps domain object types to their required typed collection classes.
     * When a method parameter or return type uses array<MappedType>,
     * the guard reports it and suggests the corresponding collection class.
     */
    private const COLLECTION_REQUIRED_TYPES = [
        OAuthProvider::class => 'OAuthProviderCollection',
        OAuthProviderInterface::class => 'OAuthProviderCollection',
        User::class => 'UserCollection',
        UserInterface::class => 'UserCollection',
        RecoveryCode::class => 'RecoveryCodeCollection',
        AuthSession::class => 'AuthSessionCollection',
        PasswordResetTokenInterface::class => 'PasswordResetTokenCollection',
        DomainEvent::class => 'DomainEventCollection',
    ];

    /**
     * Maps classes to their preferred factory for production code.
     * Using 'new ClassName()' outside factory contexts will be reported.
     */
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

        if (!self::isFactorySource($filePath) && !self::isCollectionSource($filePath)) {
            self::reportDomainObjectArrayCollections($event, $statement);
        }

        if (!self::isDoctrineTypeSource($filePath) && !self::isCollectionSource($filePath)) {
            self::reportBareArraySignatures($event, $statement);
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

    private static function reportDomainObjectArrayCollections(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        self::reportDomainObjectArrayParameters($event, $statement);
        self::reportDomainObjectArrayReturnType($event, $statement);
    }

    private static function reportDomainObjectArrayParameters(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        foreach ($statement->getParams() as $index => $parameter) {
            $collectionName = self::parameterContainsDomainObjectArray($event, $index);
            if ($collectionName === null) {
                continue;
            }

            self::reportFunctionLikeIssue(
                $event,
                $parameter,
                self::parameterCollectionMessage($collectionName)
            );
        }
    }

    private static function reportDomainObjectArrayReturnType(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        $collectionName = self::containsDomainObjectArray(
            $event->getFunctionlikeStorage()->return_type
        );
        if ($collectionName === null) {
            return;
        }

        $returnType = $statement->getReturnType();
        if ($returnType === null) {
            return;
        }

        self::reportFunctionLikeIssue(
            $event,
            $returnType,
            self::returnCollectionMessage($collectionName)
        );
    }

    private static function containsDomainObjectArray(?Union $type): ?string
    {
        if ($type === null) {
            return null;
        }

        foreach ($type->getAtomicTypes() as $atomicType) {
            $collectionName = self::matchesDomainObjectCollectionAtomic($atomicType);
            if ($collectionName !== null) {
                return $collectionName;
            }
        }

        return null;
    }

    private static function matchesDomainObjectCollectionAtomic(Atomic $atomicType): ?string
    {
        if ($atomicType instanceof TArray || $atomicType instanceof TIterable) {
            return self::containsDomainObjectType($atomicType->type_params[1]);
        }

        if (
            $atomicType instanceof TKeyedArray
            && $atomicType->fallback_params !== null
        ) {
            return self::containsDomainObjectType($atomicType->fallback_params[1]);
        }

        return null;
    }

    private static function containsDomainObjectType(Union $type): ?string
    {
        foreach ($type->getAtomicTypes() as $atomicType) {
            if (!$atomicType instanceof TNamedObject) {
                continue;
            }

            $collectionName = self::COLLECTION_REQUIRED_TYPES[$atomicType->value] ?? null;
            if ($collectionName !== null) {
                return $collectionName;
            }
        }

        return null;
    }

    private static function parameterContainsDomainObjectArray(
        AfterFunctionLikeAnalysisEvent $event,
        int $index,
    ): ?string {
        $storageParameter = $event->getFunctionlikeStorage()->params[$index] ?? null;
        if ($storageParameter === null) {
            return null;
        }

        return self::containsDomainObjectArray($storageParameter->type);
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

    private static function parameterCollectionMessage(string $collectionName): string
    {
        return sprintf(
            'Use %s instead of bare array, list, or iterable of domain objects.',
            $collectionName
        );
    }

    private static function returnCollectionMessage(string $collectionName): string
    {
        return sprintf(
            'Return %s instead of bare array, list, or iterable of domain objects.',
            $collectionName
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

    private static function reportBareArraySignatures(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        self::reportBareArrayParameters($event, $statement);
        self::reportBareArrayReturnType($event, $statement);
    }

    private static function reportBareArrayParameters(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        foreach ($statement->getParams() as $index => $parameter) {
            $storageParameter = $event->getFunctionlikeStorage()->params[$index] ?? null;
            if ($storageParameter === null) {
                continue;
            }

            if (self::containsBareArray($storageParameter->type)) {
                self::reportFunctionLikeIssue(
                    $event,
                    $parameter,
                    self::BARE_ARRAY_MESSAGE
                );
            }
        }
    }

    private static function reportBareArrayReturnType(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        if (!self::containsBareArray($event->getFunctionlikeStorage()->return_type)) {
            return;
        }

        $returnType = $statement->getReturnType();
        if ($returnType === null) {
            return;
        }

        self::reportFunctionLikeIssue(
            $event,
            $returnType,
            self::BARE_ARRAY_MESSAGE
        );
    }

    private static function containsBareArray(?Union $type): bool
    {
        if ($type === null) {
            return false;
        }

        foreach ($type->getAtomicTypes() as $atomicType) {
            if (self::isBareArray($atomicType)) {
                return true;
            }
        }

        return false;
    }

    private static function isBareArray(Atomic $atomicType): bool
    {
        if (!$atomicType instanceof TArray) {
            return false;
        }

        foreach ($atomicType->type_params[1]->getAtomicTypes() as $valueType) {
            if ($valueType instanceof TMixed) {
                return true;
            }
        }

        return false;
    }

    private static function isFactorySource(string $filePath): bool
    {
        return str_contains($filePath, self::FACTORY_DIRECTORY);
    }

    private static function isCollectionSource(string $filePath): bool
    {
        return str_contains($filePath, self::COLLECTION_DIRECTORY);
    }

    private static function isDoctrineTypeSource(string $filePath): bool
    {
        return str_contains($filePath, self::DOCTRINE_TYPE_DIRECTORY);
    }

    private static function isProductionSource(string $filePath): bool
    {
        return str_contains($filePath, self::SOURCE_DIRECTORY);
    }
}
