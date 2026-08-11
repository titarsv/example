<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('localization', function (Blueprint $table) {
            $table->index(['localizable_type', 'localizable_id', 'language', 'field'], 'localization_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('localization', function (Blueprint $table) {
            $table->dropIndex('localization_lookup_index');
        });
    }
};