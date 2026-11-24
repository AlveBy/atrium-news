<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Atrium\News\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * With `route:cache` the routes come from the cache file, which already has
 * the feed: the module adds none of its own.
 */
final class RssFeedCachedRoutesTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'routes');
        $_SERVER['APP_ROUTES_CACHE'] = $this->file;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['APP_ROUTES_CACHE']);
        unlink($this->file);
    }

    public function test_the_module_adds_no_route_to_cached_ones(): void
    {
        $this->assertTrue($this->app->routesAreCached());
        $this->assertFalse(Route::has('atrium.news.feed'));
    }
}
