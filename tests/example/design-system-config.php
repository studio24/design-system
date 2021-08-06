<?php

/**
 * Design System configuration
 *
 * Overrides default config settings
 * @see Studio24\DesignSystem\Config::$config
 */
$config = array (
  'debug' => true,
  'twig_render' => 
  array (
    'Components' => 'templates/components',
    'Templates' => 'templates/examples',
  ),
  'navigation' => 
  array (
    'Home' => '/',
    'Styles' => 'styles/',
    'Components' => '@twig_render:Components',
    'Templates' => '@twig_render:Templates',
  ),
);
