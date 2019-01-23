<?php

declare(strict_types=1);

namespace App\Git\Domain;

use Carbon\CarbonImmutable;

/**
 * Class Comment.
 */
class Comment
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var CarbonImmutable
     */
    private $createdAt;

    /**
     * Comment constructor.
     *
     * @param string          $id
     * @param CarbonImmutable $createdAt
     */
    public function __construct(string $id, CarbonImmutable $createdAt)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return CarbonImmutable
     */
    public function createdAt(): CarbonImmutable
    {
        return $this->createdAt;
    }
}
