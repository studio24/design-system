<?php

/**
 * Apollo build config
 *
 * @see Studio24\Apollo\Build::$config
 */
$config = [
    'source_path'       => 'source',
    'destination_path'  => 'dist',
    'cache_path'        => 'var/cache',
    'build_command'     => 'npm run build',
    'navigation' => [
        'Home'          => 'index.md',
        'Get started'   => 'get-started.md',
        'Guidelines'    => 'guidelines/',
        'Components'    => 'components/',
        'Examples'      => 'examples/',
        'Support'       => 'support.md',
    ],
];