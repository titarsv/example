<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Redirect;
use App\Models\Action;
use App\Models\User;

class RedirectsController extends Controller
{
    /**
     * Список редиректов
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction(){
        return view('admin.promotion.redirects.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable')])]);
    }

    /**
     * Фильтр редиректов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }
        $query = Redirect::select('redirects.*');

        if($request->has('search.value')){
            $text = $request->search['value'];
            $query->where(function($query) use($text){
                $query->where('old_url', 'like', '%'.$text.'%')
                    ->orWhere('new_url', 'like', '%'.$text.'%');
            });
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $redirects = $query->get();

        $data = [];
        foreach($redirects as $redirect){
            $actions = [];
            if($user->hasAccess(['redirects.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/promotion/redirects/edit/'.$redirect->id)
                ];
            }
            if($user->hasAccess(['redirects.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $redirect->id,
                    'name' => $redirect->old_url
                ];
            }

            $data[] = [
                'id' => $redirect->id,
                'from' => $redirect->old_url,
                'to' => $redirect->new_url,
                'status' => $redirect->status,
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Redirect::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Создание редиректа
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStoreAction(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'old_url' => 'required|unique:redirects,old_url',
            'new_url' => 'required'
        ], [
            'old_url.required' => trans('validation.required', ['attribute' => 'URL']),
            'old_url.unique' => trans('validation.unique', ['attribute' => 'URL']),
            'new_url.required' => trans('validation.required', ['attribute' => 'новый URL'])
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $redirect = new Redirect();

        $redirect->fill($request->except('_token'));
        $redirect->save();

        Redirect::where('new_url', $request->old_url)->update(['new_url' => $request->new_url]);

        Action::createEntity($redirect);

        return response()->json(['result' => 'success', 'redirect' => '/admin/promotion/redirects/edit/'.$redirect->id]);
    }

    /**
     * Страница изменения редиректа
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminEditAction($id){
        $redirect = Redirect::find($id);

        return view('admin.promotion.redirects.edit')
            ->with('redirect', $redirect);
    }

    /**
     * Обновление редиректа
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateAction(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $redirect = Redirect::find($id);
        if(empty($redirect)){
            return response()->json(['result' => 'error', 'message' => trans('redirects.not_found')], 200);
        }

        $rules = [
            'old_url' => 'required|unique:redirects,old_url',
            'new_url' => 'required',
            'status' => 'required',
        ];
        $messages = [
            'old_url.required' => trans('validation.required', ['attribute' => 'URL']),
            'old_url.unique' => trans('validation.unique', ['attribute' => 'URL']),
            'new_url.required' => trans('validation.required', ['attribute' => 'новый URL']),
            'status.required' => trans('validation.required', ['attribute' => 'статус']),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->messages(), 'message' => trans('locale.Form validation error')], 200);
        }

        $redirect_data = $redirect->fullData();

        $redirect->fill($request->except('_token'));
        $redirect->save();

        Action::updateEntity(Redirect::find($id), $redirect_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
    }

    /**
     * Удаление редиректа
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDeleteAction($id): \Illuminate\Http\JsonResponse
    {
        $redirect = Redirect::find($id);

        if(empty($redirect)){
            return response()->json(['result' => 'error', 'message' => trans('locale.redirects.not_found')], 200);
        }

        // Сохранение действия
        Action::deleteEntity($redirect);

        $from = $redirect->old_url;

        $redirect->delete();

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.redirects.deleted', ['url' => ENV('APP_URL') . $from])
        ], 200);
    }
}
