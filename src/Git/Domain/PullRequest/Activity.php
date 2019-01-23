<?php

declare(strict_types=1);

namespace App\Git\Domain\PullRequest;

use Carbon\CarbonImmutable;

/**
 * Class Activity.
 */
abstract class Activity
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var CarbonImmutable
     */
    private $createdAt;

    /**
     * Activity constructor.
     *
     * @param string          $username
     * @param CarbonImmutable $createdAt
     */
    public function __construct(string $username, CarbonImmutable $createdAt)
    {
        $this->username = $username;
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * @return CarbonImmutable
     */
    public function createdAt(): CarbonImmutable
    {
        return $this->createdAt;
    }
}
