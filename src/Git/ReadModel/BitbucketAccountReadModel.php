<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\Account;
use Bitbucket\API\User;

/**
 * Class BitbucketAccountReadModel.
 */
class BitbucketAccountReadModel extends BitbucketClient implements AccountReadModel
{
    /** @var User */
    private $accountRepo;

    /**
     * BitbucketAccountReadModel constructor.
     *
     * @param string $email
     * @param string $password
     */
    public function __construct(string $email, string $password)
    {
        parent::__construct($email, $password);

        $this->accountRepo = $this->client()->api('User');
    }

    /**
     * @return Account
     */
    public function me(): Account {
        $response = $this->accountRepo->get();
        $rawAccount = json_decode($response->getContent());

        return new Account($rawAccount->user->username);
    }
}
