<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Sites;
use Atrium\News\Feed\RssFeed;
use Atrium\News\Models\News;
use Atrium\News\Tests\TestCase;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;

/**
 * The RSS 2.0 feed of the news: what it lists, for which site and language,
 * and that what comes out is valid XML RSS readers accept.
 */
final class RssFeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['en' => 'English', 'de' => 'Deutsch'] as $code => $name) {
            Language::query()->firstOrCreate(['code' => $code], ['name' => $name, 'active' => true, 'sort' => 100 + Language::query()->count()]);
        }

        app(Sites::class)->default();
    }

    /**
     * @param  array<string,array{0:string,1:string,2?:string,3?:string}>  $texts  language => name, code, preview, detail
     * @param  array<string,mixed>  $attributes
     */
    private function news(array $texts, array $attributes = []): News
    {
        $news = new News($attributes + ['active' => true, 'active_from' => now()->subDay(), 'sort' => 500]);

        foreach ($texts as $language => $text) {
            $news->setTranslation('name', $language, $text[0])
                ->setTranslation('code', $language, $text[1])
                ->setTranslation('preview_text', $language, $text[2] ?? '')
                ->setTranslation('detail_text', $language, $text[3] ?? '');
        }

        $news->save();

        if (isset($attributes['created_at'])) {
            News::query()->whereKey($news->id)->toBase()->update(['created_at' => $attributes['created_at']]);
        }

        return $news;
    }

    private function feed(string $url = '/news.xml'): DOMDocument
    {
        $response = $this->get($url)->assertOk();
        $this->assertSame('application/rss+xml; charset=UTF-8', $response->headers->get('Content-Type'));

        $document = new DOMDocument;
        $this->assertTrue($document->loadXML((string) $response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING), 'the feed is well-formed XML');

        return $document;
    }

    /**
     * @return array<int,string>
     */
    private function titles(DOMDocument $document): array
    {
        return array_map(
            static fn ($node): string => (string) $node->textContent,
            iterator_to_array((new DOMXPath($document))->query('/rss/channel/item/title') ?: [], false),
        );
    }

    private function value(DOMDocument $document, string $path): string
    {
        return (string) (new DOMXPath($document))->evaluate('string('.$path.')');
    }

    public function test_it_is_valid_rss_two_with_what_a_reader_needs(): void
    {
        $this->news(['en' => ['Opening day', 'opening-day', '<p>Come and see.</p>']], ['active_from' => '2026-11-02 09:30:00']);

        $document = $this->feed();

        $this->assertSame('rss', $document->documentElement?->nodeName);
        $this->assertSame('2.0', $document->documentElement?->getAttribute('version'));

        // The channel: the three elements the specification requires.
        $this->assertSame('Demo shop: news', $this->value($document, '/rss/channel/title'));
        $this->assertSame($this->root().'/news', $this->value($document, '/rss/channel/link'));
        $this->assertSame('The latest news of Demo shop', $this->value($document, '/rss/channel/description'));
        $this->assertSame('en', $this->value($document, '/rss/channel/language'));
        $this->assertSame('Atrium', $this->value($document, '/rss/channel/generator'));

        // The feed names its own address.
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
        $self = $xpath->query('/rss/channel/atom:link')?->item(0);
        $this->assertInstanceOf(DOMElement::class, $self);
        $this->assertSame($this->root().'/news.xml', $self->getAttribute('href'));
        $this->assertSame('self', $self->getAttribute('rel'));
        $this->assertSame('application/rss+xml', $self->getAttribute('type'));

        // The item: an absolute link that is also its identity, a date in
        // the form RSS asks for, the preview as plain text.
        $this->assertSame('Opening day', $this->value($document, '/rss/channel/item/title'));
        $this->assertSame($this->root().'/news/opening-day', $this->value($document, '/rss/channel/item/link'));
        $this->assertSame($this->root().'/news/opening-day', $this->value($document, '/rss/channel/item/guid'));
        $this->assertSame('true', $this->value($document, '/rss/channel/item/guid/@isPermaLink'));
        $this->assertSame('Mon, 02 Nov 2026 09:30:00 +0000', $this->value($document, '/rss/channel/item/pubDate'));
        $this->assertSame('Come and see.', $this->value($document, '/rss/channel/item/description'));
    }

    public function test_the_feed_of_a_language_lists_the_news_written_in_it(): void
    {
        $this->news(['en' => ['Both', 'both'], 'de' => ['Beide', 'beide']]);
        $this->news(['en' => ['English only', 'english-only']]);
        $this->news(['de' => ['Nur deutsch', 'nur-deutsch']]);

        $english = $this->feed();
        $german = $this->feed('/de/news.xml');

        $this->assertEqualsCanonicalizing(['Both', 'English only'], $this->titles($english));
        $this->assertEqualsCanonicalizing(['Beide', 'Nur deutsch'], $this->titles($german));
        $this->assertSame('de', $this->value($german, '/rss/channel/language'));
        $this->assertSame('Demo shop: Neuigkeiten', $this->value($german, '/rss/channel/title'));
        $this->assertSame($this->root().'/de/news', $this->value($german, '/rss/channel/link'));
        $this->assertSame($this->root().'/de/news.xml', $this->value($german, '/rss/channel/atom:link/@href'));
        $this->assertContains($this->root().'/de/news/beide', array_map(
            static fn ($node): string => (string) $node->textContent,
            iterator_to_array((new DOMXPath($german))->query('/rss/channel/item/link') ?: [], false),
        ));
    }

    public function test_an_item_needs_its_code_in_the_language_as_well_as_its_name(): void
    {
        $news = $this->news(['en' => ['Named only', 'named-only']]);
        $news->setTranslation('name', 'de', 'Nur benannt')->save();

        $this->assertSame([], $this->titles($this->feed('/de/news.xml')));
    }

    public function test_news_that_a_visitor_may_not_see_are_left_out(): void
    {
        $this->news(['en' => ['Shown', 'shown']]);
        $this->news(['en' => ['Switched off', 'switched-off']], ['active' => false]);
        $this->news(['en' => ['Not yet', 'not-yet']], ['active_from' => now()->addDay()]);
        $this->news(['en' => ['Without a date', 'without-a-date']], ['active_from' => null]);

        $this->assertEqualsCanonicalizing(['Shown', 'Without a date'], $this->titles($this->feed()));
    }

    public function test_a_site_has_only_its_own_news(): void
    {
        $outlet = Site::query()->create(['code' => 'outlet', 'name' => 'Outlet', 'domains' => ['outlet.localhost'], 'languages' => ['en'], 'default_language' => 'en', 'active' => true, 'sort' => 50]);
        $this->news(['en' => ['Main news', 'main-news']]);
        $this->news(['en' => ['Outlet news', 'outlet-news']], ['site_id' => $outlet->id]);

        $this->assertSame(['Main news'], $this->titles($this->feed()));

        $document = $this->feed('http://outlet.localhost/news.xml');

        $this->assertSame(['Outlet news'], $this->titles($document));
        $this->assertSame('Outlet: news', $this->value($document, '/rss/channel/title'));
        $this->assertSame('http://outlet.localhost/news/outlet-news', $this->value($document, '/rss/channel/item/link'));
    }

    public function test_the_newest_come_first_and_the_setting_says_how_many(): void
    {
        $this->news(['en' => ['Oldest', 'oldest']], ['active_from' => now()->subDays(5)]);
        $this->news(['en' => ['Newest', 'newest']], ['active_from' => now()->subHour()]);
        $this->news(['en' => ['Middle', 'middle']], ['active_from' => now()->subDays(2)]);

        $this->assertSame(['Newest', 'Middle', 'Oldest'], $this->titles($this->feed()));

        Setting::save('news', ['rss_items' => 2]);

        $this->assertSame(['Newest', 'Middle'], $this->titles($this->feed()));
    }

    public function test_the_default_is_twenty_items(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->news(['en' => ["Item {$i}", "item-{$i}"]], ['active_from' => now()->subMinutes($i)]);
        }

        $titles = $this->titles($this->feed());

        $this->assertCount(20, $titles);
        $this->assertSame('Item 1', $titles[0]);
        $this->assertNotContains('Item 21', $titles);
    }

    public function test_a_news_without_a_date_is_dated_by_its_creation(): void
    {
        $this->news(['en' => ['Undated', 'undated']], ['active_from' => null, 'created_at' => '2026-10-01 08:00:00']);

        $this->assertSame('Thu, 01 Oct 2026 08:00:00 +0000', $this->value($this->feed(), '/rss/channel/item/pubDate'));
    }

    public function test_the_text_is_escaped_and_stripped_of_what_xml_forbids(): void
    {
        $this->news(['en' => [
            "Tom & Jerry <b>\x08\x0B\u{FFFE}live</b> ]]> \"quoted\" 😀",
            'tom-and-jerry',
            '<p>5 &lt; 6 &amp; 7 &gt; 3</p><p>It&#039;s &quot;ok&quot;</p><p>Next&nbsp;line</p><ul><li>One</li><li>Two</li></ul>',
        ]]);

        $content = (string) $this->get('/news.xml')->getContent();
        $document = $this->feed();

        // On the wire the markup is escaped, in the reader it reads as written.
        $this->assertStringContainsString('Tom &amp; Jerry &lt;b&gt;live&lt;/b&gt; ]]&gt; &quot;quoted&quot; 😀', $content);
        $this->assertStringNotContainsString("\x08", $content);
        $this->assertSame('Tom & Jerry <b>live</b> ]]> "quoted" 😀', $this->value($document, '/rss/channel/item/title'));
        $this->assertSame('5 < 6 & 7 > 3 It\'s "ok" Next line One Two', $this->value($document, '/rss/channel/item/description'));
    }

    public function test_without_a_preview_the_start_of_the_text_stands_in_for_it(): void
    {
        $this->news(['en' => ['Long read', 'long-read', '', '<p>'.str_repeat('word ', 200).'</p>']]);
        $this->news(['en' => ['Short read', 'short-read', '', '<p>Just this.</p>']], ['active_from' => now()->subDays(3)]);

        $document = $this->feed();
        $long = $this->value($document, '/rss/channel/item[1]/description');

        $this->assertStringStartsWith('word word word', $long);
        $this->assertStringEndsWith('...', $long);
        $this->assertLessThanOrEqual(503, mb_strlen($long));
        $this->assertSame('Just this.', $this->value($document, '/rss/channel/item[2]/description'));
    }

    public function test_a_news_without_any_text_is_listed_with_an_empty_description(): void
    {
        $news = new News(['active' => true, 'active_from' => now()->subDay()]);
        $news->setTranslation('name', 'en', 'Headline only')->setTranslation('code', 'en', 'headline-only')->save();

        $document = $this->feed();

        $this->assertSame(['Headline only'], $this->titles($document));
        $this->assertSame('', $this->value($document, '/rss/channel/item/description'));
    }

    public function test_the_preview_picture_is_the_enclosure(): void
    {
        Storage::disk('public')->put('news/cover.jpg', str_repeat('x', 1234));
        $this->news(['en' => ['With a picture', 'with-a-picture']], ['preview_picture' => '/storage/news/cover.jpg']);

        $document = $this->feed();

        $this->assertSame($this->root().'/storage/news/cover.jpg', $this->value($document, '/rss/channel/item/enclosure/@url'));
        $this->assertSame('1234', $this->value($document, '/rss/channel/item/enclosure/@length'));
        $this->assertSame('image/jpeg', $this->value($document, '/rss/channel/item/enclosure/@type'));
    }

    public function test_a_path_without_the_storage_prefix_is_read_from_the_disk_too(): void
    {
        Storage::disk('public')->put('news/cover.png', 'abc');
        $this->news(['en' => ['Short path', 'short-path']], ['preview_picture' => 'news/cover.png']);

        $document = $this->feed();

        $this->assertSame($this->root().'/storage/news/cover.png', $this->value($document, '/rss/channel/item/enclosure/@url'));
        $this->assertSame('3', $this->value($document, '/rss/channel/item/enclosure/@length'));
        $this->assertSame('image/png', $this->value($document, '/rss/channel/item/enclosure/@type'));
    }

    public function test_the_size_is_zero_when_the_file_is_not_on_this_disk(): void
    {
        $this->news(['en' => ['Lost file', 'lost-file']], ['preview_picture' => '/storage/news/missing.webp']);
        $this->news(['en' => ['Remote', 'remote']], ['preview_picture' => 'https://cdn.example.com/a/b.gif?v=2', 'active_from' => now()->subDays(2)]);
        $this->news(['en' => ['Upload', 'upload']], ['preview_picture' => '/upload/iblock/photo.jpg', 'active_from' => now()->subDays(3)]);

        $document = $this->feed();

        $this->assertSame('0', $this->value($document, '/rss/channel/item[1]/enclosure/@length'));
        $this->assertSame('image/webp', $this->value($document, '/rss/channel/item[1]/enclosure/@type'));
        $this->assertSame('0', $this->value($document, '/rss/channel/item[2]/enclosure/@length'));
        $this->assertSame('https://cdn.example.com/a/b.gif?v=2', $this->value($document, '/rss/channel/item[2]/enclosure/@url'));
        $this->assertSame('image/gif', $this->value($document, '/rss/channel/item[2]/enclosure/@type'));
        $this->assertSame('0', $this->value($document, '/rss/channel/item[3]/enclosure/@length'));
    }

    public function test_an_item_without_a_usable_picture_has_no_enclosure(): void
    {
        Storage::disk('public')->put('news/readme.pdf', 'pdf');
        $this->news(['en' => ['No picture', 'no-picture']]);
        $this->news(['en' => ['Not an image', 'not-an-image']], ['preview_picture' => '/storage/news/readme.pdf', 'active_from' => now()->subDays(2)]);
        $this->news(['en' => ['No extension', 'no-extension']], ['preview_picture' => '/storage/news/cover', 'active_from' => now()->subDays(3)]);
        $this->news(['en' => ['Unknown type', 'unknown-type']], ['preview_picture' => '/storage/news/cover.nonsense', 'active_from' => now()->subDays(4)]);

        $document = $this->feed();

        $this->assertCount(4, (new DOMXPath($document))->query('/rss/channel/item') ?: []);
        $this->assertCount(0, (new DOMXPath($document))->query('//enclosure') ?: []);
    }

    public function test_the_head_of_the_news_pages_points_to_the_feed(): void
    {
        $this->news(['en' => ['Opening day', 'opening-day'], 'de' => ['Eröffnung', 'eroeffnung']]);
        $link = '<link rel="alternate" type="application/rss+xml" title="Demo shop: news" href="'.$this->root().'/news.xml">';

        $this->get('/news')->assertOk()->assertSee($link, false);
        $this->get('/news/opening-day')->assertOk()->assertSee($link, false);
        $this->get('/de/news/eroeffnung')->assertOk()
            ->assertSee('<link rel="alternate" type="application/rss+xml" title="Demo shop: Neuigkeiten" href="'.$this->root().'/de/news.xml">', false);
    }

    public function test_the_link_comes_after_what_others_put_in_the_head(): void
    {
        Hook::filter('atrium.head', static fn (string $html): string => $html.'<!--earlier-->', 1);

        $this->get('/news')->assertOk()->assertSee('<!--earlier--><link rel="alternate" type="application/rss+xml"', false);
    }

    public function test_other_pages_do_not_point_to_it(): void
    {
        $this->get('/')->assertOk()->assertDontSee('application/rss+xml', false);
    }

    public function test_the_number_of_items_is_set_in_the_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/modules/news/settings')
            ->assertOk()
            ->assertSee('News in the RSS feed');

        foreach ([0, 101, 'many', ''] as $bad) {
            $this->actingAs($admin, 'admin')
                ->put('/admin/modules/news/settings', ['rss_items' => $bad])
                ->assertSessionHasErrors('rss_items');
        }

        $this->actingAs($admin, 'admin')->put('/admin/modules/news/settings', ['rss_items' => 5]);

        $this->assertSame(5, (int) Setting::get('news.rss_items'));
    }

    public function test_the_default_of_the_setting_is_twenty(): void
    {
        $this->assertSame(20, (int) Setting::get('news.rss_items'));
    }

    public function test_the_text_is_written_as_indented_xml_with_its_declaration(): void
    {
        $this->news(['en' => ['Opening day', 'opening-day']]);

        $content = (string) $this->get('/news.xml')->assertOk()->getContent();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString("\n <channel>\n  <title>", $content);
        $this->assertStringEndsWith("</rss>\n", $content);
    }

    public function test_a_description_is_cut_at_five_hundred_characters(): void
    {
        $this->news(['en' => ['Long read', 'long-read', str_repeat('a', 600)]]);

        $this->assertSame(str_repeat('a', 500).'...', $this->value($this->feed(), '/rss/channel/item/description'));
    }

    public function test_the_language_of_the_channel_is_written_the_way_rss_has_it(): void
    {
        $content = app(RssFeed::class)->render('pt_BR', 'https://example.com/pt_BR/news.xml', 'https://example.com/pt_BR/news');

        $this->assertStringContainsString('<language>pt-BR</language>', $content);
    }

    public function test_a_text_of_another_language_does_not_stand_in_for_the_missing_one(): void
    {
        $news = $this->news([
            'en' => ['Opening day', 'opening-day', 'Come and see.', 'The whole story.'],
            'de' => ['Eröffnung', 'eroeffnung'],
        ]);

        $document = $this->feed('/de/news.xml');

        $this->assertSame(['Eröffnung'], $this->titles($document));
        $this->assertSame('', $this->value($document, '/rss/channel/item/description'));
        $this->assertSame('Come and see.', $this->value($this->feed(), '/rss/channel/item/description'));
        $this->assertNotNull($news->id);
    }

    public function test_the_extension_of_the_picture_may_be_in_capitals(): void
    {
        $this->news(['en' => ['Shouting', 'shouting']], ['preview_picture' => '/storage/news/COVER.JPG']);

        $this->assertSame('image/jpeg', $this->value($this->feed(), '/rss/channel/item/enclosure/@type'));
    }
}
