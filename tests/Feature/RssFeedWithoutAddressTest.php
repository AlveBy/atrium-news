<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\News\Models\News;
use Atrium\News\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * A site that gave the news no address has no feed either.
 */
final class RssFeedWithoutAddressTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.public_urls', [News::class => ['prefix' => '']]);
    }

    public function test_there_is_no_feed_and_no_link_to_it(): void
    {
        $this->assertFalse(Route::has('atrium.news.feed'));
        $this->get('/.xml')->assertNotFound();
        $this->get('/news.xml')->assertNotFound();
        $this->assertSame('<meta>', Hook::apply('atrium.head', '<meta>'));
    }
}
