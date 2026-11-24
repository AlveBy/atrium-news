<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Новости',
    'settings' => [
        'rss_items' => 'Новостей в RSS-ленте',
        'rss_items_hint' => 'Сколько последних новостей показывает RSS-лента этого сайта.',
    ],
    'feed' => [
        'title' => ':site: новости',
        'description' => 'Последние новости сайта :site',
    ],
];
