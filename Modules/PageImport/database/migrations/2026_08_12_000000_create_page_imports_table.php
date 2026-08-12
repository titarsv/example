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
        Schema::create('page_imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // 0 - Загружен, 1 - Разбирается, 2 - Готов к ревью, 3 - Опубликован, 4 - Ошибка
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('archive_path')->nullable();
            $table->json('log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_imports');
    }
};