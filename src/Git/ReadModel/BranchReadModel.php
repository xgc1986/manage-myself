<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\Branch\Branch;

/**
 * Interface BranchReadModel.
 */
interface BranchReadModel
{
    /**
     * @param string $repository
     * @param string $branch
     *
     * @return Branch
     */
    public function get(string $repository, string $branch): Branch;
}
