<?php

namespace App\Http\Controllers;

use App\Models\User;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Coupon;
use App\Models\Action;
use App;

class CouponsController extends Controller
{
    private $messages = [];

    function __construct()
    {
        $this->messages = [
            'code.required' => trans('validation.required', ['attribute' => trans('locale.Code')]),
            'price.required_without' => trans('validation.required', ['attribute' => trans('locale.Price')]),
            'percent.required_without' => trans('validation.required', ['attribute' => trans('locale.Percent')]),
        ];
    }

    /**
     * Список купонов в админпанели
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction(){
        return view('admin.products.promocodes.index')
            ->with([
                'breadcrumbs' => [
                    ['link' => '/admin', 'name' => trans('locale.Home')],
                    ['name' => trans('locale.Promo codes')]
                ],
                'localization' => json_encode(['datatable' => trans('datatable')]
            )]);
    }

    /**
     * Фильтр купонов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }

        $query = Coupon::select('coupons.*');

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'], $order['dir']);
            }
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $coupons = $query->get();

        $data = [];
        foreach($coupons as $coupon){
            $actions = [];
            if($user->hasAccess(['coupons.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/products/promocodes/edit/'.$coupon->id)
                ];
            }
            if($user->hasAccess(['coupons.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $coupon->id,
                    'name' => $coupon->name
                ];
            }

            $data[] = [
                'name' => $coupon->name,
                'code' => $coupon->code,
                'sale' => !empty($coupon->price) ? $coupon->price.' £' : (!empty($coupon->percent) ? $coupon->percent.'%' : ''),
                'to' => !empty($coupon->to) ? $coupon->to : '',
                'disposable' => ['id' => $coupon->id, 'status' => !(bool)$coupon->disposable],
                'used' => ['id' => $coupon->id, 'status' => (bool)$coupon->used],
                'status' => ['id' => $coupon->id, 'status' => (bool)$coupon->status],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Coupon::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    public function adminCreateAction(Request $request){
        if(!empty($request->prev)){
            $prev = $request->prev;
        }else{
            $prev = app('url')->previous();
        }

        return view('admin.products.promocodes.create')
            ->with('prev', $prev)
            ->with([
            'breadcrumbs' => [
                ['link' => '/admin', 'name' => trans('locale.Home')],
                ['link' => '/admin/products/promocodes', 'name' => trans('locale.Promo codes')],
                ['name' => trans('locale.Create promocode')]
            ],
            'localization' => json_encode(['datatable' => trans('datatable')]
            )]);
    }

    /**
     * Генерация уникального кода
     *
     * @return string
     */
    public function adminGenerateCodeAction(){
        $code = Str::random();
        $i = 0;
        while(!empty(Coupon::where('code', $code)->first())){
            $code = Str::random();
            $i++;
            if($i == 100){
                break;
            }
        }

        return $code;
    }

    /**
     * Создание купона
     *
     * @param Request $request
     * @param Coupon $coupons
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStoreAction(Request $request, Coupon $coupons){
        $rules = [
            'code' => 'required|unique:coupons,code',
            'price' => 'required_without:percent',
            'percent' => 'required_without:price',
        ];

        $messages = [
            'code.required' => trans('validation.required', ['attribute' => trans('locale.Code')]),
            'price.required_without' => trans('validation.required', ['attribute' => trans('locale.Discount size')]),
            'percent.required_without' => trans('validation.required', ['attribute' => trans('locale.Discount size')]),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails()){
            $errors = array_map(function($error) {
                return is_array($error) ? $error[0] : $error;
            }, $validator->errors()->all());
            return response()->json(['result' => 'error', 'errors' => $errors]);
        }

        $data = $request->only(['name', 'code', 'price', 'percent', 'disposable', 'min_total', 'without_sale']);
        $data['disposable'] = empty($request->disposable);
        $data['from'] = !empty($request->from) ? date('Y-m-d', strtotime($request->from)) : null;
        $data['to'] =  !empty($request->to) ? date('Y-m-d', strtotime($request->to)) : null;
        if(in_array($request->scope, ['products', 'categories'])){
            $data['scope'] = json_encode(['type' => $request->scope, 'ids' => explode(',', $request->{'scope_'.$request->scope})]);
        }else{
            $data['scope'] = null;
        }

        if(!isset($data['price']))
            $data['price'] = null;
        if(!isset($data['percent']))
            $data['percent'] = null;
        if(!isset($data['min_total']))
            $data['min_total'] = 0;
        if(!isset($data['without_sale']))
            $data['without_sale'] = 0;

        if(!empty($data['percent']) && $data['percent'] > 100){
            $data['percent'] = 100;
        }

        $coupons->fill($data);
        $coupons->save();

        Action::createEntity($coupons);

        return response()->json(['result' => 'success', 'redirect' => '/admin/products/promocodes/edit/'.$coupons->id]);
    }

    /**
     * Страница изменения купона
     *
     * @param Request $request
     * @param $id
     *
     * @return mixed
     */
    public function adminEditAction(Request $request, $id){
        if(!empty($request->prev)){
            $prev = $request->prev;
        }else{
            $prev = app('url')->previous();
        }

        return view('admin.products.promocodes.edit')
            ->with('coupon', Coupon::find($id))
            ->with('prev', $prev)
            ->with([
                'breadcrumbs' => [
                    ['link' => '/admin', 'name' => trans('locale.Home')],
                    ['link' => '/admin/products/promocodes', 'name' => trans('locale.Promo codes')],
                    ['name' => trans('locale.Edit promocode')]
                ],
                'localization' => json_encode(['datatable' => trans('datatable')]
            )]);
    }

    /**
     * Обновление купона
     *
     * @param Request $request
     * @param $id
     * @param Coupon $coupons
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateAction(Request $request, $id, Coupon $coupons){
        $rules = [
            'code' => 'required|unique:coupons,code,'.$id,
            'price' => 'required_without:percent',
            'percent' => 'required_without:price',
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'message' => trans('locale.messages.save_failed'),
                'errors' => $validator
            ], 200);
        }

        $coupon = $coupons->find($id);

        $coupon_data = $coupon->fullData();

        $data = $request->only(['name', 'code', 'price', 'percent', 'disposable', 'min_total', 'without_sale']);
        $data['disposable'] = empty($request->disposable);

        if(!empty($request->from))
            $data['from'] = date('Y-m-d', strtotime($request->from));
        else
            $data['from'] = null;

        if(!empty($request->to))
            $data['to'] = date('Y-m-d', strtotime($request->to));
        else
            $data['to'] = null;

        if(in_array($request->scope, ['products', 'categories'])){
            $data['scope'] = json_encode(['type' => $request->scope, 'ids' => explode(',', $request->{'scope_'.$request->scope})]);
        }else{
            $data['scope'] = null;
        }

        if(!isset($data['price']))
            $data['price'] = null;
        if(!isset($data['percent']))
            $data['percent'] = null;
        if(!isset($data['min_total']))
            $data['min_total'] = 0;
        if(!isset($data['without_sale']))
            $data['without_sale'] = 0;

        if(!empty($data['percent']) && $data['percent'] > 100){
            $data['percent'] = 100;
        }

        $coupon->fill($data);
        $coupon->save();

        Action::updateEntity($coupons->find($id), $coupon_data);

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.messages.coupon_updated', ['name' => $coupon->name])
        ], 200);
    }

    /**
     * Обновление статуса
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStatusAction(Request $request, $id){
        $coupon = Coupon::find($id);

        if(empty($coupon)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Coupon not found')], 200);
        }
        $coupon_data = $coupon->fullData();
        $coupon->status = (int)$request->status;
        $coupon->save();

        Action::updateEntity($coupon->find($id), $coupon_data);

        if($coupon->status)
            return response()->json(['result' => 'success', 'message' => trans('locale.Coupon enabled')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.Coupon disabled')], 200);
    }

    /**
     * Удаление купона
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDeleteAction($id){
        $coupon = Coupon::find($id);
        $name = $coupon->name;
        $code = $coupon->code;

        Action::deleteEntity($coupon);

        $coupon->delete();

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.messages.entity_deleted', ['name' => $name, 'code' => $code])
        ], 200);
    }
}
