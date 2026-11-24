<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Notizie',
    'settings' => [
        'rss_items' => 'Notizie nel feed RSS',
        'rss_items_hint' => 'Quante delle ultime notizie elenca il feed RSS di questo sito.',
    ],
    'feed' => [
        'title' => ':site: notizie',
        'description' => 'Le ultime notizie di :site',
    ],
];
