<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use Behat\Behat\Context\Context;
use GraphQL\RequestBuilder\Argument;
use GraphQL\RequestBuilder\RootType;
use GraphQL\RequestBuilder\Type;

final class UserGraphQLQueryContext implements Context
{
    private const GRAPHQL_ID_PREFIX = '/api/users/';

    public function __construct(private UserGraphQLState $state)
    {
    }

    /**
     * @Given requesting to return user's id and email
     */
    public function expectingToGetIdAndEmail(): void
    {
        $this->state->addResponseField('id');
        $this->state->addResponseField('email');
    }

    /**
     * @Given requesting to return user's id
     */
    public function expectingToGetId(): void
    {
        $this->state->addResponseField('id');
    }

    /**
     * @Given getting user with id :id
     */
    public function gettingUser(string $id): void
    {
        $this->state->setQueryName('user');
        $id = self::GRAPHQL_ID_PREFIX . $id;

        $query = (string) (new RootType($this->state->getQueryName()))->addArgument(
            new Argument('id', $id)
        )->addSubTypes($this->state->getResponseContent());

        $this->state->setQuery('query' . $query);
    }

    /**
     * @Given getting collection of users
     */
    public function gettingCollectionOfUsers(): void
    {
        $this->state->setQueryName('users');

        $query = (string) (new RootType($this->state->getQueryName()))->addArgument(
            new Argument('first', 1)
        )->addSubType((new Type('edges'))->addSubType(
            (new Type('node'))->addSubTypes($this->state->getResponseContent())
        ));

        $this->state->setQuery('query' . $query);
    }
}
