<?php

declare(strict_types=1);

namespace App\App\Command;

use Joli\JoliNotif\Notification;
use Joli\JoliNotif\Notifier\AppleScriptNotifier;
use Joli\JoliNotif\Notifier\TerminalNotifierNotifier;
use Joli\JoliNotif\NotifierFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TestCommand.
 */
class TestCommand extends Command
{

    /**
     *
     */
    protected function configure(): void
    {
        $this->setName('notification:out');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $notifier = NotifierFactory::create([
            new AppleScriptNotifier(),
            new TerminalNotifierNotifier(),
        ]);
        $notification =
            (new Notification())
                ->setTitle('Notification title')
                ->setBody('This is the body of your notification')
                ->setIcon(__DIR__ . '/../../../assets/1313235.png')
                ->addOption('subtitle', 'This is a subtitle')
                ->addOption('sound', 'Pop')
                ->addOption('sound', 'Blow')
                ->addOption('url', 'https://google.com')
        ;

        $notifier->send($notification);
    }
}
