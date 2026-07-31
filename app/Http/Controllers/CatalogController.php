<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Filter;
use App\Models\Sale;
use App\Models\Seo;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Каталог товаров
     *
     * @param $data
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showAction($data){
        $filter = new Filter();
        if($data->seo->seotable_type == 'Catalog'){
            $parts = explode('/', trim($data->seo->url, '/'));
            $category_seo = Seo::where('url', '/'.$parts[0])->where('seotable_type', 'Categories')->first();
            if(!empty($category_seo)){
                $category = $category_seo->seotable;
                $filter->setCategory($category);
            }
            $data->params = array_unique(array_merge(isset($data->params) ? (array)$data->params : [], array_slice($parts, 1)));
        }
        $filter->setProductAttributes();

        if(isset($data->request['text'])){
            $filter->setSearchText($data->request->input('text'));
        }

        if(isset($data->params)){
            foreach($data->params as $param){
                if(!empty($param))
                    $filter->setParam($param);
            }
        }

        if(!empty($filter->getUndefined())){
            abort( 404 );
        }

        $orders = [
            'priority-asc' => ['sort_priority', 'asc'],
            'popularity-desc' => ['popularity', 'desc'],
            'rating-desc' => ['rating', 'desc'],
            'created-desc' => ['created', 'desc'],
            'price-asc' => ['price', 'asc'],
            'price-desc' => ['price', 'desc'],
        ];

        $sale_products = null;
        $sale = Sale::first();
        if(!empty($sale))
            $sale_products = $sale->products;

        $viewed = json_decode($data->request->cookie('viewed'), true);
        $products = new Product();

        if(!empty($data->seo)){
            $data->seo->load('localization');
        }

        $attributes = $filter->getFilter();
        if(!empty($attributes) && $data->seo->url != '/'.$data->request->path()){
            if(count($attributes) > 1
                || (!empty($val_ids) && count($val_ids) > 1)
                || !empty($data->request->order)
                || (empty($filter->getCurrentCategory()) && count($attributes))){
                $data->seo->robots = 'noindex, nofollow';
            }
        }

        if($filter->with_price_filter || $filter->with_stock_filter || (!empty($data->seo->seotable_id) && !in_array($data->seo->seotable_id, [3, 4, 5]))){
            $data->seo->robots = 'noindex, nofollow';
        }

        if(!empty($data->request->order) || $filter->isSale()){
            $data->seo->canonical = str_replace('/sale', '', $data->request->url());
        }

        return view('public.catalog')
            ->with('seo', $data->seo)
            ->with('category', isset($category_seo) ? $category_seo->seotable : null)
            ->with('products', $filter->getProducts(isset($orders[$data->request->order]) ? $orders[$data->request->order] : ['sort_priority', 'asc']))
            ->with('filter', $filter->getFilterAttributes(true))
            ->with('selected_filters', $filter->getSelectedFilters())
            ->with('price', $filter->getPriceSlider())
            ->with('view', empty($data->request->view) || !in_array($data->request->view, ['grid', 'list']) ? 'grid' : $data->request->view)
            ->with('path', $data->request->path())
            ->with('sale', $sale)
            ->with('viewed', !is_null($viewed) ? $products->getProducts($viewed) : null)
            ->with('sale_products', $sale_products)
            ->with('categories', Category::where('status', 1)->get())
            ->with('effects', AttributeValue::where('attribute_id', 1)->get())
            ->withShortcodes();
    }

    public function filterAction(Request $request){
        $orders = [
            'priority-asc' => ['sort_priority', 'asc'],
            'popularity-desc' => ['popularity', 'desc'],
            'rating-desc' => ['rating', 'desc'],
            'created-desc' => ['created', 'desc'],
            'price-asc' => ['price', 'asc'],
            'price-desc' => ['price', 'desc'],
        ];

        $filter = new Filter();
        $category = Category::find($request->category);
        $filter->setCategory($category);
        if(!empty($request->page) && $request->page > 1)
            $filter->setPage((int)$request->page);

        if(!empty($request->is_sale)){
            $filter->setIsSale($request->is_sale);
        }

        if(!empty($request->sale)){
            $filter->setSale($request->sale);
        }

        if(!empty($request->stock)){
            $filter->setStock($request->stock);
        }

        if($request->price_min !== null){
            $filter->setMinPrice($request->price_min);
        }

        if($request->price_max !== null){
            $filter->setMaxPrice($request->price_max);
        }

        if(!empty($request->search_text)){
            $filter->setSearchText($request->search_text);
        }

        $filter->setAttributesValues($request->filters);
        $products = $filter->getProducts(isset($orders[$request->order]) ? $orders[$request->order] : ['sort_priority', 'asc']);
        $attributes = $filter->getFilterAttributes(true);
        $name = !empty($category) ? $category->seo->name : 'THC Products';

        return response()->json([
            'result' => 'success',
            'html' => view('public.layouts.products_list')
                ->with('category', $filter->getCurrentCategory())
                ->with('products', $products)
                ->with('view', !empty($request->view) ? $request->view : 'grid')
                ->with('is_search', !empty($request->search_text))
                ->render(),
            'filters' => view('public.layouts.filters')
                ->with('filter', empty($request->search_text) ? $attributes : null)
                ->render(),
            'mob_filters' => view('public.layouts.mob_filters')
                ->with('filter', empty($request->search_text) ? $attributes : null)
                ->with('selected_filters', empty($request->search_text) ? $filter->getSelectedFilters() : null)
                ->render(),
            'checked' => view('public.layouts.selected_filters')
                ->with('selected_filters', $filter->getSelectedFilters())
                ->render(),
            'pagination' => view('public.layouts.pagination')
                ->with('paginator', $products)
                ->render(),
            'link' => $filter->getCurrentLink($request->order, $request->view),
            'count' => trans_choice('app.products_found', $products->total(), [':count' => $products->total()], app()->getLocale()),
            'name' => $name,
        ]);
    }
}
