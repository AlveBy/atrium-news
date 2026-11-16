<?php

declare(strict_types=1);

namespace Atrium\News\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\DateTimeField;
use Atrium\Admin\Fields\ImageField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SlugField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * News of one site, written in several languages: each language has its own
 * address, and a site lists only its own news.
 *
 * @property int $id
 * @property int|null $site_id
 */
class News extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'news';

    /** @var array<int,string> */
    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    protected $fillable = ['site_id', 'name', 'code', 'active', 'active_from', 'sort', 'preview_picture', 'preview_text', 'detail_text'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'active_from' => 'datetime', 'sort' => 'integer'];
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-news::news.module');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'news';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'active_from';
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            DateTimeField::make('active_from')->label(__('atrium::fields.standard.active_from')),
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('news', 'code'),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            ImageField::make('preview_picture')->label(__('atrium::fields.standard.preview_picture'))->directory('news'),
            ContentField::make('preview_text')->label(__('atrium::fields.standard.preview_text')),
            ContentField::make('detail_text')->label(__('atrium::fields.standard.detail_text')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            DateColumn::make('active_from')->label(__('atrium::fields.standard.active_from'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
