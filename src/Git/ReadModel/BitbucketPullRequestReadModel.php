<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use App\Git\Domain\Diff;
use App\Git\Domain\PullRequest\Author;
use App\Git\Domain\PullRequest\Reviewer;
use App\Git\Domain\PullRequest\PullRequest;
use Bitbucket\API\Http\Listener\BasicAuthListener;
use Bitbucket\API\Repositories\PullRequests;
use Carbon\CarbonImmutable;
use Exception;

/**
 * Class BitbucketPullRequestReadModel.
 */
class BitbucketPullRequestReadModel implements PullRequestReadModel
{
    /**
     * @var PullRequests
     */
    private $pullRequestRepo;

    /**
     * @var ActivityReadModel
     */
    private $activityReadModel;

    /**
     * @var BranchReadModel
     */
    private $branchReadModel;

    /**
     * BitbucketPullRequestReadModel constructor.
     *
     * @param string            $email
     * @param string            $password
     * @param ActivityReadModel $activityReadModel
     * @param BranchReadModel   $branchReadModel
     */
    public function __construct(string $email, string $password, ActivityReadModel $activityReadModel, BranchReadModel $branchReadModel)
    {
        $bitbucket = new \Bitbucket\API\Api();
        $bitbucket->getClient()->addListener(
            new BasicAuthListener($email, $password)
        );

        $this->pullRequestRepo = $bitbucket->api('Repositories\PullRequests');
        $this->activityReadModel = $activityReadModel;
        $this->branchReadModel = $branchReadModel;
    }

    /**
     * @param string $repository
     *
     * @return PullRequest[]
     *
     * @throws Exception
     */
    public function all(string $repository): array
    {
        $pullRequests = [];
        [$account, $repositoryName] = explode('/', $repository);
        $rawPRs = json_decode($this->pullRequestRepo->all($account, $repositoryName)->getContent());

        foreach ($rawPRs->values as $rawPR) {
            $rawInfo = json_decode($this->pullRequestRepo->get($account, $repositoryName, $rawPR->id)->getContent());
            $master = substr($rawInfo->destination->commit->hash, 0, 6);
            $commits = json_decode($this->pullRequestRepo->commits($account, $repositoryName, $rawPR->id)->getContent())->values;
            $updateToMaster = false;
            foreach ($commits as $commit) {
                if ($updateToMaster) {
                    break;
                }

                foreach ($commit->parents as $parent) {
                    $hash = substr($parent->hash, 0, 6);
                    if ($hash === $master) {
                        $updateToMaster = true;
                        break;
                    }
                }
            }

            $activities = $this->activityReadModel->all($repository, (string)$rawPR->id);
            $diff = $this->diff($repository, (string)$rawPR->id);

            /** @var Reviewer[] $reviewers */
            $reviewers = [];
            foreach ($rawInfo->participants as $participant) {
                $reviewer = new Reviewer(
                    $participant->user->username,
                    $participant->participated_on ? new CarbonImmutable($participant->participated_on) : null,
                    $participant->approved
                );
                $reviewers[] = $reviewer;
            }

            $pullRequests[] = new PullRequest(
                (string)$rawPR->id,
                $rawPR->title,
                new Author($rawPR->author->username, new CarbonImmutable($rawPR->created_on)),
                $repository,
                $rawPR->links->html->href,
                new CarbonImmutable($rawPR->created_on),
                $reviewers,
                $diff,
                $rawInfo->source->branch->name,
                $activities,
                $updateToMaster
            );
        }

        return $pullRequests;
    }

    /**
     * @param string $repository
     * @param string $id
     *
     * @return Diff
     */
    public function diff(string $repository, string $id): Diff
    {
        [$account, $repositoryName] = explode('/', $repository);
        $diff = $this->pullRequestRepo->diff($account, $repositoryName, $id)->getContent();

        // TODO check how to get conflict
        // $diff = json_decode($this->pullRequestRepo->diffStat($account, $repositoryName, $id)->getContent());

        return new Diff(
            substr_count($diff, 'diff --git'),
            substr_count($diff, '<<<<<<<')
        );
    }
}
