<?php

use PHPUnit\Framework\TestCase;

use Studio24\Apollo\Command\BuildCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class BuildTest extends TestCase
{

    public function testBuild()
    {
        $application = new Application();
        $application->add(new BuildCommand());
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $status = $tester->run(['dist', 'pages'], ['verbosity' => true]);
        $this->assertSame(0, $status);
    }

}