<?php

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\News\Database\NewsDemoSeeder;
use Atrium\News\Models\News;
use Atrium\News\Tests\TestCase;

final class NewsTest extends TestCase
{
    public function test_news_are_written_in_several_languages_for_two_sites(): void
    {
        $this->assertTrue((new NewsDemoSeeder)->run());
        $news = News::query()->get();

        $this->assertSame(4, $news->count());
        $this->assertSame(2, $news->pluck('site_id')->unique()->count());
        $this->assertSame('Neue Filiale in der Innenstadt', $news->first()?->getTranslation('name', 'de'));
        $this->assertSame(['en'], array_keys($news->firstWhere(fn (News $item): bool => $item->getTranslation('code', 'en') === 'how-to-choose-a-laptop-for-study')?->getTranslations('name') ?? []));
        $this->assertSame(['de', 'en', 'ru'], Language::query()->orderBy('code')->pluck('code')->all());
        $this->assertSame(['de', 'en'], Site::query()->where('code', 'outlet')->value('languages'));

        $this->actingAs($this->admin(), 'admin');
        $this->get('/admin/news')->assertOk();
        $this->get('/admin/news/create')->assertOk();
        $this->get("/admin/news/{$news->first()?->id}/edit")->assertOk();

        $this->get('/de/news/neue-filiale-in-der-innenstadt')->assertOk()->assertSee('<html lang="de">', false);
    }

    public function test_the_demo_is_filled_once(): void
    {
        (new NewsDemoSeeder)->run();

        $this->assertFalse((new NewsDemoSeeder)->run());
        $this->assertSame(4, News::query()->count());
    }
}
