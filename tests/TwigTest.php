<?php
declare(strict_types=1);

namespace Apollo\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;

class TwigTest extends TestCase
{

    public function getTwig(): Environment
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__);
        $twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../var/cache',
        ]);
        return $twig;
    }

    public function testTwigMarkdown()
    {
        $twig = $this->getTwig();
        $twig->addFilter();

        $html = $twig->render('test.html.twig', ['name' => "John Smith"]);
        

        $this->assertStringContainsString('<h2>My test HTML</h2>', $html);

    }

}