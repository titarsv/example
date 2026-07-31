<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Portfolio;

class MainController extends Controller
{
	/**
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
	 */
    public function route(){
        $parts = explode('/', str_replace('https://', '', url()->current()));
        $part = end($parts);

        if(in_array(substr($part, -4), ['.jpg', '.png', 'jpeg', '.webp'])){
            return redirect('/uploads/no_image.jpg', 301);
        }

        return abort(404);
    }
}
