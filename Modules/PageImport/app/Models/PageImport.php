<?php

namespace Modules\PageImport\Models;

use Illuminate\Database\Eloquent\Model;

class PageImport extends Model
{
    const STATUS_UPLOADED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_REVIEW = 2;
    const STATUS_PUBLISHED = 3;
    const STATUS_ERROR = 4;

    protected $table = 'page_imports';

    protected $fillable = [
        'name',
        'status',
        'archive_path',
        'log',
        'pages',
        'imported_pages',
    ];

    protected $casts = [
        'log' => 'array',
        'pages' => 'array',
        'imported_pages' => 'array',
    ];

    /**
     * Человекочитаемая метка статуса для админки.
     */
    public function statusLabel(): string
    {
        return match((int) $this->status) {
            self::STATUS_UPLOADED => trans('locale.page_import.status_uploaded'),
            self::STATUS_PROCESSING => trans('locale.page_import.status_processing'),
            self::STATUS_REVIEW => trans('locale.page_import.status_review'),
            self::STATUS_PUBLISHED => trans('locale.page_import.status_published'),
            self::STATUS_ERROR => trans('locale.page_import.status_error'),
            default => (string) $this->status,
        };
    }

    /**
     * Путь к рабочей директории импорта (распакованный архив).
     */
    public function workPath(): string
    {
        return storage_path('app/page_imports/'.$this->id);
    }

    /**
     * Путь к распакованному содержимому архива.
     */
    public function extractedPath(): string
    {
        return $this->workPath().DIRECTORY_SEPARATOR.'extracted';
    }
}