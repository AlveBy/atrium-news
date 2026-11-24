<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Haberler',
    'settings' => [
        'rss_items' => 'RSS akışındaki haberler',
        'rss_items_hint' => 'Bu sitenin RSS akışının listelediği son haber sayısı.',
    ],
    'feed' => [
        'title' => ':site: haberler',
        'description' => ':site sitesinin son haberleri',
    ],
];
