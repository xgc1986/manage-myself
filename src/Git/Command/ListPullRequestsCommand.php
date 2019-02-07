<?php

declare(strict_types=1);

namespace App\Git\Command;

use App\Git\Domain\PullRequest\Reviewer;
use App\Git\ReadModel\BitbucketAccountReadModel;
use App\Git\ReadModel\BitbucketActivityReadModel;
use App\Git\ReadModel\BitbucketBranchReadModel;
use App\Git\ReadModel\BitbucketPullRequestReadModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListPullRequestsCommand.
 */
class ListPullRequestsCommand extends Command
{

    /**
     *
     */
    protected function configure(): void
    {
        $this->setName('git:pr:list');
        $this->addOption('pending', 'p', InputOption::VALUE_NONE, 'only shows pull requests that are waiting to you for fix or review');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!getenv('BITBUCKET_EMAIL')) {
            throw new \RuntimeException('Environament variable BITBUCKET_EMAIL must be defined');
        }

        if (!getenv('BITBUCKET_PASSWORD')) {
            throw new \RuntimeException('Environament variable BITBUCKET_PASSWORD must be defined');
        }

        $this->initStyles($output);

        $email = getenv('BITBUCKET_EMAIL');
        $password = getenv('BITBUCKET_PASSWORD');

        $repositories = [
            'nektria/yieldmanagerbo',
            'nektria/yieldmanager',
            'nektria/routeplanner',
            'nektria/ReCS',
            'nektria/fms',
            'nektria/yield-Manager-gtm',
            'nektria/yield-manager-gtm-dia',
            'nektria/yield-manager-gtm-fontvella',
            'nektria/yield-manager-gtm-hortetdelbaix',
            'nektria/yield-manager-gtm-latavella',
            'nektria/yield-manager-gtm-leroymerlin',
            'nektria/yield-manager-gtm-sample',
            'nektria/yield-manager-gtm-smartfooding',
            'nektria/yield-manager-gtm-nektria',
            'nektria/yield-manager-sdk-js',
            'nektria/yieldmanager-sdk-android',
            'nektria/yieldmanager-sdk-java'
        ];

        $accountRM = new BitbucketAccountReadModel($email, $password);
        $activityRM = new BitbucketActivityReadModel($email, $password);
        $branchRM = new BitbucketBranchReadModel($email, $password);
        $pullRequestRM = new BitbucketPullRequestReadModel($email, $password, $activityRM, $branchRM);
        $account = $accountRM->me();

        shell_exec('reset');

        $this->renderLegend($output);

        foreach ($repositories as $repository) {
            try {
                $pullRequests = $pullRequestRM->all($repository);

                foreach ($pullRequests as $pullRequest) {
                    $reviewers = [];
                    $isWaitingYou = false;

                    foreach ($pullRequest->reviewers() as $reviewer) {
                        if ($reviewer->username() === $pullRequest->author()->username()) {
                            continue;
                        }

                        if ($reviewer->participatedAt()) {
                            $pendingComments = $pullRequest->getPendingCommentsFor($reviewer);
                            $status = 'white';
                            $approved = '';
                            if ($reviewer->hasApproved()) {
                                $approved = '3';
                            }

                            switch ($reviewer->status()) {
                                case Reviewer::STATUS_COMMIT_PENDING:
                                    $status = 'red';
                                    break;
                                case Reviewer::STATUS_NEW_PR:
                                    $status = 'danger';
                                    $approved = '';
                                    break;
                                case Reviewer::STATUS_UPDATED:
                                    $status = $pendingComments ? 'yellow' : 'white';
                                    break;
                            }

                            if ($pendingComments) {
                                $reviewers[] = "<${status}${approved}>".$reviewer->username()." (${pendingComments})</${status}${approved}>";
                            } else {
                                $reviewers[] = "<${status}${approved}>".$reviewer->username()."</${status}${approved}>";
                            }
                        } else {
                            $reviewers[] = '<danger>'.$reviewer->username().'</danger>';
                        }
                    }

                    if (!$isWaitingYou && $input->getOption('pending')) {
                        continue;
                    }

                    $headerTag = 'blue2';
                    if ($pullRequest->author()->username() !== $account->username()) {
                        $headerTag = 'green2';
                    }
                    $files = $pullRequest->diff()->files();
                    $diffInDays = $pullRequest->createdAt()->diffInDays();
                    $daysTag = 'default';
                    if ($diffInDays >= 1) {
                        $daysTag = 'danger';
                    }

                    $authorPendingComments = $pullRequest->getPendingCommentsFor($pullRequest->author());
                    $authorTag = 'white';

                    if (!$pullRequest->author()->updated()) {
                        if ($authorPendingComments) {
                            $authorTag = 'red';
                        } else {
                            $authorTag = 'yellow';
                        }
                    } elseif ($authorPendingComments) {
                        $authorTag = 'yellow';
                    }

                    $conflicts = $pullRequest->diff()->conflicts();
                    $conflictsMessage = $conflicts ? " ($conflicts)": '';

                    $table = new Table($output);
                    $table->setStyle('box-double');
                    $table
                        ->setHeaders([
                            "<${headerTag}>".$pullRequest->id()."</${headerTag}>",
                            ($conflicts || !$pullRequest->updateToMaster()) ? '<danger>'.$pullRequest->title()."$conflictsMessage</danger>" : "<${headerTag}>".$pullRequest->title()."</${headerTag}>"
                        ])
                        ->setRows([
                            [
                                'Author',
                                $authorPendingComments
                                    ? "<${authorTag}>".$pullRequest->author()->username()." ($authorPendingComments)</${authorTag}>"
                                    : "<${authorTag}>".$pullRequest->author()->username()."</${authorTag}>"
                            ],
                            ['Link', $pullRequest->link()],
                            ['Task', $pullRequest->taskLink()],
                            ['Branch', $pullRequest->branch()],
                            ['Last update', "<${daysTag}>".$pullRequest->createdAt()->diffForHumans()."</${daysTag}>"],
                            ['Reviewers', implode(', ', $reviewers)],
                            ['Tiramisu', $files >= 50 ? "<red>Yes (${files})</red>" : "<green>No (${files})</green>"]
                        ]);
                    $table->render();
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $output->writeln("<error>${message}</error>");
                dump($e);
            }
        }

        // $output->writeln(self::PETER);

        return 0;
    }

    /**
     * @param OutputInterface $output
     */
    private function initStyles(OutputInterface $output): void
    {
        $output->getFormatter()->setStyle('default', new OutputFormatterStyle());

        $output->getFormatter()->setStyle('white', new OutputFormatterStyle('white'));
        $output->getFormatter()->setStyle('red', new OutputFormatterStyle('red'));
        $output->getFormatter()->setStyle('green', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('blue', new OutputFormatterStyle('blue'));
        $output->getFormatter()->setStyle('yellow', new OutputFormatterStyle('yellow'));
        $output->getFormatter()->setStyle('cyan', new OutputFormatterStyle('cyan'));
        $output->getFormatter()->setStyle('magenta', new OutputFormatterStyle('magenta'));
        $output->getFormatter()->setStyle('black', new OutputFormatterStyle('black'));

        $output->getFormatter()->setStyle('white2', new OutputFormatterStyle('white', null, ['bold']));
        $output->getFormatter()->setStyle('red2', new OutputFormatterStyle('red', null, ['bold']));
        $output->getFormatter()->setStyle('green2', new OutputFormatterStyle('green', null, ['bold']));
        $output->getFormatter()->setStyle('blue2', new OutputFormatterStyle('blue', null, ['bold']));
        $output->getFormatter()->setStyle('yellow2', new OutputFormatterStyle('yellow', null, ['bold']));
        $output->getFormatter()->setStyle('cyan2', new OutputFormatterStyle('cyan', null, ['bold']));
        $output->getFormatter()->setStyle('magenta2', new OutputFormatterStyle('magenta', null, ['bold']));
        $output->getFormatter()->setStyle('black2', new OutputFormatterStyle('black', null, ['bold']));

        $output->getFormatter()->setStyle('white3', new OutputFormatterStyle('white', null, ['underscore']));
        $output->getFormatter()->setStyle('red3', new OutputFormatterStyle('red', null, ['underscore']));
        $output->getFormatter()->setStyle('green3', new OutputFormatterStyle('green', null, ['underscore']));
        $output->getFormatter()->setStyle('blue3', new OutputFormatterStyle('blue', null, ['underscore']));
        $output->getFormatter()->setStyle('yellow3', new OutputFormatterStyle('yellow', null, ['underscore']));
        $output->getFormatter()->setStyle('cyan3', new OutputFormatterStyle('cyan', null, ['underscore']));
        $output->getFormatter()->setStyle('magenta3', new OutputFormatterStyle('magenta', null, ['underscore']));
        $output->getFormatter()->setStyle('black3', new OutputFormatterStyle('black', null, ['underscore']));

        $output->getFormatter()->setStyle('danger', new OutputFormatterStyle('white', 'red'));
    }

    private const PETER = '<green2>
                                                                                                                       
                                                                                                                       
                                                    .*(########/*.                                                     
                                              ,#(.                 .*#(.                                               
                                          .#*                           .#/                                            
                                       .(,                                 .#.                                         
                                     .#                                       (.                                       
                                    /*,     ,##,                ./#/,        #,%%                                      
                                   ( . .#    (*   ,/#######(/,.          .*#(##                                        
                                 ,%,  (  /    #        /##(##/.        #*      .#.                                     
                                *, (*    #,/#*/.    .#.        #,    .%          *(                                    
                               #.         ,###( ,##(/           ,(###&,  /%.      (                                    
                              #                    #    .#,      #   (*(#(,..  ..*(*,                                
                             #.                    #  .*%%#/*,*/(%    (,(##*.    #,     .#/                            
                            *.                     ,%           /.   (.      (/#/          *#                          
                           *(                        #*       ,#              ,*             (*                        
                           #                           .*###/.                 (              .*                       
                          *.                                                  /.               //                      
                         .#                         *#(,,/          .*      ,#                  (                      
                         (,                       #       ,(,          .,..    .#.               /                     
                         #                       *(           ,(###((//((#####(*,                #.                    
                        .,                        #                   #                          */                    
                        /                         .*                 #.                          .#                    
                        #                          *&,. .((          #                            #                    
                       .#                            #,    #         (((#######/,                 (                    
                       */                              *#(**#,*(#(*               .#/             /                    
                       (,                                                         /.              /.                   
                       #.                                                        #                /.                   
                       #.                                                       #                 (                    
                       #                                                       #.                 #                    
                      #//                                                      (                  #                    
                     #. ,,                                                     ##*               .#                    
                     (,  ,#                                                        #,            ,(                    
                      #    #                             ,*                         #            (,                    
                      .*    *,                           (                          (            #                     
                       *(     #                         .*             /            #           *#,                    
                        .*     .#                        #.           # (,        .#           #,.#                    
                         .#      ,#                       **        **    .(####(.            #   #                    
                           #.      .#.                       *(##/,                         ./    #                    
                            ,*        /*                                                   #*    .#                    
                              #.        .#,                                              .#      (,                    
                               .#          /#.                                          (.       /                     
                                 *#           ,#,                                     ,*        #                      
                                   *#            ,#*                                .(         #.                      
                                     ,#.             *#*                          ,(          #                        
                                        /*               ,#/.                   **          .(                         
                                          .#*                .*#/.            .#..         (*                          
                                             ,#,                 *#%#        //    ,#.   .#                            
                                                .#/            //     (.    ,,       *( #.                             
                                                    ,#*       ,,       .#  */         /.                               
                                                        ,#/   #          #./                                           
                                                            .((          ,&                                            
                                                                                                                                                                                              
    </green2>';

    /**
     * @param OutputInterface $output
     */
    private function renderLegend(OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setStyle('box-double');
        $table->setHeaders(['<blue>Legend</blue>']);
        $table->addRows([
            ['Title', '<green>another\'s PR</green>, <danger>Needs to bring master</danger>'],
            ['Author', '<red>have comments</red>, <yellow>has commited, but still has comments</yellow>'],
            ['Last update', '<red>this PR is old and needs to be fixed asap</red>'],
            ['Tiramisu', '<white>There are a lot of files to review</white>'],
            ['Reviewers', '<white3>has approved the PR</white3>, <danger>has to participate</danger>, <red3>has to review again</red3>, <yellow>pending comments</yellow>'],
        ]);

        $table->render();
    }
}
