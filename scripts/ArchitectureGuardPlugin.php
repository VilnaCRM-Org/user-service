<?php

declare(strict_types=1);

namespace App\Psalm;

use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;

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
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function str_contains;
use function strtolower;

final class ArchitectureGuardPlugin implements
    AfterExpressionAnalysisInterface,
    AfterFunctionLikeAnalysisInterface
{
    private const SOURCE_DIRECTORY = DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    private const OAUTH_DIRECTORY = self::SOURCE_DIRECTORY . 'OAuth' . DIRECTORY_SEPARATOR;
    private const NEW_OAUTH_PROVIDER_MESSAGE =
        'Instantiate OAuthProvider via OAuthProvider::fromString() in production code.';
    private const CONSTRUCTOR_DEFAULT_MESSAGE =
        'Inject dependencies instead of instantiating them in constructor defaults.';
    private const PARAMETER_COLLECTION_MESSAGE =
        'Use OAuthProviderCollection instead of bare array or list collections of OAuth providers.';
    private const RETURN_COLLECTION_MESSAGE =
        'Return OAuthProviderCollection instead of bare OAuth provider arrays or lists.';

    private const OAUTH_PROVIDER_TYPES = [
        OAuthProvider::class,
        OAuthProviderInterface::class,
    ];

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expression = $event->getExpr();
        if (!self::shouldReportNewOAuthProvider($event, $expression)) {
            return null;
        }

        self::reportExpressionIssue(
            $event,
            $expression->class,
            self::NEW_OAUTH_PROVIDER_MESSAGE
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

        if (self::isOAuthSource($filePath)) {
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
                self::PARAMETER_COLLECTION_MESSAGE
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
            self::RETURN_COLLECTION_MESSAGE
        );
    }

    private static function containsOAuthProviderArray(?Union $type): bool
    {
        if ($type === null) {
            return false;
        }

        foreach ($type->getAtomicTypes() as $atomicType) {
            if ($atomicType instanceof TArray
                && self::containsOAuthProviderType($atomicType->type_params[1])
            ) {
                return true;
            }

            if ($atomicType instanceof TKeyedArray
                && $atomicType->fallback_params !== null
                && self::containsOAuthProviderType($atomicType->fallback_params[1])
            ) {
                return true;
            }
        }

        return false;
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

    private static function shouldReportNewOAuthProvider(
        AfterExpressionAnalysisEvent $event,
        Expr $expression,
    ): bool {
        if (!self::isProductionSource($event->getStatementsSource()->getFilePath())) {
            return false;
        }

        if (!$expression instanceof Expr\New_ || !$expression->class instanceof Node\Name) {
            return false;
        }

        $resolvedName = (string) $expression->class->getAttribute('resolvedName');

        return $resolvedName === OAuthProvider::class
            && $event->getContext()->self !== OAuthProvider::class;
    }

    private static function isConstructor(Node\FunctionLike $statement): bool
    {
        return $statement instanceof ClassMethod
            && strtolower($statement->name->name) === '__construct';
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

    private static function isProductionSource(string $filePath): bool
    {
        return str_contains($filePath, self::SOURCE_DIRECTORY);
    }
}
