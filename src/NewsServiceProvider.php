<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Admin\Support\Sites;
use Atrium\News\Http\FeedController;
use Atrium\News\Models\News;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The model, its migrations, its demo and its settings are in the manifest
 * (composer.json, extra.atrium): they are on once `atrium:module:install` has
 * run. The RSS feed is the module's own route.
 */
final class NewsServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-news';

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-news');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-news'),
            ], 'atrium-news-lang');
        }

        if (! $this->app->make(Modules::class)->isInstalled(self::MODULE)) {
            return;
        }

        // Once every module has told where its pages are, and the panel has
        // booted: Route::localized() is its macro, the prefix is in its map.
        $this->app->booted(function (): void {
            $this->registerFeed();
        });
    }

    /**
     * /news.xml next to the list at /news (the prefix is the module's
     * address): a route of its own, so it never meets the theme's
     * /news/{path}. A news page under that prefix links to it from <head>.
     */
    private function registerFeed(): void
    {
        $prefix = PublicUrl::ruleFor(News::class)['prefix'] ?? '';

        if ($prefix === '') {
            return;
        }

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(function () use ($prefix): void {
                Route::localized(function () use ($prefix): void {
                    Route::get($prefix.'.xml', FeedController::class)->name('atrium.news.feed');
                });
            });
        }

        Hook::filter('atrium.head', function (string $html) use ($prefix): string {
            if (request()->route()?->parameter('prefix') !== $prefix) {
                return $html;
            }

            return $html.'<link rel="alternate" type="application/rss+xml" title="'.e(__('atrium-news::news.feed.title', ['site' => app(Sites::class)->current()->name])).'" href="'.e(app(ContentLanguages::class)->route('atrium.news.feed')).'">';
        });
    }
}
