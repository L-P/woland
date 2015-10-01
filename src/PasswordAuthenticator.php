<?php

namespace Woland;

use \Slim\Middleware\HttpBasicAuthentication\AuthenticatorInterface;

/// Authenticate users using password_verify.
class PasswordAuthenticator implements AuthenticatorInterface
{
    /// @var string[] "user" => "hash"
    private $users;

    /// @param string[] $users @see $this->users
    public function __construct(array $users)
    {
        $this->users = $users;
        assert('count($users) > 0');
    }

    public function __invoke(array $args)
    {
        if (!array_key_exists($args['user'], $this->users)) {
            return false;
        }

        return password_verify($args['password'], $this->users[$args['user']]);
    }
}
