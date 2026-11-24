<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The news module.
 */
return [
    'module' => 'News',
    'settings' => [
        'rss_items' => 'News in the RSS feed',
        'rss_items_hint' => 'How many of the latest news the RSS feed of this site lists.',
    ],
    'feed' => [
        'title' => ':site: news',
        'description' => 'The latest news of :site',
    ],
];
