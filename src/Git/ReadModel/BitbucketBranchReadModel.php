<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\Account;
use App\Git\Domain\Branch\Branch;
use App\Git\Domain\PullRequest\Activity;
use App\Git\Domain\PullRequest\ApprovalActivity;
use App\Git\Domain\PullRequest\CommentActivity;
use App\Git\Domain\PullRequest\CommitActivity;
use Bitbucket\API\Http\Response\Pager;
use Bitbucket\API\Repositories\PullRequests;
use Bitbucket\API\Repositories\Refs\Branches;
use Bitbucket\API\User;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Class BitbucketBranchReadModel.
 */
class BitbucketBranchReadModel extends BitbucketClient implements BranchReadModel
{
    /**
     * @var Branches
     */
    private $branchRepo;

    /**
     * BitbucketBranchReadModel constructor.
     *
     * @param string $email
     * @param string $password
     */
    public function __construct(string $email, string $password)
    {
        parent::__construct($email, $password);
        $this->branchRepo = $this->client()->api('Repositories\Refs\Branches');
    }


    /**
     * @param string $repository
     * @param string $branch
     *
     * @return Branch
     */
    public function get(string $repository, string $branch): Branch
    {
        dump(json_decode($this->branchRepo->get('nektria', 'yieldmanagerbo', 'YMB-377-allow-display-or-hide-all-in-map')->getContent()));
        die;
        return new Branch();
    }
}
