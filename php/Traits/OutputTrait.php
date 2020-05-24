<?php
declare(strict_types=1);

namespace Studio24\Apollo\Traits;

use Studio24\Apollo\Exception\BuildException;
use Symfony\Component\Console\Output\OutputInterface;

trait OutputTrait
{
    /** @var OutputInterface */
    protected $output;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Output an info message if verbose mode is enabled
     * @param $message String message
     * @param mixed ...$params Optional params to pass to message via sprintf
     * @throws BuildException
     */
    public function verboseInfo($message, ...$params)
    {
        if (!$this->isVerboseOutput()) {
            return;
        }
        $this->output->writeln('<comment>' . sprintf($message, ...$params) . '</comment>');
    }

    /**
     * Do we have output set & is it verbose?
     * @return bool
     */
    public function isVerboseOutput(): bool
    {
        return ($this->output instanceof OutputInterface && $this->output->isVerbose());
    }

    public function hasOutput(): bool
    {
        return ($this->output instanceof OutputInterface);
    }

    public function getOutput(): OutputInterface
    {
        if (!$this->hasOutput()) {
            $class = self::class;
            throw new BuildException(sprintf('You must set Twig object via %s::setOutput()', $class));
        }
        return $this->output;
    }
}