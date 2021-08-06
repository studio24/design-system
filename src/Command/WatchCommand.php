<?php
declare(strict_types=1);

namespace Studio24\DesignSystem\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WatchCommand extends Command
{
    protected static $defaultName = 'watch';

    protected function configure()
    {
        $this
            ->setDescription('Build & watch project files')
            ->setHelp('This command builds the project files and watches for any changes, on chance it rebuilds files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Apollo: Build & Watch your project files');

        return 0;
    }
}