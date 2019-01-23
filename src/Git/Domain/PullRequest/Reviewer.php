<?php

declare(strict_types=1);

namespace App\Git\Domain\PullRequest;

use App\Git\Domain\Account;
use Carbon\CarbonImmutable;

/**
 * Class PullRequestReview.
 */
class Reviewer extends Account
{
    public const STATUS_UPDATED = 0;
    public const STATUS_COMMIT_PENDING = 2;
    public const STATUS_NEW_PR = 3;

    /**
     * @var CarbonImmutable|null
     */
    private $participatedAt;

    /**
     * @var bool
     */
    private $hasApproved;

    /**
     * @var int
     */
    private $status;

    /**
     * @var CommentActivity
     */
    private $comments;

    /**
     * Reviewer constructor.
     *
     * @param string               $username
     * @param CarbonImmutable|null $participatedAt
     * @param bool                 $hasApproved
     */
    public function __construct(string $username, ?CarbonImmutable $participatedAt, bool $hasApproved)
    {
        parent::__construct($username);
        $this->participatedAt = $participatedAt;
        $this->hasApproved = $hasApproved;
        $this->status = $participatedAt ? self::STATUS_UPDATED : self::STATUS_NEW_PR;
        $this->comments = [];
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return CarbonImmutable|null
     */
    public function participatedAt(): ?CarbonImmutable
    {
        return $this->participatedAt;
    }

    /**
     * @return bool
     */
    public function hasApproved(): bool
    {
        return $this->hasApproved;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return CommentActivity
     */
    public function comments(): CommentActivity
    {
        return $this->comments;
    }

    /**
     * @param CommentActivity $comment
     */
    public function addComment(CommentActivity $comment): void {
        $this->comments[] = $comment;
    }
}
