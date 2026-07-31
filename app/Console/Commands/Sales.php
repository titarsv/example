<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Variation;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class Sales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sales';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $date = date('Y-m-d H:i:s');

        // ID активных акций
//        $active_sales = Sale::where('status', 1)
//            ->where('show_from', '<=', $date)
//            ->where('show_to', '>=', $date)
//            ->get()
//            ->pluck('id')
//            ->toArray();
//
//        // ID не активных акций
//        $not_active_sales = Sale::where(function($query) use($date){
//            $query->where('status', 1)
//                ->where(function($query) use($date){
//                    $query->where('show_from', '>', $date)
//                        ->orWhere('show_to', '<', $date);
//                })
//                ->orWhere('status', 0);
//            })
//            ->get()
//            ->pluck('id')
//            ->toArray();
//
//        $active_ids = DB::table('sale_products')->whereIn('sale_id', $active_sales)->get()->pluck('product_id')->toArray();
//        $not_active_ids = DB::table('sale_products')->whereIn('sale_id', $not_active_sales)->get()->pluck('product_id')->toArray();
//
//        // Возвращаем оригинальную цену
//        Product::where(function($query) use($date, $not_active_ids){
//                // Акционная цена назначена, но ещё/уже не действует
//                $query->where(function($query) use($date){
//                    $query->where('sale', 1)->where(function($query) use($date){
//                            $query->where('sale_from', '>', $date)
//                                ->orWhere('sale_to', '<', $date);
//                    });
//                })
//                // Акционная цена не назначена
//                ->orWhere('sale', 0)
//                ->orWhereIn('id', $not_active_ids);
//            })
//            ->whereNotIn('id', $active_ids)
//            ->where('price', '!=', DB::raw('original_price'))
//            ->update(['price' => DB::raw('original_price')]);
//
//        // Устанавливаем групповую акционную цену
//        Product::select(['products.id', 'products.price', 'sale_products.sale_price'])
//            ->join('sale_products', 'sale_products.product_id', '=', 'products.id')
//            ->whereIn('sale_products.sale_id', $active_sales)
//            ->where('products.price', '!=', DB::raw('sale_products.sale_price'))
//            ->update(['products.price' => DB::raw('sale_products.sale_price')]);

        // Устанавливаем персональную акционную цену
//        $sale = Sale::find(1);
//        $sale->products()->detach(
//            Product::where(function($query) use($date){
//                    $query->where('sale', 0)
//                        ->orWhere('sale_from', '>', $date)
//                        ->orWhere('sale_to', '<', $date);
//                })
//                ->where('price', '=', DB::raw('sale_price'))
//                ->get()
//                ->pluck('id')
//                ->toArray()
//        );

        if(env('REDIS_CACHE')){
            foreach(Product::where(function($query) use($date){
                $query->where('sale', 0)
                    ->orWhere('sale_from', '>', $date)
                    ->orWhere('sale_to', '<', $date);
            })->where('price', '=', DB::raw('sale_price'))->get() as $product){
                Redis::command('setbit', ["product_sale", $product->id, 0]);
            }

            foreach(Product::where('sale', 1)
                        ->where('sale_from', '<=', $date)
                        ->where('sale_to', '>=', $date)
                        ->where('price', '!=', DB::raw('sale_price'))->get() as $product){
                Redis::command('setbit', ["product_sale", $product->id, 1]);
            }
        }

        Product::where(function($query) use($date){
                $query->where('sale', 0)
                    ->orWhere('sale_from', '>', $date)
                    ->orWhere('sale_to', '<', $date);
            })
            ->where('price', '=', DB::raw('sale_price'))
            ->update(['price' => DB::raw('original_price')]);

        Variation::leftJoin('products', 'products.id', '=', 'variations.product_id')
            ->where(function($query) use($date){
                $query->where('products.sale', 0)
                    ->orWhere('products.sale_from', '>', $date)
                    ->orWhere('products.sale_to', '<', $date);
            })
            ->where('variations.price', '=', DB::raw('variations.sale_price'))
            ->update(['variations.price' => DB::raw('variations.original_price')]);

        Product::where('sale', 1)
            ->where(function($query) use($date){
                $query->whereNull('sale_from')
                    ->orWhere('sale_from', '<=', $date);
            })
            ->where(function($query) use($date){
                $query->whereNull('sale_to')
                    ->orWhere('sale_to', '>=', $date);
            })
            ->where('price', '!=', DB::raw('sale_price'))
            ->update(['price' => DB::raw('sale_price')]);

        Variation::leftJoin('products', 'products.id', '=', 'variations.product_id')
            ->where('products.sale', 1)
            ->where('products.sale_from', '<=', $date)
            ->where('products.sale_to', '>=', $date)
            ->where('variations.price', '!=', DB::raw('variations.sale_price'))
            ->update(['variations.price' => DB::raw('variations.sale_price')]);

        if(env('REDIS_CACHE')){
            foreach(Product::all() as $product){
                Redis::command('zadd', ['prices', $product->price * 100, $product->id]);
            }

//            $products = $sale->products()->select('product_id')->get()->pluck('product_id')->unique()->sort()->values()->all();
//            Redis::command('del', ['sale_1']);
//            foreach(array_chunk($products, 100) as $data){
//                $command = ['sale_1'];
//
//                foreach($data as $product_id){
//                    $command[] = 'SET';
//                    $command[] = 'u1';
//                    $command[] = $product_id;
//                    $command[] = 1;
//                }
//
//                Redis::command('bitfield', $command);
//            }
        }
    }
}
