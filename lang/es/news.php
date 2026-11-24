<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Noticias',
    'settings' => [
        'rss_items' => 'Noticias en el feed RSS',
        'rss_items_hint' => 'Cuántas de las últimas noticias muestra el feed RSS de este sitio.',
    ],
    'feed' => [
        'title' => ':site: noticias',
        'description' => 'Las últimas noticias de :site',
    ],
];
