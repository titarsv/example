<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('theme_imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // 0 - Загружен, 1 - Разбирается, 2 - Готов к ревью, 3 - Ошибка
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('archive_path')->nullable();
            // Слаг созданной темы (resources/themes/{theme_name}) — заполняется, как только
            // оркестратор успешно вызвал theme:make; null, пока до этого шага не дошло/если упал раньше.
            $table->string('theme_name')->nullable();
            $table->json('log')->nullable();
            // Классификация каждой найденной app/assets/templates/layouts/*.html страницы —
            // {file, type, source: 'heuristic'|'ai'} на каждую, до какой-либо сборки.
            $table->json('pages')->nullable();
            // Результат сборки/раскладки по каждой странице — для static: то же, что отдаёт
            // PageBuilder::build() (status/name/page_id/template/title); для остальных типов,
            // пока для них нет трансплантера (см. docs/dynamic-page-import-plan.md), — просто
            // {status: 'not_implemented', type}, файл не генерируется, ассеты (scss/картинки) всё
            // равно раскладываются.
            $table->json('built_pages')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_imports');
    }
};