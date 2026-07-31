<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ErrorsController extends Controller
{
    public function saveAjaxErrorAction(Request $request){
        return response()->json([
            'result' => 'warning',
            'message' => trans('locale.messages.error_notification_sent')
        ]);
    }
}
