<?php

declare(strict_types=1);

namespace App\Git\Domain;

/**
 * Class Diff.
 */
class Diff
{
    /**
     * @var int
     */
    private $files;

    /**
     * @var int
     */
    private $conflicts;

    /**
     * Diff constructor.
     *
     * @param int $files
     * @param int $conflicts
     */
    public function __construct(int $files, int $conflicts)
    {
        $this->files = $files;
        $this->conflicts = $conflicts;
    }

    /**
     * @return int
     */
    public function files(): int
    {
        return $this->files;
    }

    /**
     * @return int
     */
    public function conflicts(): int
    {
        return $this->conflicts;
    }
}
