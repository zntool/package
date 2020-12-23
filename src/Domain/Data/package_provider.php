<?php

return [
    [
        'name' => 'github',
        'host' => 'https://github.com',
        'url_templates' => [
            'new_tag' => '{package}/releases/new',
            'view_commit' => '{package}/commit/{hash}',
        ],
    ],
    [
        'name' => 'gitlab',
        'host' => 'https://gitlab.com',
    ],
];