<?php
declare(strict_types=1);

namespace Studio24\DesignSystem\Command;

use Studio24\DesignSystem\Build;
use Studio24\DesignSystem\Config;
use Studio24\DesignSystem\Exception\BuildException;
use Studio24\DesignSystem\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';
    protected string $actions;

    protected function configure()
    {
        $this
            ->setDescription('Build design system website')
            ->setHelp('This command builds the design system website into static HTML files. You can optionally set the path (defaults to current path) and config file (defaults to ./design-system-config.php).')
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Root path to build design system website from',
                getcwd()
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Config to config file',
                Config::DEFAULT_CONFIG_FILE
            )
            ->addOption(
                'actions',
                'a',
                InputOption::VALUE_REQUIRED,
                'Which actions to run ("c" = clean, "a" = assets, "p" = pages, "t" = templates)',
                'capt'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start(self::$defaultName);
        $io = new SymfonyStyle($input, $output);
        $io->title(Version::NAME . ': ' . $this->getDescription());

        $this->actions = $input->getOption('actions');
        $rootPath = $input->getOption('path');
        $io->text(sprintf('Root path set to: %s', $rootPath));
        $config = new Config($rootPath, $input->getOption('config'));
        $io->text(sprintf('Config loaded from: %s', $input->getOption('config')));
        $build = new Build($config, $io);

        // Clean destination folder
        if ($this->doClean()) {
            $build->cleanDestination();
            $build->copyDesignSystemAssets();
            $io->info(sprintf('Cleaned destination path at %s', Config::DIST_PATH));
        }

        // Assets
        if ($this->doAssets()) {
            $io->text('Running assets build...');
            if ($output->isVerbose()) {
                $build->buildAssets(true);
            } else {
                $build->buildAssets();
            }
            $io->success('Assets successfully built!');
        }

        // Pages
        if ($this->doPages()) {
            $io->text('Building pages...');
            $build->buildPages();
            $io->info('Documentation pages built');
        }

        // Templates
        if ($this->doTemplates()) {

        }

        // Finish up
        $event = $stopwatch->stop(self::$defaultName);
        $io->text(sprintf('Execution time: %01.2f secs', $event->getDuration() / 1000));
        $io->text(sprintf('Memory usage: %01.2f MB', $event->getMemory()  / 1024 / 1024));

        return self::SUCCESS;
    }

    private function doClean(): bool
    {
        return strpos($this->actions, 'c') !== false;
    }

    private function doAssets(): bool
    {
        return strpos($this->actions, 'a') !== false;
    }

    private function doPages(): bool
    {
        return strpos($this->actions, 'p') !== false;
    }

    private function doTemplates(): bool
    {
        return strpos($this->actions, 't') !== false;
    }
}