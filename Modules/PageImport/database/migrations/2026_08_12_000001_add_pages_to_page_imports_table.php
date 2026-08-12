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
            // Результат разбора каждой найденной html-страницы архива: контент <main> (с уже
            // переписанными на медиатеку src картинок) + счётчики перенесённых картинок/иконок.
            // Полноценные записи под каждую страницу (стадия 2.1/2.2) появятся позже — пока
            // это просто json, чтобы не фиксировать реляционную схему раньше времени.
            $table->json('pages')->nullable()->after('log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_imports', function (Blueprint $table) {
            $table->dropColumn('pages');
        });
    }
};