<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\PullRequest\Activity;

/**
 * interface ActivityReadModel.
 */
interface ActivityReadModel
{
    /**
     * @param string $repository
     * @param string $pullRequestId
     *
     * @return Activity[]
     */
    public function all(string $repository, string $pullRequestId): array;
}
