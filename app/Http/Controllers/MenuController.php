<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Localization;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Menu;

class MenuController extends Controller
{
    /**
     * List menus
     *
     * @return $this
     */
    public function adminIndexAction(){
        return view('admin.menu.index')
            ->with('menus', Menu::paginate(10));
    }

    /**
     * Create menu
     *
     * @param Request $request
     * @param Menu $menus
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStoreAction(Request $request, Menu $menus){
        return response()->json(['result' => 'success', 'redirect' => '/admin/menu/edit/1']);
        $validator = Validator::make($request->all(),
            ['name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => 'required'],
            ['name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '').'.required' => trans('locale.menu.name_required')]
        );

        if($validator->fails()){
            return response()->json($validator);
        }

        $id = $menus->insertGetId(['status' => 1]);
        $menu = $menus->find($id);
        $menu->saveLocalization($request);

        return response()->json(['result' => 'success', 'redirect' => '/admin/menu/edit/'.$id]);
    }

    /**
     * Get pages with optimized localization loading
     */
    private function getPagesWithLocalization($limit, $order, $paginate = false){
        if($paginate){
            $pages = Page::orderBy('id', $order)->with('seo')->paginate($limit);
            $this->loadOptimizedLocalization($pages->getCollection(), 'Pages',);
            return $pages;
        } else {
            $pages = Page::orderBy('id', $order)->with('seo')->limit($limit)->get();
            $this->loadOptimizedLocalization($pages, 'Pages');
            return $pages;
        }
    }

    /**
     * Get categories with optimized localization loading
     */
    private function getCategoriesWithLocalization($limit, $order, $paginate = false){
        if($paginate){
            $categories = Category::orderBy('id', $order)->with('seo')->paginate($limit);
            $this->loadOptimizedLocalization($categories->getCollection(), 'Categories');
            $this->preloadCategoryTreeNames($categories->getCollection());
            return $categories;
        } else {
            $categories = Category::orderBy('id', $order)->with('seo')->limit($limit)->get();
            $this->loadOptimizedLocalization($categories, 'Categories');
            $this->preloadCategoryTreeNames($categories);
            return $categories;
        }
    }

    /**
     * Preload tree names for all categories to avoid N+1 queries
     */
    private function preloadCategoryTreeNames($categories){
        if($categories->isEmpty()){
            return;
        }
        
        // Get all category IDs
        $categoryIds = $categories->pluck('id')->toArray();
        
        // Load all categories in single query
        $allCategories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');
        
        // Preload localizations for all categories
        $this->loadOptimizedLocalization($allCategories, 'Categories');
        
        // Precompute tree names for all categories
        foreach($categories as $category){
            if(isset($allCategories[$category->id])){
                $category->tree_name_preloaded = $this->buildTreeNameForCategory($allCategories[$category->id], $allCategories);
            }
        }
    }
    
    /**
     * Build tree name for a specific category using preloaded data
     */
    private function buildTreeNameForCategory($category, $allCategories){
        $path = [];
        $currentId = $category->id;
        
        // Build path using preloaded categories
        while ($currentId > 0) {
            if(isset($allCategories[$currentId])){
                $path[] = $allCategories[$currentId];
                $currentId = $allCategories[$currentId]->parent_id;
            } else {
                break;
            }
        }
        
        $name = '';
        foreach(array_reverse($path) as $cat){
            if(!empty($name)){
                $name .= ' > ';
            }
            $name .= $this->getOptimizedName($cat);
        }
        
        return $name;
    }

    /**
     * Get optimized name using preloaded localizations
     */
    private function getOptimizedName($category){
        // If localization is already loaded, use it
        if($category->relationLoaded('localization') && $category->localization->isNotEmpty()){
            $localization = $category->localization->where('field', 'name')->first();
            if($localization){
                return $localization->value;
            }
        }

        // Fallback to original name property
        return $category->name;
    }

    /**
     * Load localizations for multiple entities in single queries
     */
    private function loadOptimizedLocalization($entities, $entityType){
        if($entities->isEmpty()){
            return;
        }

        $entityIds = $entities->pluck('id')->toArray();
        $locales = Config::get('app.locales');

        // Load all localizations for all locales in one query
        $localizations = Localization::whereIn('localizable_id', $entityIds)
            ->where('localizable_type', $entityType)
            ->whereIn('language', $locales)
            ->get()
            ->groupBy(['localizable_id', 'language']);

        // Attach localizations to entities
        foreach($entities as $entity){
            $entityLocalizations = collect();
            foreach($locales as $lang){
                if(isset($localizations[$entity->id][$lang])){
                    $entityLocalizations = $entityLocalizations->merge($localizations[$entity->id][$lang]);
                }
            }
            $entity->setRelation('localization', $entityLocalizations);
        }
    }

    /**
     * Editor menu
     *
     * @param $id
     * @return mixed
     */
    public function adminShowAction($id){
        // Load pages with optimized localization
        $new_pages = $this->getPagesWithLocalization(15, 'desc');
        $all_pages = $this->getPagesWithLocalization(50, 'asc', true);

        // Load categories with optimized localization
        $new_categories = $this->getCategoriesWithLocalization(15, 'desc');
        $all_categories = $this->getCategoriesWithLocalization(50, 'asc', true);

        // Load menu items with optimized localization
        $items = MenuItem::where('menu_id', $id)->orderBy('position', 'asc')->get();
        $this->loadOptimizedLocalization($items, 'MenuItems', Config::get('app.locale'));

        return view('admin.menu.show')
            ->with('new_pages', $new_pages)
            ->with('all_pages', $all_pages)
            ->with('new_categories', $new_categories)
            ->with('all_categories', $all_categories)
            ->with('items', $items)
            ->with('menu', Menu::find($id))
            ->with('locales_names', Config::get('app.locales_names'))
            ->with('locales', Config::get('app.locales'))
            ->with('main_lang', Config::get('app.locale'));
    }

    /**
     * Update menu
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function adminUpdateAction(Request $request, $id){
        $data = [];
        $locales = Config::get('app.locales');

        $fields = [
            'menu-item-title',
            'menu-item-attr-title',
            'menu-item-target',
            'menu-item-classes',
            'menu-item-xfn',
            'menu-item-image',
            'menu-item-description',
            'menu-item-db-id',
            'menu-item-object-id',
            'menu-item-object',
            'menu-item-parent-id',
            'menu-item-position',
            'menu-item-type',
            'menu-item-url',
            'menu-item-with-children',
        ];

        foreach($locales as $locale){
            $fields[] = 'menu-item-title_'.$locale;
            $fields[] = 'menu-item-attr-title_'.$locale;
            $fields[] = 'menu-item-description_'.$locale;
        }

        foreach(array_reverse($request->only($fields)) as $key => $values){
            foreach($values as $id => $value){
                $data[$id][$key] = $value;
            }
        }

        $items = MenuItem::select('id')
            ->where('menu_id', $request->menu)
            ->whereNotIn('id', array_keys($data))
            ->get();
        if(!empty($items)){
            Localization::where('localizable_type', 'MenuItem')
                ->whereIn('localizable_id', $items->pluck('id')->toArray())
                ->delete();
            MenuItem::where('menu_id', $request->menu)->whereNotIn('id', array_keys($data))->delete();
        }

        $i = 1;
        foreach($data as $id => $item){
            $depth = 0;
            if(!empty($item['menu-item-parent-id'])){
                $last_depth = MenuItem::find($item['menu-item-parent-id']);
                $depth = !empty($last_depth) ? $last_depth->depth + 1 : 0;
            }

            MenuItem::where('id', $id)->update([
                'menu_id' => $request->menu,
                'parent_id' => isset($item['menu-item-parent-id']) ? $item['menu-item-parent-id'] : 0,
                'type' => isset($item['menu-item-object']) ? $item['menu-item-object'] : (isset($item['menu-item-type']) ? $item['menu-item-type'] : ''),
                'value' => isset($item['menu-item-type']) && $item['menu-item-type'] == 'custom' ? $item['menu-item-url'] : $item['menu-item-object-id'],
                'class' => isset($item['menu-item-classes']) ? $item['menu-item-classes'] : '',
                'blank' => !empty($item['menu-item-target']),
                'xfn' => isset($item['menu-item-xfn']) ? $item['menu-item-xfn'] : null,
                'position' => isset($item['menu-item-position']) ? $item['menu-item-position'] :$i,
                'depth' => $depth,
                'image_id' => isset($item['menu-item-image']) ? $item['menu-item-image'] : null,
                'with_children' => !empty($item['menu-item-with-children']),
            ]);

            $menu_item = MenuItem::find($id);

            if(!empty($menu_item)){
                $rq = new Request();

                if(count($locales) > 1){
                    foreach($locales as $locale){
                        $rd['name_'.$locale] = $item['menu-item-title_'.$locale];
                        $rd['title_'.$locale] = isset($item['menu-item-attr-title_'.$locale]) ? $item['menu-item-attr-title_'.$locale] : '';
                        $rd['description_'.$locale] = $item['menu-item-description_'.$locale];
                    }
                }else{
                    $rd['name'] = $item['menu-item-title'];
                    $rd['title'] = isset($item['menu-item-attr-title']) ? $item['menu-item-attr-title'] : '';
                    $rd['description'] = $item['menu-item-description'];
                }

                $rq->merge($rd);

                $menu_item->saveLocalization($rq);

                $i++;
            }
        }

        foreach($locales as $locale){
            $rq = new Request();
            $rd = [];
            if(count($locales) > 1){
                foreach($locales as $locale){
                    $rd['name_'.$locale] = $request->{'menu-name'.'_'.$locale};
                }
            }else{
                $rd['name'] = $request->{'menu-name'};
            }
            $rq->merge($rd);
            $menu = Menu::find($request->menu);
            $menu->saveLocalization($rq);
            Cache::forget('menu_'.$request->menu.'_'.$locale);
        }

        return redirect()->back();
    }

    public function adminDestroyAction($id){
        $menu = Menu::find($id);
        $name = $menu->name;

        $items = MenuItem::select('id')->where('menu_id', $id)->get();
        if(!empty($items)){
            Localization::where('localizable_type', 'MenuItem')
                ->whereIn('localizable_id', $items->pluck('id')->toArray())
                ->delete();
            MenuItem::where('menu_id', $id)->delete();
        }
        Localization::where('localizable_type', 'Menu')
            ->where('localizable_id', $id)
            ->delete();
        $menu->delete();

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.Menu').' "' . $name . '" ' . trans('locale.menu.successfully_deleted')
        ], 200);
    }

    public function adminUpdateStatusAction(Request $request, $id){
        $menu = Menu::find($id);
        if(empty($menu)){
            return json_encode([]);
        }

        $menu->update(['status' => $request->value]);

        return json_encode($menu->toArray());
    }
}
