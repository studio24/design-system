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
    'examples',
    'single.html.twig',
  ),
  'navigation' => 
  array (
    'Home' => '/',
    'Styles' => '/styles/',
    'Components' => '/components/',
    'Guidelines' => '/guidelines/',
    'Templates' => '/templates/',
  ),
);
