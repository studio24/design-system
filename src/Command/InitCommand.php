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

class InitCommand extends Command
{
    protected static $defaultName = 'init';

    protected function configure()
    {
        $this
            ->setDescription('Initialise design system project')
            ->setHelp('This command setups up a design system project by copying the necessary config files into the project root.')
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Root path to build design system website from',
                getcwd()
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(Version::NAME . ': ' . $this->getDescription());

        $rootPath = $input->getOption('path');
        $rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        if (!$io->confirm(sprintf('Do you want to create design system config files in %s ?', $rootPath))) {
            $io->text('Quitting');
            return self::FAILURE;
        }

        // Create config files
        $destination = $rootPath . DIRECTORY_SEPARATOR . Config::DEFAULT_CONFIG_FILE;
        if (Config::saveDefaultConfigFile($destination)) {
            $io->text('* ' . $destination);
        } else {
            $io->text('Skipped config file since already exists at ' . $destination);
        }

        $destination = $rootPath . DIRECTORY_SEPARATOR . Config::DEFAULT_ASSETS_BUILD_SCRIPT;
        if (Config::saveBuildAssetsFile($destination)) {
            $io->text('* ' . $destination);
        } else {
            $io->text('Skipped build assets shell script since already exists at ' . $destination);
        }

        $io->success('All done!');

        // Finish up
        return self::SUCCESS;
    }
}