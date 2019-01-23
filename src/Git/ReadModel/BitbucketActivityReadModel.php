<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\Account;
use App\Git\Domain\PullRequest\Activity;
use App\Git\Domain\PullRequest\ApprovalActivity;
use App\Git\Domain\PullRequest\CommentActivity;
use App\Git\Domain\PullRequest\CommitActivity;
use Bitbucket\API\Http\Response\Pager;
use Bitbucket\API\Repositories\PullRequests;
use Bitbucket\API\User;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Class BitbucketActivityReadModel.
 */
class BitbucketActivityReadModel extends BitbucketClient implements ActivityReadModel
{
    /** @var PullRequests */
    private $pullRequestRepo;

    /**
     * BitbucketAccountReadModel constructor.
     *
     * @param string $email
     * @param string $password
     */
    public function __construct(string $email, string $password)
    {
        parent::__construct($email, $password);

        $this->pullRequestRepo = $this->client()->api('Repositories\PullRequests');
    }

    /**
     * @param string $repository
     * @param string $pullRequestId
     *
     * @return Activity[]
     */
    public function all(string $repository, string $pullRequestId): array
    {
        [$account, $repositoryName] = explode('/', $repository);

        $pager = new Pager(
            $this->pullRequestRepo->getClient(),
            $this->pullRequestRepo->activity($account, $repositoryName, $pullRequestId)
        );

        $activities = [];
        $responsesCluster = [[]];
        $comments = [];

        do {
            $currentPage = json_decode($pager->getCurrent()->getContent());
            $responsesCluster[] = $currentPage->values;
        } while ($pager->fetchNext());
        $responses = array_merge(...$responsesCluster);

        foreach (array_reverse($responses) as $response) {
            try {
                if (isset($response->update)) {
                    $data = $response->update;
                    $activities[] = new CommitActivity($data->author->username, new CarbonImmutable($data->date));
                }

                if (isset($response->approval)) {
                    $data = $response->approval;
                    $activities[] = new ApprovalActivity($data->user->username, new CarbonImmutable($data->date));
                }

                if (isset($response->comment)) {
                    $data = $response->comment;
                    $comment = new CommentActivity(
                        $data->user->username,
                        new CarbonImmutable($data->created_on),
                        (string)$data->id,
                        $data->content->raw,
                        isset($data->parent) ? $comments[$data->parent->id] : null
                    );
                    $activities[] = $comment;
                    $comments[$comment->id()] = $comment;

                    if ($data->content->raw === 'Fixed') {
                        $activities[] = new ApprovalActivity($data->user->username, new CarbonImmutable($data->created_on));
                    }
                }
            } catch (\Exception $e) {
                throw new RuntimeException('Unable to create activity', 0, $e);
            }
        }

        return $activities;
    }
}
