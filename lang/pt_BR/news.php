<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Notícias',
    'settings' => [
        'rss_items' => 'Notícias no feed RSS',
        'rss_items_hint' => 'Quantas das últimas notícias o feed RSS deste site lista.',
    ],
    'feed' => [
        'title' => ':site: notícias',
        'description' => 'As últimas notícias de :site',
    ],
];
