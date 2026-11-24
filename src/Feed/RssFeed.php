<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Feed;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Support\MediaUrl;
use Atrium\Admin\Support\RecordUrls;
use Atrium\Admin\Support\Sites;
use Atrium\News\Models\News;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use XMLWriter;

/**
 * The RSS 2.0 feed of the news of the current site in one language: the
 * latest active ones, newest first, each with its address, date, preview
 * text and picture. Only news written in that language are listed, and only
 * those that have a page there.
 */
final class RssFeed
{
    /** The pictures a feed reader shows, by extension. */
    private const IMAGE_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'svg' => 'image/svg+xml',
    ];

    public function __construct(
        private readonly RecordUrls $urls,
        private readonly Sites $sites,
    ) {}

    /**
     * @param  string  $self  the address of the feed itself
     * @param  string  $link  the address of the site's news page
     */
    public function render(string $language, string $self, string $link): string
    {
        $site = $this->sites->current()->name ?: config()->string('app.name');
        $items = $this->items($language);
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->startElement('channel');
        $this->text($xml, 'title', __('atrium-news::news.feed.title', ['site' => $site], $language));
        $this->text($xml, 'link', $link);
        $this->text($xml, 'description', __('atrium-news::news.feed.description', ['site' => $site], $language));
        $this->text($xml, 'language', str_replace('_', '-', $language));
        $this->text($xml, 'generator', 'Atrium');
        $xml->startElement('atom:link');
        $xml->writeAttribute('href', $self);
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        foreach ($items as [$news, $url]) {
            $this->item($xml, $news, $url, $language);
        }

        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * @return array<int,array{0:News,1:string}>
     */
    private function items(string $language): array
    {
        $items = [];

        foreach ($this->query($language)->limit((int) Setting::get('news.rss_items'))->get() as $news) {
            if (($url = $this->urls->url($news, $language)) !== null) {
                $items[] = [$news, $url];
            }
        }

        return $items;
    }

    /**
     * @return Builder<News>
     */
    private function query(string $language): Builder
    {
        return News::query()
            ->where('active', true)
            ->where(fn (Builder $query) => $query->whereNull('active_from')->orWhere('active_from', '<=', now()))
            ->where("name->{$language}", '!=', '')
            ->where("code->{$language}", '!=', '')
            ->orderByRaw('COALESCE(active_from, created_at) DESC')
            ->orderByDesc('id');
    }

    private function item(XMLWriter $xml, News $news, string $url, string $language): void
    {
        $xml->startElement('item');
        $this->text($xml, 'title', $news->getTranslation('name', $language));
        $this->text($xml, 'link', $url);
        $xml->startElement('guid');
        $xml->writeAttribute('isPermaLink', 'true');
        $xml->text($url);
        $xml->endElement();
        $this->text($xml, 'pubDate', ($news->active_from ?? $news->created_at)->toRfc2822String());
        $this->text($xml, 'description', $this->description($news, $language));
        $this->enclosure($xml, $news);
        $xml->endElement();
    }

    /**
     * The preview text as plain text, the start of the full text when there
     * is no preview.
     */
    private function description(News $news, string $language): string
    {
        $text = (string) ($news->getTranslation('preview_text', $language, false) ?: $news->getTranslation('detail_text', $language, false));
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $text)), 500);
    }

    /**
     * The preview picture, if it is one of the usual image types: RSS wants
     * the type and the size in bytes (0 when the file is not on this
     * server's disk).
     */
    private function enclosure(XMLWriter $xml, News $news): void
    {
        $picture = (string) $news->preview_picture;
        $url = MediaUrl::to($picture);
        $type = self::IMAGE_TYPES[strtolower(pathinfo((string) parse_url($picture, PHP_URL_PATH), PATHINFO_EXTENSION))] ?? null;

        if ($url === null || $type === null) {
            return;
        }

        $xml->startElement('enclosure');
        $xml->writeAttribute('url', $url);
        $xml->writeAttribute('length', (string) $this->size($picture));
        $xml->writeAttribute('type', $type);
        $xml->endElement();
    }

    /**
     * The size of a file kept on the public disk (`/storage/news/a.jpg` or
     * `news/a.jpg`); 0 for any other: a remote address, a file of an old site.
     */
    private function size(string $picture): int
    {
        $disk = Storage::disk('public');
        $path = Str::chopStart($picture, '/storage/');

        return $disk->exists($path) ? $disk->size($path) : 0;
    }

    /**
     * An element with a text: characters XML does not allow are dropped.
     */
    private function text(XMLWriter $xml, string $name, string $value): void
    {
        $xml->writeElement($name, (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value));
    }
}
