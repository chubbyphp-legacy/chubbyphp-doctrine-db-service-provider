<?php

return [
    "target_php_version" => null,
    'directory_list' => [
        'src',
        'vendor/doctrine',
        'vendor/monolog/monolog',
        'vendor/pimple/pimple',
        'vendor/psr/log',
    ],
    "exclude_analysis_directory_list" => [
        'vendor/'
    ],
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
    ],
];
