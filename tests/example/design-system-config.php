<?php

/**
 * Design System configuration
 *
 * Overrides default config settings
 * @see Studio24\DesignSystem\Config::$config
 */
$config = [
    'debug'             => true,
    'build_templates'   => [
        'examples',
        'single.html.twig',
    ],
    'navigation'        => [
        'Home' => '/',
        'Styles' => '/styles/',
        'Components' => '/components/',
        'Guidelines' => '/guidelines/',
        'Templates' => '/code/templates/',
    ],
];

