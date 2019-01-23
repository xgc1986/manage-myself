<?php

declare(strict_types=1);

namespace App\Git\Domain;

/**
 * Class Account.
 */
class Account
{
    /**
     * @var string
     */
    private $username;

    /**
     * Account constructor.
     *
     * @param string $username
     */
    public function __construct(string $username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function username(): string
    {
        return $this->username;
    }
}
