<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\ModuleBestsellers;
use App\Models\ModuleLatest;
use App\Models\News;
use App\Models\Page;
use App\Models\Product;
use App\Models\Sale;
use App\Services\MyCryptoCheckout\LaravelAPI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Redis;

class AjaxController extends Controller
{
	public function front(){

	}

	public function back(){

	}

    public function index(Request $request)
    {
        if($request->action == 'query-attachments'){
            return $this->queryAttachments($request->toArray());
        }elseif($request->action == 'image-editor'){
            return $this->imageEditor($request->toArray());
        }elseif($request->action == 'imgedit-preview'){
            return $this->imgeditPreview($request->toArray());
        }elseif($request->action == 'delete-post'){
	        $id = intval($request->id);
            return $this->deletePost($id);
        }elseif($request->action == 'save-attachment'){
            $id = intval($request->id);
            if(isset($request->changes['status']) && $request->changes['status'] == 'trash'){
                return $this->deletePost($id);
            }else{
                return $this->updatePost($request->toArray());
            }
        }elseif($request->action == 'send-attachment-to-editor'){
	        return $this->imageHtml($request->attachment['id']);
        }elseif($request->action == 'filter'){
            return $this->filter($request->toArray());
        }elseif($request->action == 'add-menu-item'){
            return $this->addMenuItem($request->toArray());
        }elseif($request->action == 'menu-get-metabox'){
            return $this->menuGetMetabox($request->toArray());
        }elseif($request->action == 'menu-quick-search'){
            return $this->menuQuickSearch($request->toArray());
        }

        return response()->json(['success' => false]);
    }

    public function queryAttachments($request){
        $files = new File();
        $response = ['success' => true];
        $orderby = 'date';
        $order = 'DESC';
        $posts_per_page = 40;
        $paged = 1;
        if(!empty($request['query'])){
            if(!empty($request['query']['orderby'])){
                $orderby = $request['query']['orderby'];
            }
            if(!empty($request['query']['order'])){
                $order = $request['query']['order'];
            }
            if(!empty($request['query']['posts_per_page'])){
                $posts_per_page = $request['query']['posts_per_page'];
            }
            if(!empty($request['query']['paged'])){
                $paged = $request['query']['paged'];
            }
            if(!empty($request['query']['post_mime_type'])){
                $mime_type = $request['query']['post_mime_type'];
            }
        }
        if($orderby == 'date'){
            $orderby = 'created_at';
        }
        $offset = $posts_per_page * ($paged - 1);

        $query = $files->orderBy($orderby, $order)->offset($offset)->take($posts_per_page);

        if(!empty($request['query']['year']) && $request['query']['year'] != 'false' && !empty($request['query']['monthnum']) && $request['query']['monthnum'] != 'false'){
            $monthnum = $request['query']['monthnum'];
            $timfrom = $request['query']['year'].'-'.($monthnum < 10 ? '0'.$monthnum : $monthnum).'-01 00:00:00';
            if($request['query']['monthnum'] == 12){
                $timto = ($request['query']['year']+1).'-01-01 00:00:00';
            }else{
                $monthnum++;
                $timto = $request['query']['year'].'-'.($monthnum < 10 ? '0'.$monthnum : $monthnum).'-01 00:00:00';
            }
            $query->whereBetween('created_at',[$timfrom,$timto]);
        }

	    if(!empty($mime_type)){
		    $types = [''];
		    foreach((array)$mime_type as $type){
		    	$types = array_merge($types, [$type]);
		    }
		    $query->whereIn('type', $types);
	    }

        if(!empty($request['query']['s'])){
            $query->where('title', 'like', '%'.$request['query']['s'].'%');
        }

        if(!empty($request['query']['post_status']) && $request['query']['post_status'] == 'trash'){
	        $query->onlyTrashed();
        }

        $files = $query->get();
        foreach($files as $file){
            if(empty($file->data)){
                $file->updateData();
            }
            if(!empty($request['query']['post_status']) && $request['query']['post_status'] == 'trash'){
                $data = $file->data;
                $data['status'] = 'trashed';
                $file->data = $data;
            }
        }
        $data = $files->pluck('data')->toArray();

        $response['data'] = $data;
        return response()->json($response);
    }

    public function imageEditor($request){
        if(!empty($request['postid'])){
            $image = File::where(['id' => $request['postid'], 'type' => 'image']);
        }

        return view('admin.media.editor')
            ->with('image', !empty($image) ? $image : null)
            ->with('nonce', $request['_ajax_nonce']);
    }

    public function imgeditPreview($request) {
        $post_id = intval($request['postid']);
        if ( empty($post_id) )
            return '-1';

        $image = File::find($post_id);
        $path = public_path() . '/uploads/' . $image->href;
        $data = $image->fileData();
        $im = $image->imagecreatefromfile($path);

        switch ( $data['mime'] ) {
            case 'image/jpeg':
                header( 'Content-Type: image/jpeg' );
                return imagejpeg( $im, null, 90 );
            case 'image/png':
                header( 'Content-Type: image/png' );
                return imagepng( $im );
            case 'image/gif':
                header( 'Content-Type: image/gif' );
                return imagegif( $im );
            default:
                return '';
        }
    }

    public function deletePost($id){
        if(empty($id))
            return '-1';
        $file = File::withTrashed()->find($id);

        if($file !== null) {
        	if($file->trashed()){
		        $file_path = public_path($file->path);
		        if(is_file($file_path)){
			        unlink($file_path);
		        }
		        $file->forceDelete();
	        }else{
		        DB::transaction(function() use($file){
			        foreach ($file->images as $image ) {
				        $image->delete();
			        }
			        $file->delete();
		        });
	        }

            return 1;
        } else {
            return 0;
        }
    }

    public function updatePost($request){
        $file = File::find($request['id']);
        if(!empty($file)){
            $data = $file->data;
            // Заголовок
            if(isset($request['changes']['title'])){
                $file->title = $request['changes']['title'];
                $data['title'] = $request['changes']['title'];
                $file->data = $data;
            }
            // Подпись
            if(isset($request['changes']['caption'])){
                $data['caption'] = $request['changes']['caption'];
                $file->data = $data;
            }
            // Атрибут alt
            if(isset($request['changes']['alt'])){
                $data['alt'] = $request['changes']['alt'];
                $file->data = $data;
            }
            // Описание
            if(isset($request['changes']['description'])){
                $data['description'] = $request['changes']['description'];
                $file->data = $data;
            }
            $file->save();
        }
        return response()->json(['success' => true]);
    }

	/**
	 * Вставка изображения в эдитор
	 *
	 * @param $id
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function imageHtml($id){
		$file = File::find($id);

		return response()->json(['success' => true, 'data' => $file->webp('full', ['alt' => $file->alt, 'title' => $file->title], 'editor')]);
	}

    /**
     * Добавление элемента в меню
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMenuItem($request){
        $items = [];
        $locales = config()->get('app.locales');
        $last_item = MenuItem::where('menu_id', $request['menu'])->orderBy('position', 'desc')->first();

        foreach($request['menu-item'] as $item){
            $id = MenuItem::insertGetId([
                'menu_id' => $request['menu'],
                'parent_id' => isset($item['menu-item-parent-id']) ? $item['menu-item-parent-id'] : 0,
                'type' => isset($item['menu-item-object']) ? $item['menu-item-object'] : $item['menu-item-type'],
                'value' => $item['menu-item-type'] == 'custom' ? $item['menu-item-url'] : ($item['menu-item-type'] == 'group' ? '' : $item['menu-item-object-id']),
                'class' => isset($item['menu-item-classes']) ? $item['menu-item-classes'] : '',
                'blank' => !empty($item['menu-item-target']),
                'xfn' => isset($item['menu-item-xfn']) ? $item['menu-item-xfn'] : null,
                'position' => !empty($last_item) ? $last_item->position + 1 : 1,
                'image_id' => null
            ]);

            $menu_item = MenuItem::find($id);

            $rq = new Request();
            if(in_array($menu_item->type, ['page', 'category'])){
                $parent = $menu_item->type == 'page' ? Page::find($menu_item->value) : Category::find($menu_item->value);
                if(count($locales) > 1){
                    foreach($locales as $locale){
                        $rd['name_'.$locale] = $parent->localize($locale, 'name');
                    }
                }else{
                    $rd['name'] = $parent->name;
                }
            }else{
                if(count($locales) > 1){
                    $rd['name_'.config()->get('app.locale')] = $item['menu-item-title'];
                }else{
                    $rd['name'] = $item['menu-item-title'];
                }
            }

            $rq->merge($rd);
            $menu_item->saveLocalization($rq);

            $items[] = $menu_item;
        }

        foreach($locales as $locale){
            Cache::forget('menu_'.$request['menu'].'_'.$locale);
        }

        return view('admin.menu.items')
            ->with('items', $items)
            ->with('locales_names', Config::get('app.locales_names'))
            ->with('main_lang', Config::get('app.locale'));
    }

    /**
     * Пагинация в сайдбаре настроек меню
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function menuGetMetabox($request){
        if($request['item-type'] == 'post_type' && $request['item-object'] == 'page'){
            return response()->json([ 'replace-id' => 'posttype-page',
                'markup' => view('admin.menu.pages')
                    ->with('new_pages', Page::orderBy('id', 'desc')->limit(15)->get())
                    ->with('all_pages', Page::orderBy('id', 'asc')->paginate(50, ['*'], 'pages', $request['paged']))
                    ->with('page_tab', $request['page-tab'])
                    ->with('i', 1)
                    ->render()]);
        }elseif($request['item-type'] == 'taxonomy' && $request['item-object'] == 'category'){
            return response()->json([ 'replace-id' => 'taxonomy-category',
                'markup' => view('admin.menu.categories')
                    ->with('new_categories', Category::orderBy('id', 'desc')->limit(15)->get())
                    ->with('all_categories', Category::orderBy('id', 'asc')->paginate(50, ['*'], 'pages', $request['paged']))
                    ->with('category_tab', $request['category-tab'])
                    ->with('i', 1)
                    ->render()]);
        }

        return response()->json(['success' => false]);
    }

    /**
     * Поиск в сайдбаре настроек меню
     *
     * @param $request
     * @return $this|\Illuminate\Http\JsonResponse
     */
    public function menuQuickSearch($request){
        if($request['type'] == 'quick-search-posttype-page'){
            $pages = Page::select('pages.*')
                ->leftJoin('localization', 'localization.localizable_id', '=', 'pages.id')
                ->where('localizable_type', 'Pages')
                ->where('value', 'like', '%' . $request['q'] . '%')
                ->groupBy('pages.id')
                ->get();

            return view('admin.menu.pages_list')
                ->with('items', $pages)
                ->with('i', 1);
        }elseif($request['type'] == 'quick-search-taxonomy-category'){
            $pages = Category::select('categories.*')
                ->leftJoin('localization', 'localization.localizable_id', '=', 'categories.id')
                ->where('localizable_type', 'Categories')
                ->where('value', 'like', '%' . $request['q'] . '%')
                ->groupBy('categories.id')
                ->get();

            return view('admin.menu.categories_list')
                ->with('items', $pages)
                ->with('i', 1);
        }

        return response()->json(['success' => false]);
    }

    public function adminLiveSearchAction(Request $request, Product $products){
        $search_text = $request->input('search');
        if(strpos($search_text, '%') === 0){
            $search_text = urlencode($search_text);
        }

        // Установка текущей страницы пагинации
        $results = $products->search(trim($search_text), 1, 12, false, !empty($request->input('sale_id')) ? $request->input('sale_id') : null, !empty($request->input('news_id')) ? $request->input('news_id') : null,!empty($request->input('category_id')) ? $request->input('category_id') : null);

        foreach($results as $result){
            if ($result) {
                $json[] = [
                    'product_id'   => $result->id,
                    'name'         => $result->name,
                    'sku'          => $result->sku,
                    'price'        => $result->original_price,
                    'image'        => !empty($result->image) ? $result->image->url() : '/uploads/no_image.jpg'
                ];
            }
        }

        if (!empty($json)) {
            return response()->json($json);
        } else {
            return response()->json([['empty' => 'Ничего не найдено!']]);
        }
    }

    public function adminAddSaleProduct(Request $request){
        $sale = Sale::find($request->sale_id);
        $product = Product::find($request->product_id);
        if(!empty($sale) && !empty($product)){
            if(!empty($sale->sale_percent)){
                $price = $product->original_price * (100 - $sale->sale_percent) / 100;
            }else{
                $price = $product->original_price;
            }
            $sale->products()->attach($request->product_id, ['sale_price' => $price]);

            return response()->json(['result' => 'success', 'html' => view('admin.sales.products')->with('products', $sale->productsList(1))->render()]);
        }

        return response()->json(['result' => 'error']);
    }

    public function adminRemoveSaleProduct(Request $request){
        $sale = Sale::find($request->sale_id);
        $product = Product::find($request->product_id);
        if(!empty($sale) && !empty($product)){
            if($product->sale && strtotime($product->sale_from) >= time() && strtotime($product->sale_to) >= time()){
                $product->price = $product->sale_price;
            }else{
                $product->price = $product->original_price;
            }
            $product->save();
            $sale->products()->detach($product->id);

            if(env('REDIS_CACHE')){
                Redis::command('setbit', ['sale_'.$sale->id, $product->id, 0]);
            }

            return response()->json(['result' => 'success']);
        }

        return response()->json(['result' => 'error']);
    }

    public function adminAddNewsProduct(Request $request){
        $news = News::find($request->news_id);
        $product = Product::find($request->product_id);
        if(!empty($news) && !empty($product)){
            $news->products()->attach($request->product_id);
            return response()->json(['result' => 'success', 'html' => view('admin.modules.products')->with('products', $news->productsList(1))->render()]);
        }

        return response()->json(['result' => 'error']);
    }

    public function adminRemoveNewsProduct(Request $request){
        $news = News::find($request->news_id);
        $product = Product::find($request->product_id);
        if(!empty($news) && !empty($product)){
            $news->products()->detach($product->id);
            return response()->json(['result' => 'success']);
        }

        return response()->json(['result' => 'error']);
    }

    public function adminAddLatestProduct(Request $request){
        $product = Product::find($request->product_id);
        if(!empty($product)){
            $latest = new ModuleLatest();
            $latest->insert(['product_id' => $request->product_id]);
            return response()->json(['result' => 'success', 'html' => view('admin.modules.products')->with('products', $latest->productsList(1))->render()]);
        }

        return response()->json(['result' => 'error']);
    }

    public function adminRemoveLatestProduct(Request $request){
        $product = Product::find($request->product_id);
        if(!empty($product)){
            $latest = new ModuleLatest();
            $latest->where(['product_id' => $request->product_id])->delete();
            return response()->json(['result' => 'success']);
        }

        return response()->json(['result' => 'error']);
    }

    public function adminAddBestsellerProduct(Request $request){
        $product = Product::find($request->product_id);
        if(!empty($product)){
            $bestsellers = new ModuleBestsellers();
            $bestsellers->insert(['product_id' => $request->product_id]);
            return response()->json(['result' => 'success', 'html' => view('admin.modules.products')->with('products', $bestsellers->productsList(1))->render()]);
        }

        return response()->json(['result' => 'error']);
    }

    public function adminRemoveBestsellerProduct(Request $request){
        $product = Product::find($request->product_id);
        if(!empty($product)){
            $bestsellers = new ModuleBestsellers();
            $bestsellers->where(['product_id' => $request->product_id])->delete();
            return response()->json(['result' => 'success']);
        }

        return response()->json(['result' => 'error']);
    }

    public function mccInit($request = null){
        $mcc = new LaravelAPI();

        try {
            // 1. Генерируем ключ верификации
            $retrieve_key = bin2hex(random_bytes(16));

            // 2. Сохраняем его (используя метод нашего LaravelAccount)
            $mcc->account()->set_retrieve_key($retrieve_key);

            // 3. Правильный вызов в v2 API:
            // Мы отправляем наши данные на сервер MCC
            $client_data = $mcc->account()->generate_client_account_data();

            // Указываем серверу ключ, который он должен прислать обратно для проверки
            $client_data->retrieve_key = $retrieve_key;

            // Отправляем данные аккаунта (метод send_client_account_data)
            $mcc->account()->send_client_account_data($client_data);

            return "Запрос на привязку отправлен успешно!";
        } catch (\Exception $e) {
            return "Ошибка активации: " . $e->getMessage();
        }

//        $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
//
//        // 1. Сначала нужно получить данные аккаунта (если вы впервые подключаетесь)
//        // Обычно это делается один раз.
//        try {
//            $account = $mcc->account();
//            // В MCC v2 используется механизм уведомлений.
//            // Вам нужно будет зайти в админку MCC и подтвердить домен.
//
//            return response()->json(['result' => 'success']);
//        } catch (\Exception $e) {
//            return $e->getMessage();
//        }
    }
}
