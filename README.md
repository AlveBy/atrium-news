# Atrium News

News for [Atrium](https://github.com/AlveBy/atrium). Each site has its own news, written in its content languages. Every language has its own address and a site lists only its own news.

## Install

```bash
composer require alveby/atrium-news
php artisan atrium:module:install alveby/atrium-news
```

Add `--demo` to fill it with four news items. The demo also adds German and Russian next to English, and a second site, the outlet, at `outlet.<your host>`, in German and English.

## On the site

With the public theme (`alveby/atrium-theme`):

| Address | Page |
|---|---|
| `/news` | the list |
| `/news/a-new-store-in-the-city-centre` | one news item |
| `/de/news/neue-filiale-in-der-innenstadt` | the same in German |

To move the pages, list `Atrium\News\Models\News` in `config/atrium.php` under `public_urls`.

## Contributing

This repository is a read-only copy of `packages/news` in [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
