<?php

/**
 * Apollo dist config
 *
 * @see Studio24\src\Build::$config
 */
$config = [
    'source_path'       => 'source',
    'destination_path'  => 'dist',
    'cache_path'        => 'var/cache',
    'build_command'     => 'npm run dist',
    'navigation' => [
        'Home'          => 'index.md',
        'Get started'   => 'get-started.md',
        'Guidelines'    => 'guidelines/',
        'Components'    => 'components/',
        'Examples'      => 'examples/',
        'Support'       => 'support.md',
    ],
];