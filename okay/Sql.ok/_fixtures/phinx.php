<?php

return
        [
            'default_migration_table' => 'phinx',
            'default_database' => 'seeded',
            'logging' => true,
            'version_order' => 'creation',
            'paths' => [
                'migrations' => __DIR__ . '/migrations',
                'seeds' => __DIR__ . '/seeds',
            ],
            'sqlite' => [
                'user' => '',
                'pass' => '',
                'dir' => '/tmp/pisara-fixtures',
                'which' => [
                    'snapshots' => [
                        'dir' => '/tmp/pisara-snapshots'
                    ]
                ]
            ],
            'environments' => [
                'empty' => [
                    'adapter' => 'sqlite',
                    'name' => "empty",
                    'migrate' => [
                        'target' => '0001',
                        'seeders' => false
                    ]
                ],
                'seeded' => [
                    'adapter' => 'sqlite',
                    'name' => "seeded",
                    'migrate' => [
                        'seeders' => true
                    ]
                ]
            ]
];
