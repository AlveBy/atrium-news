<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Neuigkeiten',
    'settings' => [
        'rss_items' => 'Neuigkeiten im RSS-Feed',
        'rss_items_hint' => 'Wie viele der neuesten Neuigkeiten der RSS-Feed dieser Website auflistet.',
    ],
    'feed' => [
        'title' => ':site: Neuigkeiten',
        'description' => 'Die neuesten Neuigkeiten von :site',
    ],
];
