<?php

/**
 * Design System configuration
 *
 * Default values are provided when you run the init command.
 * Delete any you do not need to change since your config
 * settings override the default values.
 *
 * @see https://github.com/studio24/design-system/blob/main/docs/configuration.md
 */
$config = [
    'site_title'        => null,
    'navigation'        => [
        'Home'          => '/',
        'Templates'     => '/code/templates/',
    ],
    'assets_build_command' => './design-system-build.sh',
    'docs_path'         => 'docs/',
    'templates_path'    => 'templates/',
    'zip_name'          => null,
    'zip_folder'        => null,
];
