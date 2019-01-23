<?php

declare(strict_types=1);

namespace App\Git\Domain\PullRequest;

use App\Git\Domain\Account;
use Carbon\CarbonImmutable;

/**
 * Class PullRequestAuthor.
 */
class Author extends Account
{
    /**
     * @var CarbonImmutable|null
     */
    private $participatedAt;

    /**
     * @var bool
     */
    private $updated;

    /**
     * @var CommentActivity
     */
    private $comments;

    /**
     * @param string               $username
     * @param CarbonImmutable|null $participatedAt
     */
    public function __construct(string $username, ?CarbonImmutable $participatedAt)
    {
        parent::__construct($username);
        $this->participatedAt = $participatedAt;
        $this->updated = true;
        $this->comments = [];
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
    public function updated(): bool
    {
        return $this->updated;
    }

    /**
     * @param bool $updated
     */
    public function setUpdated(bool $updated): void
    {
        $this->updated = $updated;
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
