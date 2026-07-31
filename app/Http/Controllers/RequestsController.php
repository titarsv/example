<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Http\Request;
use App\Models\Redirect;
use App\Models\Request as Inquiry;
use App\Models\User;

class RequestsController extends Controller
{
    /**
     * Список редиректов
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction(){
        return view('admin.requests.index')
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
        $query = Inquiry::select('requests.*');

        if($request->has('search.value')){
            $text = $request->search['value'];
            $query->where(function($query) use($text){
                $query->orWhere('email', 'like', '%'.$text.'%')
                    ->orWhere('name', 'like', '%'.$text.'%');
            });
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $requests = [];
        foreach($query->get() as $re){
            $actions = [[
                'type' => 'delete',
                'id' => $re->id
            ]];

            $requests[] = [
                'id' => $re->id,
                'form' => $re->form,
                'name' => $re->name,
                'email' => $re->email,
                'comment' => $re->comment,
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Inquiry::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $requests
        ]);
    }

    public function adminDestroyAction($id){
        $record = Inquiry::find($id);
        $record->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.messages.request_deleted', ['id' => $id])], 200);
    }

}
