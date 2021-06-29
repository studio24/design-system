<?php
declare(strict_types=1);

namespace Studio24\Apollo\Command;

use Studio24\Apollo\Build;
use Studio24\Apollo\Config;
use Studio24\Apollo\Exception\BuildException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class CleanCommand extends Command
{
    protected static $defaultName = 'clean';

    protected function configure()
    {
        $this
            ->setDescription('Clean destination directory')
            ->setHelp('This command deletes previous build files from the destination directory')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Config to build design system files from'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Config to config file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Timer
        $stopwatch = new Stopwatch();
        $stopwatch->start(self::$defaultName);

        $io = new SymfonyStyle($input, $output);
        $io->title('Apollo: ' . $this->getDescription());

        // Get root path for template build operation, or use current working directory
        $rootPath = $input->getArgument('path');
        if (empty($rootPath)) {
            $rootPath = getcwd();
        }
        $io->text(sprintf('Root path set to: %s', $rootPath));

        // Load config
        $config = Config::getInstance($rootPath, $input->getOption('config'));

        // Pass output to child classes so they can output messages
        $build = new Build();
        $build->setOutput($output);
        $build->getMarkdown()->setOutput($output);

        // Run clean destination folder action
        $count = $build->cleanDestFiles();
        $io->success(sprintf('Deleted %s old files from destination path', $count));

        // Finish up
        $event = $stopwatch->stop(self::$defaultName);
        $io->text(sprintf('Execution time: %01.2f secs', $event->getDuration() / 1000));
        $io->text(sprintf('Memory usage: %01.2f MB', $event->getMemory()  / 1024 / 1024));

        return self::SUCCESS;
    }
}