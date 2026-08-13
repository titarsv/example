<?php

namespace Modules\ThemeImport\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeImport extends Model
{
    const STATUS_UPLOADED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_REVIEW = 2;
    const STATUS_ERROR = 3;

    protected $table = 'theme_imports';

    protected $fillable = [
        'name',
        'status',
        'archive_path',
        'theme_name',
        'log',
        'pages',
        'built_pages',
    ];

    protected $casts = [
        'log' => 'array',
        'pages' => 'array',
        'built_pages' => 'array',
    ];

    /**
     * Человекочитаемая метка статуса для админки.
     */
    public function statusLabel(): string
    {
        return match((int) $this->status) {
            self::STATUS_UPLOADED => trans('locale.theme_import.status_uploaded'),
            self::STATUS_PROCESSING => trans('locale.theme_import.status_processing'),
            self::STATUS_REVIEW => trans('locale.theme_import.status_review'),
            self::STATUS_ERROR => trans('locale.theme_import.status_error'),
            default => (string) $this->status,
        };
    }

    /**
     * Путь к рабочей директории импорта (распакованный архив с исходниками верстальщика).
     */
    public function workPath(): string
    {
        return storage_path('app/theme_imports/'.$this->id);
    }

    /**
     * Путь к распакованному содержимому архива.
     */
    public function extractedPath(): string
    {
        return $this->workPath().DIRECTORY_SEPARATOR.'extracted';
    }
}