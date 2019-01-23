<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\Account;

/**
 * Interface UserReadModel.
 */
interface AccountReadModel
{
    /**
     * @return Account
     */
    public function me(): Account;
}
