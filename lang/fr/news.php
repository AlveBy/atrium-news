<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Actualités',
    'settings' => [
        'rss_items' => 'Actualités dans le flux RSS',
        'rss_items_hint' => 'Combien des dernières actualités le flux RSS de ce site liste.',
    ],
    'feed' => [
        'title' => ':site : actualités',
        'description' => 'Les dernières actualités de :site',
    ],
];
