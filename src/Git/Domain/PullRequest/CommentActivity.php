<?php

declare(strict_types=1);

namespace App\Git\Domain\PullRequest;

use Carbon\CarbonImmutable;

/**
 * Class CommentActivity.
 */
class CommentActivity extends Activity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $content;

    /**
     * @var CommentActivity|null
     */
    private $previous;

    /**
     * @var CommentActivity[]
     */
    private $responses;

    /**
     * CommentActivity constructor.
     *
     * @param string               $username
     * @param CarbonImmutable      $createdAt
     * @param string               $id
     * @param string               $content
     * @param CommentActivity|null $previous
     */
    public function __construct(string $username, CarbonImmutable $createdAt, string $id, string $content, ?CommentActivity $previous)
    {
        parent::__construct($username, $createdAt);
        $this->id = $id;
        $this->content = $content;
        $this->previous = $previous;

        if ($previous) {
            $previous->addResponse($previous);
        }
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * @return CommentActivity|null
     */
    public function previous(): ?CommentActivity
    {
        return $this->previous;
    }

    /**
     * @return CommentActivity[]
     */
    public function responses(): array
    {
        return $this->responses;
    }

    /**
     * @param CommentActivity $comment
     */
    private function addResponse(CommentActivity $comment): void
    {
        $this->responses[] = $comment;
    }
}
