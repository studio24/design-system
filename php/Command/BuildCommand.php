<?php
declare(strict_types=1);

namespace Studio24\Apollo\Command;

use Studio24\Apollo\Build;
use Studio24\Apollo\Exception\BuildException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';
    protected $childCommands = ['delete', 'assets', 'pages', 'examples'];

    protected function configure()
    {
        $this
            ->setDescription('Build project files')
            ->setHelp('This command builds the templates and assets into static HTML files')
            ->addArgument(
                'commands',
                InputArgument::IS_ARRAY,
                'What build commands do you want to run (delete, assets, pages, examples)? If nothing is passed then all commands are run'
            )
        ;
    }

    /**
     * Whether to run a child command
     * @param string $name
     * @param array $commands
     * @return bool
     */
    protected function doChildFunction(string $name, array $commands): bool
    {
        if (empty($commands) || in_array($name, $commands)) {
            return true;
        }
        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = $input->getArgument('commands');
        $diff = array_diff($commands, $this->childCommands);
        if (count($diff) > 0) {
            throw new BuildException(sprintf('Command %s unrecognised', implode(' ', $commands)));
        }

        $stopwatch = new Stopwatch();
        $stopwatch->start('build');

        $io = new SymfonyStyle($input, $output);
        $io->title('Apollo: Build your project files');

        $build = new Build(__DIR__ . '/../../config.php');
        $build->setOutput($output);
        $build->getMarkdown()->setOutput($output);

        $io->text(sprintf('Build from: %s', $build->config('source_path')));
        $io->text(sprintf('Build to: %s', $build->config('destination_path')));
        if ($output->isVerbose()) {
            $io->text('Verbose mode enabled');
        }

        if ($this->doChildFunction('delete', $commands)) {
            $count = $build->deleteDestFiles();
            $io->success(sprintf('Deleted %s old files from destination path', $count));
        }

        if ($this->doChildFunction('assets', $commands)) {
            $io->text('Building assets...');
            if ($output->isVerbose()) {
                $build->buildAssets(true);
            } else {
                $build->buildAssets();
            }

            // Copy design system assets
            $build->createDestFolder('assets/design-system/styles/');
            copy($build->getPath('design-system/styles/design-system.css'), $build->getDestPath('assets/design-system/styles/design-system.css'));
            $io->success('Assets successfully built!');
        }

        if ($this->doChildFunction('pages', $commands)) {
            $io->text('Building pages...');
            $count = $build->buildPages();
            $io->success(sprintf('Built %s documentation page/s', $count));
        }

        if ($this->doChildFunction('examples', $commands)) {
            $io->text('Building example HTML pages...');
            $count = $build->buildExamples();
            $io->success(sprintf('Built %s example HTML file/s', $count));
        }

        $event = $stopwatch->stop('build');
        $io->text(sprintf('Execution time: %01.2f secs', $event->getDuration() / 1000));
        $io->text(sprintf('Memory usage: %01.2f MB', $event->getMemory()  / 1024 / 1024));

        return 0;
    }
}