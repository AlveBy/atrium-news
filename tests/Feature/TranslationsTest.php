<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\News\Tests\Feature;

use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;

/**
 * Every admin language has every line English has, and no other.
 */
final class TranslationsTest extends TestCase
{
    public function test_every_language_has_the_english_keys(): void
    {
        $dir = dirname(__DIR__, 2).'/lang';
        $english = array_keys(Arr::dot(require $dir.'/en/news.php'));
        $languages = array_diff(scandir($dir) ?: [], ['.', '..']);

        $this->assertCount(15, $languages);

        foreach ($languages as $language) {
            $lines = Arr::dot(require $dir.'/'.$language.'/news.php');

            $this->assertSame($english, array_keys($lines), $language);
            $this->assertNotContains('', $lines, $language);
        }
    }
}
