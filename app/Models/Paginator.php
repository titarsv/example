<?php

namespace App\Models;

class Paginator
{
    public function url($url, $page){
        $parts = explode('?', $url);
        $search = preg_replace('/&$/', '', str_replace('page='.$page, '', $parts[1]));
        if($page > 1)
            return preg_replace('/\/page-\d+/i', '', $parts[0]).'/page-'.$page.(empty($search) ? '' : '?'.$search);
        else
            return  preg_replace('/\/page-\d+/i', '', $parts[0]).(empty($search) ? '' : '?'.$search);
    }
}
