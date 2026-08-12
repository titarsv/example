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
        Schema::table('page_imports', function (Blueprint $table) {
            // Результат PageBuilder::build() по каждой странице архива: созданный шаблон/Page
            // (или причина пропуска/ошибки) — то, что покажет экран ревью импорта (стадия 2.4).
            $table->json('imported_pages')->nullable()->after('pages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_imports', function (Blueprint $table) {
            $table->dropColumn('imported_pages');
        });
    }
};