<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

use Studio24\Apollo\Command\BuildCommand;
use Studio24\DesignSystem\Config;

class ConfigTest extends TestCase
{

    public function testSaveDefaultConfigFile()
    {
        $testConfigPath = __DIR__ . '/temp/test.design-system-config2.php';
        if (file_exists($testConfigPath)) {
            unlink($testConfigPath);
        }
        Config::saveDefaultConfigFile(__DIR__ . '/temp/test.design-system-config2.php');

        $this->assertFileExists($testConfigPath);
        $result = exec('php -l ' . $testConfigPath, $output, $resultCode);

        $this->assertTrue($resultCode === 0, $result);
        unlink($testConfigPath);
    }

}