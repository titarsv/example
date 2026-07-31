<?php

namespace App\Http\Controllers;

use mycryptocheckout\api\v2\Api as MycryptocheckoutApi;
use mycryptocheckout\api\v2\Payment as MycryptocheckoutPayment;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\MyCryptoCheckout\LaravelAPI as MyCryptoCheckout;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    public function index(Request $request, $method = ''){
        if(empty($request->user_id)){
            return response()->json(['status' => 'error', 'message' => trans('locale.api.user_id_required')]);
        }else{
            $user = User::find($request->user_id);
            if(empty($user)){
                return response()->json(['status' => 'error', 'message' => trans('locale.api.user_not_found')]);
            }else{
                $hash = md5(md5($user->password).str_replace(['?hash='.$request->hash, '&hash='.$request->hash], ['?', ''], $_SERVER['REQUEST_URI']));
                if($hash != $request->hash && $method != 'test_hash'){
                    return response()->json([
                        'status' => 'error',
                        'message' => trans('locale.api.invalid_hash', ['url' => env('APP_URL').'/api/test_hash'])
                    ]);
                }
            }
        }

        if(!empty($method) && method_exists($this, $method)){
            return $this->{$method}($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => trans('locale.api.method_not_specified'),
            'methods' => [
                'products_list' => trans('locale.api_methods.products_list'),
                'product' => trans('locale.api_methods.product'),
                'update_product' => trans('locale.api_methods.update_product'),
                'orders_list' => trans('locale.api_methods.orders_list'),
                'update_order' => trans('locale.api_methods.update_order'),
                'categories_list' => trans('locale.api_methods.categories_list'),
                'category' => trans('locale.api_methods.category'),
                'attributes_list' => trans('locale.api_methods.attributes_list'),
                'mcc_callback' => trans('locale.api_methods.mcc_callback'),
            ]
        ]);
    }

    /**
     * Тестирование хеширования данных
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test_hash($request){
        if(empty($request->user_id)){
            return response()->json(['status' => 'error', 'message' => trans('locale.api.user_id_required')]);
        }else{
            $user = User::find($request->user_id);
            if(empty($user)){
                return response()->json(['status' => 'error', 'message' => trans('locale.api.user_not_found')]);
            }else{
                $string = str_replace(['?hash='.$request->hash, '&hash='.$request->hash], ['?', ''], $_SERVER['REQUEST_URI']);
                $hash = md5(md5($user->password).$string);
                if($hash != $request->hash){
                    return response()->json([
                        'status' => 'error',
                        'message' => trans('locale.api.invalid_hash_explanation', [
                            'hash' => $hash,
                            'string' => $string
                        ])
                    ]);
                }else{
                    return response()->json([
                        'status' => 'success',
                        'message' => trans('locale.api.hash_validation_success')
                    ]);
                }
            }
        }
    }

    /**
     * Список товаров
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function products_list(){
        $products = Product::select('id', 'sku', 'name', 'price', 'stock')->get()->toArray();

        return response()->json(['status' => 'success', 'products' => $products]);
    }

    /**
     * Информация о товаре
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function product($request){
        if(empty($request->id)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.product_id_required'),
                'params_list' => [
                    'id' => trans('locale.api.product_id_param')
                ]
            ]);
        }

        $product = Product::find($request->id);

        if(empty($product)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.product_not_found')
            ]);
        }

        return response()->json([
            'status' => 'success',
            'product' => $product->toArray()
        ]);
    }

    /**
     * Обновление товара
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_product($request){
        if(empty($request->id)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.product_id_required'),
                'params_list' => [
                    'id' => trans('locale.api.product_id_param'),
                    'stock' => trans('locale.api.stock_param'),
                    'price' => trans('locale.api.price_param'),
                    'old_price' => trans('locale.api.old_price_param')
                ]
            ]);
        }

        $product = Product::find($request->id);

        if(empty($product)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.product_not_found')
            ]);
        }

        $data = [];
        if(isset($request->stock)){
            $data['stock'] = $request->stock;
        }
        if(isset($request->price) && is_numeric($request->price)){
            $data['price'] = $request->price;
        }
        if(isset($request->old_price) && is_numeric($request->old_price)){
            $data['old_price'] = $request->old_price;
        }

        $product->update($data);

        return response()->json([
            'status' => 'success',
            'message' => trans('locale.api.product_updated'),
            'product' => $product->toArray()
        ]);
    }

    /**
     * Список заказов
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function orders_list(){
        $orders = Order::all()->toArray();

        foreach ($orders as $id => $order){
            $orders[$id]['products'] = json_decode($order['products']);
            $orders[$id]['user_info'] = json_decode($order['user_info']);
            $orders[$id]['delivery'] = json_decode($order['delivery']);
        }

        return response()->json(['status' => 'success', 'products' => $orders]);
    }

    /**
     * Обновление заказа
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_order($request){
        if(empty($request->id)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.order_id_required'),
                'params_list' => [
                    'id' => trans('locale.api.order_id_param'),
                    'status_id' => trans('locale.api.status_id_param'),
                    'ttn' => trans('locale.api.ttn_param')
                ]
            ]);
        }

        $order = Order::find($request->id);

        if(empty($order)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.order_not_found')
            ]);
        }

        $data = [];
        if(isset($request->status_id)){
            $data['status_id'] = (int)$request->status_id;
        }
        if(isset($request->ttn)){
            $delivery = json_decode($order->delivery, true);
            if(!is_array($delivery['info']))
                $delivery['info'] = [];
            $delivery['info']['ttn'] = $request->ttn;
            $data['delivery'] = json_encode($delivery);
        }

        $order->update($data);
        $order = $order->fresh();

        $order->products = json_decode($order->products);
        $order->user_info = json_decode($order->user_info);
        $order->delivery = json_decode($order->delivery);

        return response()->json([
            'status' => 'success',
            'message' => trans('locale.api.order_updated'),
            'order' => $order->toArray()
        ]);
    }

    /**
     * Список категорий
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories_list(){
        $categories = Category::select('id', 'name', 'url_alias', 'parent_id')->get()->toArray();

        return response()->json(['status' => 'success', 'categories' => $categories]);
    }

    /**
     * Информация о категории
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function category($request){
        if(empty($request->id)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.category_id_required'),
                'params_list' => [
                    'id' => trans('locale.api.category_id_param')
                ]
            ]);
        }

        $category = Category::find($request->id);

        if(empty($category)){
            return response()->json([
                'status' => 'error',
                'message' => trans('locale.api.category_not_found')
            ]);
        }

        return response()->json([
            'status' => 'success',
            'category' => $category->toArray()
        ]);
    }

    /**
     * Список атрибутов
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function attributes_list(){
        $attributes = Attribute::select('id', 'name', 'slug')->with('values')->get()->toArray();

        return response()->json(['status' => 'success', 'attributes' => $attributes]);
    }

    public function mcc_callback(Request $request)
    {
        $jsonToSend = json_encode($request->all());
        Log::info("MCC API Request to: " . $jsonToSend);

        $mcc = new MyCryptoCheckout();
        // Этот метод сам прочитает php://input, проверит подпись и обновит статус платежа
        return $mcc->maybe_process_messages();
    }

    public function btcpay_callback(Request $request)
    {
        // 1. Проверка валидности
        if (!$this->isWebhookValid($request)) {
            Log::warning('BTCPay Webhook: Invalid signature or spoof attempt!', [
                'ip' => $request->ip(),
                'payload' => $request->getContent() // Полезно увидеть, ЧТО именно не прошло проверку
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->all();
        $type = $data['type'] ?? 'unknown';

        try {
            // 2. Логика обработки конкретного события
            switch ($type) {
                case 'InvoiceSettled':
                    $orderId = $data['metadata']['orderId'] ?? null;

                    if (!$orderId) {
                        Log::error('BTCPay Webhook: OrderId missing in metadata', ['metadata' => $data['metadata'] ?? 'no metadata']);
                        break;
                    }

                    $order = Order::find($orderId);

                    if (!$order) {
                        Log::error("BTCPay Webhook: Order #$orderId not found in database");
                        break;
                    }

                    $order->payment_status = 1;
                    $order->status_id = $order->status_id < 3 ? 3 : $order->status_id;
                    $order->save();

                    break;

                case 'InvoiceInvalid':
                case 'InvoiceExpired':
                    // Логика отмены...
                    break;

                default:
                    break;
            }
        } catch (\Exception $e) {
            // Логируем любые системные ошибки (ошибки БД, опечатки и т.д.)
            Log::error('BTCPay Webhook: Critical Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response('Internal Error', 500);
        }

        return response('Webhook Processed', 200);
    }

    /**
     * Проверка подлинности вебхука от BTCPay Server
     * * @param \Illuminate\Http\Request $request
     * @return bool
     */
    private function isWebhookValid(Request $request): bool
    {
        $payload = $request->getContent(); // Получаем сырое тело запроса (JSON)
        $headerSignature = $request->header('btcpay-sig'); // Достаем заголовок с подписью
        $secret = config('services.btcpay.webhook_secret'); // Ваш секрет из .env

        if (empty($headerSignature) || empty($secret)) {
            return false;
        }

        // BTCPay присылает подпись в формате: sha256=хэш_строка
        // Нам нужно создать такой же хэш локально
        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        // Безопасное сравнение строк
        return hash_equals('sha256=' . $expectedSignature, $headerSignature);
    }
}
