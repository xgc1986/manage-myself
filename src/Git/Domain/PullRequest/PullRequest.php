<?php

declare(strict_types=1);

namespace App\Git\Domain\PullRequest;

use App\Git\Domain\Account;
use App\Git\Domain\Diff;
use Carbon\CarbonImmutable;

/**
 * Class PullRequest.
 */
class PullRequest
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var Author
     */
    private $author;

    /**
     * @var string
     */
    private $repository;

    /**
     * @var CarbonImmutable
     */
    private $createdAt;

    /**
     * @var string
     */
    private $link;

    /**
     * @var string
     */
    private $taskLink;

    /**
     * @var Reviewer[]
     */
    private $reviewers;

    /**
     * @var Diff
     */
    private $diff;

    /**
     * @var string
     */
    private $branch;

    /**
     * @var Activity|array
     */
    private $activities;

    /**
     * @var bool
     */
    private $updateToMaster;

    /**
     * PullRequest constructor.
     *
     * @param string          $id
     * @param string          $title
     * @param Author          $author
     * @param string          $repository
     * @param string          $link
     * @param CarbonImmutable $createdAt
     * @param Reviewer[]      $reviewers
     * @param Diff            $diff
     * @param string          $branch
     * @param Activity[]      $activities
     * @param bool            $updateToMaster
     */
    public function __construct(
        string $id,
        string $title,
        Author $author,
        string $repository,
        string $link,
        CarbonImmutable $createdAt,
        array $reviewers,
        Diff $diff,
        string $branch,
        array $activities,
        bool $updateToMaster
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->repository = $repository;
        $this->author = $author;
        $this->link = $link;
        $this->createdAt = $createdAt;
        $this->diff = $diff;
        $this->branch = $branch;
        $this->reviewers = [];
        $this->updateToMaster = $updateToMaster;

        $taskParts = explode('-', $branch);
        $task = false;
        if (count($taskParts) > 2) {
            $task = $taskParts[0].'-'.$taskParts[1];
        }
        if ($task && preg_match('/[A-Z]+-[\d]+/', $task)) {
            $this->taskLink = "https://nektria.atlassian.net/browse/$task";
        } else {
            $this->taskLink = '';
        }

        foreach ($reviewers as $reviewer) {
            $this->reviewers[$reviewer->username()] = $reviewer;
        }

        foreach ($activities as $activity) {
            $this->addActivity($activity);
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
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function repository(): string
    {
        return $this->repository;
    }

    /**
     * @return Author
     */
    public function author(): Author
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function link(): string
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function taskLink(): string
    {
        return $this->taskLink;
    }

    /**
     * @return CarbonImmutable
     */
    public function createdAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Reviewer[]
     */
    public function reviewers(): array
    {
        return array_values($this->reviewers);
    }

    /**
     * @return Diff
     */
    public function diff(): Diff
    {
        return $this->diff;
    }

    /**
     * @return string
     */
    public function branch(): string
    {
        return $this->branch;
    }

    /**
     * @return bool
     */
    public function updateToMaster(): bool
    {
        return $this->updateToMaster;
    }

    /**
     * @param Activity $activity
     */
    private function addActivity(Activity $activity): void
    {
        $this->activities[] = $activity;

        if ($activity instanceof CommentActivity) {
            if ($activity->username() !== $this->author()->username()) {
                $reviewer = $this->reviewers[$activity->username()];
                $reviewer->addComment($activity);
                $reviewer->setStatus(Reviewer::STATUS_UPDATED);
                $this->author()->setUpdated($activity->content() === 'Fixed' ? $this->author()->updated() : false);
            } else {
                $this->author()->addComment($activity);
            }
        } elseif ($activity instanceof ApprovalActivity) {
            $reviewer = $this->reviewers[$activity->username()];
            $reviewer->setStatus(Reviewer::STATUS_UPDATED);
        } elseif ($activity instanceof CommitActivity) {
            foreach ($this->reviewers() as $reviewer) {
                $reviewer->setStatus(Reviewer::STATUS_COMMIT_PENDING);
            }
            $this->author()->setUpdated(true);
        }
    }

    /**
     * @param Account $account
     *
     * @return int
     */
    public function getPendingCommentsFor(Account $account): int
    {
        $comments = [];

        foreach ($this->activities as $activity) {
            if ($activity instanceof CommentActivity) {

                if (!$account instanceof Author && !($account instanceof Reviewer && $activity->previous() && $account->username() !== $activity->username() && isset($comments[$activity->previous()->id()]))) {
                    continue;
                }
                $comments[$activity->id()] = $account->username() === $activity->username() || $activity->content() === 'Fixed';
                if ($comments[$activity->id()]) {
                    while ($activity->previous()) {
                        $activity = $activity->previous();
                        if (isset($comments[$activity->id()])) {
                            if ($comments[$activity->id()]) {
                                break;
                            }
                            $comments[$activity->id()] = true;
                        }
                    }
                }
            }
        }

        $count = 0;

        foreach ($comments as $status) {
            if (!$status) {
                $count++;
            }
        }

        return $count;
    }
}
