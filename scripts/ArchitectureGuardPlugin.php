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

use function array_key_exists;

use const DIRECTORY_SEPARATOR;

use function is_array;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt;
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
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;

final class ArchitectureGuardPlugin implements
    AfterExpressionAnalysisInterface,
    AfterFunctionLikeAnalysisInterface
{
    private const SOURCE_DIRECTORY = DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    private const FACTORY_DIRECTORY = DIRECTORY_SEPARATOR . 'Factory' . DIRECTORY_SEPARATOR;
    private const COLLECTION_DIRECTORY = DIRECTORY_SEPARATOR . 'Collection' . DIRECTORY_SEPARATOR;
    private const REGISTRATION_DIRECTORY =
        DIRECTORY_SEPARATOR . 'Registration' . DIRECTORY_SEPARATOR;
    private const DOCTRINE_TYPE_DIRECTORY =
        DIRECTORY_SEPARATOR . 'DoctrineType' . DIRECTORY_SEPARATOR;
    private const CONSTRAINT_DIRECTORY =
        DIRECTORY_SEPARATOR . 'Constraint' . DIRECTORY_SEPARATOR;
    private const CONSTRUCTOR_DEFAULT_MESSAGE =
        'Inject dependencies instead of instantiating them in constructor defaults.';
    private const BARE_ARRAY_MESSAGE =
        'Use a typed array or collection class instead of untyped array.';
    private const FORBIDDEN_REGISTRATION_DIRECTORY_MESSAGE =
        'Use CQRS handlers instead of Application\\Registration orchestrators.';
    private const REPOSITORY_LOOKUP_IN_LOOP_MESSAGE =
        'Move repository lookup calls out of loops; load data in bulk before iterating.';
    private const BATCH_USER_PAYLOAD_ARRAY_MESSAGE =
        'Use batch registration input objects instead of payload arrays.';

    private const REPOSITORY_LOOKUP_PREFIXES = [
        'count',
        'exists',
        'fetch',
        'find',
        'get',
        'load',
    ];

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

        self::reportForbiddenRegistrationDirectory($event, $filePath, $statement);
        self::reportConstructorDefaultInstantiations($event, $statement);
        self::reportRepositoryLookupsInLoops($event, $statement);

        if (!self::isFactorySource($filePath) && !self::isCollectionSource($filePath)) {
            self::reportDomainObjectArrayCollections($event, $statement);
        }

        if (!self::isBatchPayloadBoundarySource($filePath)) {
            self::reportBatchUserPayloadArraySignatures($event, $statement);
        }

        if (self::shouldReportBareArraySignatures($filePath)) {
            self::reportBareArraySignatures($event, $statement);
        }

        return null;
    }

    private static function reportForbiddenRegistrationDirectory(
        AfterFunctionLikeAnalysisEvent $event,
        string $filePath,
        Node\FunctionLike $statement,
    ): void {
        if (!self::isRegistrationSource($filePath)) {
            return;
        }

        self::reportFunctionLikeIssue(
            $event,
            $statement,
            self::FORBIDDEN_REGISTRATION_DIRECTORY_MESSAGE
        );
    }

    private static function reportRepositoryLookupsInLoops(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        foreach ($statement->getStmts() ?? [] as $node) {
            self::reportRepositoryLookupsInNode($event, $node, false);
        }
    }

    private static function reportRepositoryLookupsInNode(
        AfterFunctionLikeAnalysisEvent $event,
        Node $node,
        bool $insideLoop,
    ): void {
        self::reportRepositoryLookupInLoop($event, $node, $insideLoop);

        if (self::reportLoopRepositoryLookups($event, $node)) {
            return;
        }

        self::reportRepositoryLookupsInChildNodes($event, $node, $insideLoop);
    }

    private static function reportRepositoryLookupInLoop(
        AfterFunctionLikeAnalysisEvent $event,
        Node $node,
        bool $insideLoop,
    ): void {
        if ($insideLoop && $node instanceof MethodCall && self::isRepositoryLookupCall($node)) {
            self::reportFunctionLikeIssue(
                $event,
                $node,
                self::REPOSITORY_LOOKUP_IN_LOOP_MESSAGE
            );
        }
    }

    private static function reportLoopRepositoryLookups(
        AfterFunctionLikeAnalysisEvent $event,
        Node $node,
    ): bool {
        $reported = false;

        if ($node instanceof Stmt\Foreach_) {
            $reported = self::reportForeachRepositoryLookups($event, $node);
        } elseif ($node instanceof Stmt\For_) {
            $reported = self::reportForRepositoryLookups($event, $node);
        } elseif ($node instanceof Stmt\While_) {
            $reported = self::reportWhileRepositoryLookups($event, $node);
        } elseif ($node instanceof Stmt\Do_) {
            $reported = self::reportDoRepositoryLookups($event, $node);
        }

        return $reported;
    }

    private static function reportForeachRepositoryLookups(
        AfterFunctionLikeAnalysisEvent $event,
        Stmt\Foreach_ $node,
    ): bool {
        self::reportRepositoryLookupsInNodes($event, $node->stmts, true);

        return true;
    }

    private static function reportForRepositoryLookups(
        AfterFunctionLikeAnalysisEvent $event,
        Stmt\For_ $node,
    ): bool {
        self::reportRepositoryLookupsInValue($event, $node->init, true);
        self::reportRepositoryLookupsInValue($event, $node->cond, true);
        self::reportRepositoryLookupsInValue($event, $node->loop, true);
        self::reportRepositoryLookupsInNodes($event, $node->stmts, true);

        return true;
    }

    private static function reportWhileRepositoryLookups(
        AfterFunctionLikeAnalysisEvent $event,
        Stmt\While_ $node,
    ): bool {
        self::reportRepositoryLookupsInValue($event, $node->cond, true);
        self::reportRepositoryLookupsInNodes($event, $node->stmts, true);

        return true;
    }

    private static function reportDoRepositoryLookups(
        AfterFunctionLikeAnalysisEvent $event,
        Stmt\Do_ $node,
    ): bool {
        self::reportRepositoryLookupsInNodes($event, $node->stmts, true);
        self::reportRepositoryLookupsInValue($event, $node->cond, true);

        return true;
    }

    private static function reportRepositoryLookupsInChildNodes(
        AfterFunctionLikeAnalysisEvent $event,
        Node $node,
        bool $insideLoop,
    ): void {
        foreach ($node->getSubNodeNames() as $subNodeName) {
            self::reportRepositoryLookupsInValue(
                $event,
                $node->$subNodeName,
                $insideLoop
            );
        }
    }

    /**
     * @param list<Node> $nodes
     */
    private static function reportRepositoryLookupsInNodes(
        AfterFunctionLikeAnalysisEvent $event,
        array $nodes,
        bool $insideLoop,
    ): void {
        foreach ($nodes as $node) {
            self::reportRepositoryLookupsInNode($event, $node, $insideLoop);
        }
    }

    private static function reportRepositoryLookupsInValue(
        AfterFunctionLikeAnalysisEvent $event,
        mixed $value,
        bool $insideLoop,
    ): void {
        if ($value instanceof Node) {
            self::reportRepositoryLookupsInNode($event, $value, $insideLoop);

            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            self::reportRepositoryLookupsInValue($event, $item, $insideLoop);
        }
    }

    private static function isRepositoryLookupCall(MethodCall $call): bool
    {
        if (!$call->name instanceof Node\Identifier) {
            return false;
        }

        $receiverName = self::repositoryReceiverName($call);
        if ($receiverName === null) {
            return false;
        }

        $methodName = strtolower($call->name->name);
        foreach (self::REPOSITORY_LOOKUP_PREFIXES as $prefix) {
            if (str_starts_with($methodName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function repositoryReceiverName(MethodCall $call): ?string
    {
        if (
            $call->var instanceof PropertyFetch
            && $call->var->name instanceof Node\Identifier
        ) {
            return str_contains(
                strtolower($call->var->name->name),
                'repository'
            )
                ? $call->var->name->name
                : null;
        }

        if ($call->var instanceof Expr\Variable && is_string($call->var->name)) {
            return str_contains(strtolower($call->var->name), 'repository')
                ? $call->var->name
                : null;
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

    private static function reportBatchUserPayloadArraySignatures(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        self::reportBatchUserPayloadArrayParameters($event, $statement);
        self::reportBatchUserPayloadArrayReturnType($event, $statement);
    }

    private static function reportBatchUserPayloadArrayParameters(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        foreach ($statement->getParams() as $index => $parameter) {
            $storageParameter = $event->getFunctionlikeStorage()->params[$index] ?? null;
            if ($storageParameter === null) {
                continue;
            }

            if (!self::containsBatchUserPayloadArray($storageParameter->type)) {
                continue;
            }

            self::reportFunctionLikeIssue(
                $event,
                $parameter,
                self::BATCH_USER_PAYLOAD_ARRAY_MESSAGE
            );
        }
    }

    private static function reportBatchUserPayloadArrayReturnType(
        AfterFunctionLikeAnalysisEvent $event,
        Node\FunctionLike $statement,
    ): void {
        if (!self::containsBatchUserPayloadArray($event->getFunctionlikeStorage()->return_type)) {
            return;
        }

        $returnType = $statement->getReturnType();
        if ($returnType === null) {
            return;
        }

        self::reportFunctionLikeIssue(
            $event,
            $returnType,
            self::BATCH_USER_PAYLOAD_ARRAY_MESSAGE
        );
    }

    private static function containsBatchUserPayloadArray(?Union $type): bool
    {
        if ($type === null) {
            return false;
        }

        foreach ($type->getAtomicTypes() as $atomicType) {
            if (self::atomicContainsBatchUserPayloadArray($atomicType)) {
                return true;
            }
        }

        return false;
    }

    private static function atomicContainsBatchUserPayloadArray(Atomic $atomicType): bool
    {
        if ($atomicType instanceof TKeyedArray) {
            if (self::isBatchUserPayloadShape($atomicType)) {
                return true;
            }

            return $atomicType->fallback_params !== null
                && self::containsBatchUserPayloadArray($atomicType->fallback_params[1]);
        }

        if ($atomicType instanceof TArray || $atomicType instanceof TIterable) {
            return self::containsBatchUserPayloadArray($atomicType->type_params[1]);
        }

        return false;
    }

    private static function isBatchUserPayloadShape(TKeyedArray $arrayType): bool
    {
        return array_key_exists('email', $arrayType->properties)
            && array_key_exists('initials', $arrayType->properties)
            && array_key_exists('password', $arrayType->properties);
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

        return self::hasDefaultKeyType($atomicType)
            && self::hasMixedValueType($atomicType);
    }

    private static function hasDefaultKeyType(TArray $arrayType): bool
    {
        foreach ($arrayType->type_params[0]->getAtomicTypes() as $keyType) {
            if ($keyType instanceof TArrayKey) {
                return true;
            }
        }

        return false;
    }

    private static function hasMixedValueType(TArray $arrayType): bool
    {
        foreach ($arrayType->type_params[1]->getAtomicTypes() as $valueType) {
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

    private static function isRegistrationSource(string $filePath): bool
    {
        return str_contains($filePath, self::REGISTRATION_DIRECTORY);
    }

    private static function isDoctrineTypeSource(string $filePath): bool
    {
        return str_contains($filePath, self::DOCTRINE_TYPE_DIRECTORY);
    }

    private static function isConstraintSource(string $filePath): bool
    {
        return str_contains($filePath, self::CONSTRAINT_DIRECTORY);
    }

    private static function shouldReportBareArraySignatures(string $filePath): bool
    {
        return !self::isDoctrineTypeSource($filePath)
            && !self::isCollectionSource($filePath)
            && !self::isConstraintSource($filePath);
    }

    private static function isBatchPayloadBoundarySource(string $filePath): bool
    {
        return self::isCollectionSource($filePath)
            || self::isDoctrineTypeSource($filePath)
            || self::isConstraintSource($filePath)
            || str_contains($filePath, DIRECTORY_SEPARATOR . 'DTO' . DIRECTORY_SEPARATOR)
            || str_contains($filePath, DIRECTORY_SEPARATOR . 'Infrastructure' . DIRECTORY_SEPARATOR)
            || str_contains($filePath, DIRECTORY_SEPARATOR . 'MutationInput' . DIRECTORY_SEPARATOR)
            || str_contains($filePath, DIRECTORY_SEPARATOR . 'OpenApi' . DIRECTORY_SEPARATOR)
            || str_contains($filePath, DIRECTORY_SEPARATOR . 'Validator' . DIRECTORY_SEPARATOR);
    }

    private static function isProductionSource(string $filePath): bool
    {
        return str_contains($filePath, self::SOURCE_DIRECTORY);
    }
}
