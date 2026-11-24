<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'ニュース',
    'settings' => [
        'rss_items' => 'RSSフィードのニュース件数',
        'rss_items_hint' => 'このサイトのRSSフィードに載せる最新ニュースの件数です。',
    ],
    'feed' => [
        'title' => ':site：ニュース',
        'description' => ':site の最新ニュース',
    ],
];
