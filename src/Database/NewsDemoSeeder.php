<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Database;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Support\Sites;
use Atrium\News\Models\News;

/**
 * News of the demo shop in three languages on two sites: it adds German and
 * Russian next to English, and a second site, the outlet, in German.
 */
final class NewsDemoSeeder extends DemoSeeder
{
    public function run(): bool
    {
        if (News::query()->exists()) {
            return false;
        }

        $this->copyFiles(dirname(__DIR__, 2).'/resources/demo');

        $default = app(Sites::class)->default();
        $outlet = $this->outlet($default);

        $news = [
            [$default, 3, 'store.svg', [
                'en' => ['A new store in the city centre', 'a-new-store-in-the-city-centre', 'Come and try every laptop and pair of headphones before you buy.', '<p>Our second store opened on Market Street. Every model from the catalog is on the tables: open it, type on it, listen to it.</p><p>Open daily from 10:00 to 21:00.</p>'],
                'de' => ['Neue Filiale in der Innenstadt', 'neue-filiale-in-der-innenstadt', 'Probieren Sie jedes Notebook und jeden Kopfhörer vor dem Kauf aus.', '<p>Unsere zweite Filiale in der Market Street hat eröffnet. Alle Modelle aus dem Katalog liegen zum Ausprobieren bereit.</p><p>Täglich von 10 bis 21 Uhr geöffnet.</p>'],
                'ru' => ['Новый магазин в центре города', 'novyj-magazin-v-centre', 'Любой ноутбук и наушники можно попробовать до покупки.', '<p>Наш второй магазин открылся на Рыночной улице. Все модели из каталога выставлены: открывайте, печатайте, слушайте.</p><p>Работаем каждый день с 10:00 до 21:00.</p>'],
            ]],
            [$default, 10, 'sale.svg', [
                'en' => ['Autumn sale: 20% off headphones', 'autumn-sale', 'Until the end of the month, every pair of Vantor headphones is 20% cheaper.', '<p>The discount is applied at checkout, online and in the stores.</p>'],
                'de' => ['Herbstaktion: 20 % auf Kopfhörer', 'herbstaktion', 'Bis Monatsende sind alle Vantor-Kopfhörer 20 % günstiger.', '<p>Der Rabatt wird an der Kasse abgezogen, online und in den Filialen.</p>'],
                'ru' => ['Осенняя распродажа: −20% на наушники', 'osennyaya-rasprodazha', 'До конца месяца все наушники Vantor дешевле на 20%.', '<p>Скидка применяется при оформлении заказа — на сайте и в магазинах.</p>'],
            ]],
            // English only: it has no German or Russian page.
            [$default, 20, 'guide.svg', [
                'en' => ['How to choose a laptop for study', 'how-to-choose-a-laptop-for-study', 'Weight, battery and screen matter more than the processor.', '<h2>Weight</h2><p>You will carry it every day: under 1.5 kg is comfortable.</p><h2>Battery</h2><p>A full day of lectures needs at least 10 hours.</p><h2>Screen</h2><p>A matte screen is easier on the eyes in a bright room.</p>'],
            ]],
            [$outlet, 1, 'outlet.svg', [
                'de' => ['Das Outlet öffnet am Samstag', 'outlet-eroeffnung', 'Vorführgeräte und Vorjahresmodelle bis zu 40 % günstiger.', '<p>Alle Geräte sind geprüft und haben die volle Garantie.</p>'],
                'en' => ['The outlet opens on Saturday', 'outlet-opening', 'Display units and last year’s models up to 40% off.', '<p>Every device is checked and comes with the full warranty.</p>'],
            ]],
        ];

        foreach ($news as [$site, $daysAgo, $picture, $texts]) {
            $record = new News([
                'site_id' => $site->id,
                'active_from' => now()->subDays($daysAgo),
                'sort' => 500,
                'preview_picture' => $this->path($picture),
            ]);

            foreach ($texts as $language => [$name, $code, $preview, $detail]) {
                $record->setTranslation('name', $language, $name)
                    ->setTranslation('code', $language, $code)
                    ->setTranslation('preview_text', $language, '<p>'.$preview.'</p>')
                    ->setTranslation('detail_text', $language, $detail);
            }

            $record->save();
        }

        return true;
    }

    /**
     * German and Russian next to English, and the outlet: a site of its own
     * on a subdomain that speaks English and German and opens in German.
     */
    private function outlet(Site $default): Site
    {
        foreach (['en' => 'English', 'de' => 'Deutsch', 'ru' => 'Русский'] as $code => $name) {
            Language::query()->firstOrCreate(['code' => $code], ['name' => $name, 'active' => true, 'sort' => 100 + Language::query()->count()]);
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $host = $host === '' || filter_var($host, FILTER_VALIDATE_IP) ? 'localhost' : $host;

        $outlet = Site::query()->firstOrCreate(['code' => 'outlet'], [
            'name' => 'Atrium Outlet',
            'domains' => ['outlet.'.$host],
            'languages' => ['de', 'en'],
            'default_language' => 'de',
            'active' => true,
            'sort' => (int) $default->sort + 10,
        ]);

        app(Sites::class)->flush();

        return $outlet;
    }
}
