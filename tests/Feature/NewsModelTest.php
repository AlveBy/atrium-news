<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Sites;
use Atrium\News\Database\NewsDemoSeeder;
use Atrium\News\Models\News;
use Atrium\News\NewsServiceProvider;
use Atrium\News\Tests\TestCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What the model asks of the form, what the demo puts into the shop and what
 * an application may publish.
 */
final class NewsModelTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_the_news_are_offered_as_a_calendar_by_the_date_they_go_live(): void
    {
        $views = News::getAdministrationViews();

        $this->assertCount(1, $views);
        $this->assertSame('calendar', $views[0]->type());
        $this->assertSame('active_from', $views[0]->getField());
        $this->assertSame('name', $views[0]->getTitleField());
        $this->assertNull($views[0]->getUntil());
    }

    public function test_a_news_item_is_active_and_sorted_by_default_and_read_back_as_such(): void
    {
        $news = News::query()->create(['name' => 'Hello', 'code' => 'hello']);

        $this->assertTrue($news->fresh()?->active);
        $this->assertSame(500, $news->fresh()?->sort);
        $this->assertFalse(News::query()->create(['name' => 'Off', 'code' => 'off', 'active' => 0])->fresh()?->active);
    }

    public function test_the_form_starts_with_an_active_item_of_sort_500(): void
    {
        $fields = [];

        foreach (News::getAdministrationFields() as $field) {
            $fields[$field->getName()] = $field;
        }

        $this->assertSame(['active', 'active_from', 'name', 'code', 'sort', 'preview_picture', 'preview_text', 'detail_text'], array_keys($fields));
        $this->assertTrue($fields['active']->getDefault());
        $this->assertSame(500, $fields['sort']->getDefault());
    }

    public function test_the_list_shows_the_number_the_name_the_date_and_the_flag(): void
    {
        $this->assertSame(
            ['id', 'name', 'active_from', 'active'],
            array_map(fn ($column): string => $column->getName(), News::getAdministrationListColumns()),
        );
    }

    public function test_the_demo_news_are_dated_sorted_and_pictured(): void
    {
        CarbonImmutable::setTestNow('2026-03-20 15:30:00');
        $this->assertTrue((new NewsDemoSeeder)->run());

        $news = News::query()->orderBy('id')->get();
        $this->assertSame(
            ['2026-03-17 15:30:00', '2026-03-10 15:30:00', '2026-02-28 15:30:00', '2026-03-19 15:30:00'],
            $news->map(fn (News $item): ?string => $item->active_from?->format('Y-m-d H:i:s'))->all(),
        );
        $this->assertSame([500, 500, 500, 500], $news->pluck('sort')->all());
        $this->assertSame(['demo/store.svg', 'demo/sale.svg', 'demo/guide.svg', 'demo/outlet.svg'], $news->pluck('preview_picture')->all());
        $this->assertSame(
            [['de', 'en', 'ru'], ['de', 'en', 'ru'], ['en'], ['de', 'en']],
            // MySQL keeps the keys of a JSON column in its own order.
            $news->map(function (News $item): array {
                $languages = array_keys($item->getTranslations('name'));
                sort($languages);

                return $languages;
            })->all(),
        );

        foreach (['store', 'sale', 'guide', 'outlet'] as $picture) {
            Storage::disk('public')->assertExists("demo/{$picture}.svg");
        }
    }

    public function test_the_preview_of_every_language_is_a_paragraph(): void
    {
        (new NewsDemoSeeder)->run();

        $first = News::query()->orderBy('id')->firstOrFail();

        $this->assertSame('<p>Come and try every laptop and pair of headphones before you buy.</p>', $first->getTranslation('preview_text', 'en'));
        $this->assertSame('<p>Любой ноутбук и наушники можно попробовать до покупки.</p>', $first->getTranslation('preview_text', 'ru'));
    }

    public function test_the_demo_adds_the_three_languages_one_after_another_and_keeps_the_ones_it_finds(): void
    {
        Language::query()->delete();
        (new NewsDemoSeeder)->run();

        $this->assertSame(['en' => 100, 'de' => 101, 'ru' => 102], Language::query()->pluck('sort', 'code')->all());
        $this->assertSame([true, true, true], Language::query()->orderBy('code')->pluck('active')->map(fn ($active): bool => (bool) $active)->all());
        $this->assertSame('Deutsch', Language::query()->where('code', 'de')->value('name'));
    }

    public function test_the_outlet_is_a_site_of_its_own_next_to_the_default_one(): void
    {
        config(['app.url' => 'https://shop.example.com']);
        app(Sites::class)->flush();
        $default = app(Sites::class)->default();
        (new NewsDemoSeeder)->run();

        $outlet = Site::query()->where('code', 'outlet')->sole();
        $this->assertSame('Atrium Outlet', $outlet->name);
        $this->assertSame(['outlet.shop.example.com'], $outlet->domains);
        $this->assertSame(['de', 'en'], $outlet->languages);
        $this->assertSame('de', $outlet->default_language);
        $this->assertTrue($outlet->active);
        $this->assertSame($default->sort + 10, $outlet->sort);
        $this->assertSame($outlet->id, News::query()->orderByDesc('id')->firstOrFail()->site_id);
        $this->assertSame($default->id, News::query()->orderBy('id')->firstOrFail()->site_id);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function hostsWithoutAName(): array
    {
        return [
            'an address' => ['http://127.0.0.1:8000'],
            'no host at all' => ['localhost'],
            'an empty url' => [''],
        ];
    }

    #[DataProvider('hostsWithoutAName')]
    public function test_the_outlet_lives_on_localhost_when_the_app_url_has_no_name(string $url): void
    {
        config(['app.url' => $url]);
        (new NewsDemoSeeder)->run();

        $this->assertSame(['outlet.localhost'], Site::query()->where('code', 'outlet')->sole()->domains);
    }

    public function test_the_sites_of_the_application_know_the_outlet_at_once(): void
    {
        $this->assertSame(['main'], app(Sites::class)->all()->pluck('code')->all());

        (new NewsDemoSeeder)->run();

        $this->assertSame(['main', 'outlet'], app(Sites::class)->all()->pluck('code')->all());
    }

    public function test_the_module_is_called_by_its_own_translation(): void
    {
        $this->assertSame('News', News::getAdminLabel());
    }

    public function test_the_texts_can_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(NewsServiceProvider::class, 'atrium-news-lang');

        $this->assertSame([realpath(dirname(__DIR__, 2).'/src/../lang') => app()->langPath('vendor/atrium-news')], array_combine(array_map('realpath', array_keys($paths)), $paths));
    }
}
