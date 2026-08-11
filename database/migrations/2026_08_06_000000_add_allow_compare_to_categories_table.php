<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Товары в этом каталоге привязаны только к своей самой узкой
 * facet-категории (например «Серия: iPhone 15 Pro Max»), а не ко всей
 * цепочке родителей, и сама цепочка не гарантированно однородна по типу
 * товара (см. Modules/Compare) — поэтому совместимость для сравнения не
 * вывести автоматически из дерева категорий. Вместо этого админ явно
 * отмечает категории, товары внутри которых можно сравнивать друг с другом
 * (например «Наушники» — можно, а «Звук и музыка» — нет, там вперемешку
 * разные типы товаров).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('allow_compare')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('allow_compare');
        });
    }
};