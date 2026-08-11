<?php

namespace Modules\Compare\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Список сравнения — сессионный (как корзина, без привязки к логину), и без
 * ограничений на добавление: любой товар можно положить в список, как на
 * elmir.ua (за количеством не следим — на выдаче это решается горизонтальной
 * прокруткой и полноэкранным режимом). Разделение по типам товаров происходит
 * не при добавлении, а на выдаче — у каждого товара определяется "группа
 * сравнения" (ближайшая своя или родительская категория с allow_compare=true),
 * и друг с другом сравниваются только товары внутри одной группы. Так админ
 * точечно решает, какие категории однородны («Наушники» — можно) без блокировки
 * самого добавления в список.
 */
class CompareService
{
    const SESSION_KEY = 'compare_products';

    /** @var Collection|null id => {id, parent_id, allow_compare} */
    private $categoryMeta = null;

    /** @var array<int, Category|null> кэш уже загруженных категорий на этот запрос */
    private $categoryCache = [];

    public function ids(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function totalCount(): int
    {
        return count($this->ids());
    }

    /**
     * Добавляет/убирает товар из списка. Без ограничений на количество —
     * как в корзину.
     */
    public function toggle(int $productId): array
    {
        $ids = $this->ids();

        if (in_array($productId, $ids)) {
            $ids = array_values(array_diff($ids, [$productId]));
            Session::put(self::SESSION_KEY, $ids);

            return ['result' => 'success', 'in_compare' => false];
        }

        $product = Product::where('visible', 1)->find($productId);
        if (empty($product)) {
            return ['result' => 'error', 'status' => 404, 'message' => 'Товар не найден.'];
        }

        $ids[] = $productId;
        Session::put(self::SESSION_KEY, $ids);

        return ['result' => 'success', 'in_compare' => true];
    }

    /**
     * Очищает список целиком (без аргумента) либо только одну группу
     * (значения группы = id разрешённой категории, либо 0 для "без группы").
     */
    public function clear(?int $categoryId = null): void
    {
        if (is_null($categoryId)) {
            Session::forget(self::SESSION_KEY);

            return;
        }

        $group = $this->groups()->get($categoryId);
        if (empty($group)) {
            return;
        }

        $idsToRemove = $group['products']->pluck('id')->all();
        Session::put(self::SESSION_KEY, array_values(array_diff($this->ids(), $idsToRemove)));
    }

    /**
     * Меняет порядок товаров внутри одной группы (перетаскивание/стрелки на
     * странице сравнения). $orderedIds должен содержать РОВНО те же id, что
     * уже есть в группе — иначе запрос игнорируется (нельзя ни подменить
     * чужой товар, ни вставить новый в обход toggle()). Позиции товаров
     * ДРУГИХ групп в общем списке не трогаются.
     */
    public function reorder(int $categoryId, array $orderedIds): void
    {
        $group = $this->groups()->get($categoryId);
        if (empty($group)) {
            return;
        }

        $validIds = $group['products']->pluck('id')->all();
        $orderedIds = array_values(array_intersect($orderedIds, $validIds));
        if (count($orderedIds) !== count($validIds)) {
            return;
        }

        $queue = $orderedIds;
        $result = array_map(function ($id) use ($validIds, &$queue) {
            return in_array($id, $validIds) ? array_shift($queue) : $id;
        }, $this->ids());

        Session::put(self::SESSION_KEY, $result);
    }

    /**
     * Товары из списка сравнения, сгруппированные по группе сравнения.
     * Ключ — id разрешённой (allow_compare=true) категории, либо 0, если ни
     * у одной из категорий товара такая не нашлась (тогда группируем по его
     * собственной прямой категории как есть — так список не выглядит
     * пустым/сломанным, пока админ не настроил allow_compare).
     *
     * @return Collection<int, array{category: Category|null, products: Collection<Product>}>
     */
    public function groups(): Collection
    {
        $ids = $this->ids();
        if (empty($ids)) {
            return collect();
        }

        $products = Product::whereIn('id', $ids)->where('visible', 1)->with(['image.images'])->get()->keyBy('id');
        $ordered = collect($ids)->map(fn($id) => $products->get($id))->filter()->values();

        // Товар мог стать невидимым/удалиться с момента добавления —
        // синхронизируем сессию, чтобы счётчики не врали.
        if ($ordered->count() !== count($ids)) {
            Session::put(self::SESSION_KEY, $ordered->pluck('id')->all());
        }

        $groups = collect();
        foreach ($ordered as $product) {
            $category = $this->groupFor($product);
            $key = $category ? $category->id : 0;

            if (!$groups->has($key)) {
                $groups->put($key, ['category' => $category, 'products' => collect()]);
            }

            $groups->get($key)['products']->push($product);
        }

        return $groups;
    }

    /**
     * Импорт явного набора товаров из расшаренной ссылки (?ids=1,2,3):
     * заменяет в сессии товары ТОЙ ЖЕ группы сравнения на переданный набор
     * один в один (в его порядке), а не добавляет к уже сохранённому у
     * посетителя списку той же категории — иначе по одной и той же ссылке
     * разные люди увидели бы разные наборы товаров. Остальные группы в
     * сессии (другие категории) не трогает.
     *
     * @return Category|null группа (категория), в которую сложились товары
     */
    public function importIds(array $ids): ?Category
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return null;
        }

        $newProducts = Product::whereIn('id', $ids)->where('visible', 1)->get()->keyBy('id');
        $orderedNew = collect($ids)->map(fn($id) => $newProducts->get($id))->filter()->values();
        if ($orderedNew->isEmpty()) {
            return null;
        }

        $category = $this->groupFor($orderedNew->first());
        $categoryId = $category ? $category->id : 0;

        $currentIds = collect($this->ids());
        $existingProducts = Product::whereIn('id', $currentIds)->get()->keyBy('id');

        $keptIds = $currentIds->reject(function ($id) use ($existingProducts, $categoryId) {
            $product = $existingProducts->get($id);
            if (!$product) {
                return true;
            }

            $group = $this->groupFor($product);

            return ($group ? $group->id : 0) === $categoryId;
        });

        Session::put(self::SESSION_KEY, $keptIds->merge($orderedNew->pluck('id'))->unique()->values()->all());

        return $category;
    }

    /**
     * Компактное представление групп для JSON-ответов и шапки сайта —
     * без самих товаров, только счётчики.
     */
    public function groupsSummary(): Collection
    {
        return $this->groups()->map(function ($group) {
            $category = $group['category'];
            $name = $category ? $category->name : null;

            return [
                'id' => $category ? $category->id : 0,
                'name' => $name ?: 'Без категории',
                'count' => $group['products']->count(),
            ];
        })->values();
    }

    /**
     * Ближайшая (своя или родительская) категория товара с allow_compare=true.
     * Если такой нет ни в одной ветке — возвращает собственную первую прямую
     * категорию товара как есть (см. groups()).
     */
    public function groupFor(Product $product): ?Category
    {
        $this->loadCategoryMeta();

        $directCategoryIds = $product->categories()->pluck('categories.id');
        if ($directCategoryIds->isEmpty()) {
            return null;
        }

        foreach ($directCategoryIds as $categoryId) {
            $current = $categoryId;
            $seen = [];

            while (!empty($current) && !isset($seen[$current]) && $this->categoryMeta->has($current)) {
                $meta = $this->categoryMeta->get($current);
                if ($meta->allow_compare) {
                    return $this->categoryById($current);
                }
                $seen[$current] = true;
                $current = $meta->parent_id;
            }
        }

        return $this->categoryById($directCategoryIds->first());
    }

    private function loadCategoryMeta(): void
    {
        if (is_null($this->categoryMeta)) {
            $this->categoryMeta = DB::table('categories')->get(['id', 'parent_id', 'allow_compare'])->keyBy('id');
        }
    }

    private function categoryById(int $id): ?Category
    {
        if (!array_key_exists($id, $this->categoryCache)) {
            $this->categoryCache[$id] = Category::with('localization')->find($id);
        }

        return $this->categoryCache[$id];
    }

    /**
     * Строки таблицы сравнения для одной группы: по одной на атрибут, у
     * которого заполнено значение хотя бы у одного из товаров. Пустые для
     * всех — пропускаются, чтобы не показывать бесполезные строки-заглушки.
     *
     * @param Collection<Product> $products
     */
    public function comparisonRows(Collection $products): Collection
    {
        if ($products->isEmpty()) {
            return collect();
        }

        $productIds = $products->pluck('id');

        $pivotRows = DB::table('product_attributes')
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'attribute_id', 'attribute_value_id']);

        if ($pivotRows->isEmpty()) {
            return collect();
        }

        $attributeIds = $pivotRows->pluck('attribute_id')->unique();
        $attributes = Attribute::whereIn('id', $attributeIds)->where('visible', 1)->with('localization')->get()->keyBy('id');

        $valueIds = $pivotRows->pluck('attribute_value_id')->unique();
        $values = AttributeValue::whereIn('id', $valueIds)->with('localization')->get()->keyBy('id');

        $byAttribute = $pivotRows->groupBy('attribute_id');

        $rows = collect();
        foreach ($attributeIds as $attributeId) {
            $attribute = $attributes->get($attributeId);
            if (empty($attribute)) {
                continue;
            }

            $attributeName = $attribute->name;
            if (empty($attributeName)) {
                continue;
            }

            $attributeRows = $byAttribute->get($attributeId);
            $cells = [];
            $hasValue = false;

            foreach ($productIds as $productId) {
                $productValueNames = $attributeRows->where('product_id', $productId)
                    ->map(fn($row) => $values->get($row->attribute_value_id)?->name)
                    ->filter()
                    ->unique()
                    ->values();

                $text = $productValueNames->isNotEmpty()
                    ? $productValueNames->implode(', ').$attribute->unit
                    : null;

                $cells[$productId] = $text;
                if (!empty($text)) {
                    $hasValue = true;
                }
            }

            if (!$hasValue) {
                continue;
            }

            $distinct = collect($cells)->map(fn($text) => $text ?? '')->unique();

            $rows->push([
                'name' => $attributeName,
                'cells' => $cells,
                'differs' => $distinct->count() > 1,
            ]);
        }

        return $rows->sortBy('name')->values();
    }
}
