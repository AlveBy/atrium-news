<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\News\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * When the feed is there and when it is not: the module's address rule, a
 * module that is not installed, no public theme.
 */
final class RssFeedWiringTest extends TestCase
{
    public function test_the_feed_is_a_route_of_the_module(): void
    {
        $this->assertTrue(Route::has('atrium.news.feed'));
        $this->assertSame($this->root().'/news.xml', route('atrium.news.feed'));
        $this->assertSame($this->root().'/de/news.xml', route('localized.atrium.news.feed', ['locale' => 'de']));
    }

    public function test_the_head_of_a_page_without_a_route_is_left_as_it_was(): void
    {
        $this->assertSame('<meta>', Hook::apply('atrium.head', '<meta>'));
    }
}
