<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\Diff;
use App\Git\Domain\PullRequest\PullRequest;

/**
 * Interface PullRequestReadModel.
 */
interface PullRequestReadModel
{
    /**
     * @param string $repository
     *
     * @return PullRequest[]
     */
    public function all(string $repository): array;

    /**
     * @param string $repository
     * @param string $id
     *
     * @return Diff
     */
    public function diff(string $repository, string $id): Diff;
}
