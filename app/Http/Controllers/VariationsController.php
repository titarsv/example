<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Variation;
use App\Models\Product;

class VariationsController extends Controller
{
    /**
     * Обновление вариаций товара
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateAction(Request $request, $id){
        $product = Product::find($id);

        if(empty($product)){
            return response()->json([
                'result' => 'error',
                'message' => trans('locale.variations.product_not_found')
            ]);
        }

        $current_variations = $product->variations;
        $remove = $current_variations->pluck(['id'])->toArray();

        foreach($request->variations as $data){
            // Подготовка тела вариации
            $current_time = time();
            $sale_from = empty($data['sale_from']) ? null : strtotime($data['sale_from']);
            $sale_to = empty($data['sale_to']) ? null : strtotime($data['sale_to']);
            if(!empty($data['sale']) && !empty($data['sale_price']) && (empty($sale_from) || $sale_from <= $current_time) && (empty($sale_to) || $sale_to >= $current_time)){
                $price = $data['sale_price'];
            }else{
                $price = $data['original_price'];
            }

            $info = [
                'product_id' => $product->id,
                'file_id' => $data['image'],
                'stock' => isset($data['stock']) ? $data['stock'] : 0,
                'price' => $price,
                'original_price' => isset($data['original_price']) ? $data['original_price'] : 0,
                'sale_price' => $data['sale_price'],
                'sale' => isset($data['sale']) ? (bool)$data['sale'][0] : false,
                'sale_from' => empty($sale_from) ? null : date('Y-m-d', $sale_from),
                'sale_to' => empty($sale_to) ? null : date('Y-m-d', $sale_to)
            ];

            if(!empty($data['id'])){
                $variation = Variation::updateOrCreate(['id' =>  $data['id']], $info);
                unset($remove[array_search($data['id'], $remove)]);
            }else{
                $variation = Variation::create($info);
            }

            // Добавление атрибутов
            $attributes = [];
            if(!empty($data['attributes'])){
                foreach($data['attributes'] as $attribute){
                    $attributes[] = $attribute['values'];
                }
            }
            $variation->attribute_values()->sync(array_unique($attributes));
        }

        // Удаление вариаций
        foreach($remove as $id){
            $v = new Variation();
            $v->find($id)->update(['product_id' => null]);
        }

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.variations.updated_successfully')
        ]);
    }
}
