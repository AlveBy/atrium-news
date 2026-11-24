<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Http;

use Atrium\Admin\Support\ContentLanguages;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Admin\Support\Sites;
use Atrium\News\Feed\RssFeed;
use Atrium\News\Models\News;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

/**
 * The RSS feed of the news of the site the visitor came to, in the language
 * of the address: /news.xml, /de/news.xml.
 */
final class FeedController extends Controller
{
    public function __invoke(RssFeed $feed, ContentLanguages $languages, Sites $sites): Response
    {
        $language = app()->getLocale();
        $prefix = PublicUrl::ruleFor(News::class)['prefix'] ?? '';
        $link = Route::has('atrium.theme.index')
            ? $languages->route('atrium.theme.index', ['prefix' => $prefix], $language)
            : $sites->current()->url($prefix);

        return response($feed->render($language, $languages->route('atrium.news.feed', [], $language), $link), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
