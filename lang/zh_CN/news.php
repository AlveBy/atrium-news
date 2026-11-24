<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => '新闻',
    'settings' => [
        'rss_items' => 'RSS 订阅中的新闻条数',
        'rss_items_hint' => '此站点的 RSS 订阅列出多少条最新新闻。',
    ],
    'feed' => [
        'title' => ':site：新闻',
        'description' => ':site 的最新新闻',
    ],
];
