<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Support\RecordUrls;
use Atrium\News\Models\News;
use Atrium\News\NewsServiceProvider;
use Atrium\News\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Tighten\Ziggy\ZiggyServiceProvider;

/**
 * The feed does not need the public theme: the addresses come from the
 * module's own rule, and the news list is the site's address under it.
 */
final class RssFeedWithoutThemeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [InertiaServiceProvider::class, ZiggyServiceProvider::class, AtriumServiceProvider::class, NewsServiceProvider::class];
    }

    private function news(): void
    {
        $news = new News(['active' => true, 'active_from' => now()->subDay()]);
        $news->setTranslation('name', 'en', 'Opening day')->setTranslation('code', 'en', 'opening-day')->save();
    }

    public function test_the_addresses_come_from_the_news_rule(): void
    {
        $this->news();

        $content = (string) $this->get('/news.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<link>'.$this->root().'/news</link>', $content);
        $this->assertStringContainsString('<link>'.$this->root().'/news/opening-day</link>', $content);
    }

    public function test_a_news_that_has_no_page_is_left_out(): void
    {
        $this->news();
        app(RecordUrls::class)->extend(static fn (Model $record): string|false|null => $record instanceof News ? false : null);

        $content = (string) $this->get('/news.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('<item>', $content);
        $this->assertStringContainsString('<channel>', $content);
    }
}
