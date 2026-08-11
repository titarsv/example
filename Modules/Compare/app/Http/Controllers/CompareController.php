<?php

namespace Modules\Compare\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Compare\Services\CompareService;

class CompareController extends Controller
{
    /**
     * Страница сравнения. Без ?category — список групп (табы), если групп
     * больше одной; с ?category=ID — таблица сравнения конкретной группы.
     * Если группа всего одна, открываем её сразу, без лишнего клика.
     *
     * ?ids=1,2,3 — расшаренная ссылка («Скопировать ссылку» / Telegram и
     * т.п. на странице сравнения): импортирует ровно этот набор товаров в
     * сессию (заменяя товары той же группы) и редиректит на обычный
     * ?category=ID, чтобы у любого, кто перейдёт по ссылке, открылся тот же
     * набор товаров, что был у отправителя, а не пустой список / свой список.
     */
    public function indexAction(Request $request, CompareService $compare)
    {
        $sharedIds = $request->query('ids');
        if ($sharedIds !== null && $sharedIds !== '') {
            $category = $compare->importIds(explode(',', $sharedIds));

            return redirect(url_path('/compare') . '?category=' . ($category ? $category->id : 0));
        }

        $groups = $compare->groups();

        $activeCategoryId = $request->query('category');
        if ($activeCategoryId !== null) {
            $activeCategoryId = (int) $activeCategoryId;
        } elseif ($groups->count() === 1) {
            $activeCategoryId = $groups->keys()->first();
        }

        $activeGroup = !is_null($activeCategoryId) ? $groups->get($activeCategoryId) : null;

        return view('public.compare')
            ->with('groups', $groups)
            ->with('activeCategoryId', $activeCategoryId)
            ->with('activeGroup', $activeGroup)
            ->with('rows', $activeGroup ? $compare->comparisonRows($activeGroup['products']) : collect());
    }

    public function toggleAction(Request $request, CompareService $compare)
    {
        $result = $compare->toggle((int) $request->product_id);
        $status = $result['status'] ?? 200;
        unset($result['status']);

        if ($result['result'] === 'success') {
            $groups = $compare->groupsSummary();
            $result['groups'] = $groups;
            $result['count'] = $groups->sum('count');
        }

        return response()->json($result, $status);
    }

    public function clearAction(Request $request, CompareService $compare)
    {
        $categoryId = $request->input('category_id');
        $compare->clear($categoryId !== null ? (int) $categoryId : null);

        return response()->json(['result' => 'success']);
    }

    public function reorderAction(Request $request, CompareService $compare)
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        $compare->reorder((int) $request->input('category_id'), $ids);

        return response()->json(['result' => 'success']);
    }
}