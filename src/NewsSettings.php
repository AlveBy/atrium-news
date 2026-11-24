<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News;

use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * What can be set for the news, per site: how many of the latest news the RSS
 * feed lists. Read with `Setting::get('news.rss_items')`.
 */
final class NewsSettings extends ModuleSettings
{
    public bool $perSite = true;

    public function fields(): array
    {
        return [
            NumberField::make('rss_items')
                ->label(__('atrium-news::news.settings.rss_items'))
                ->hint(__('atrium-news::news.settings.rss_items_hint'))
                ->default(20)
                ->rules(['required', 'integer', 'min:1', 'max:100']),
        ];
    }
}
