<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Page;
use App\Models\Blog;
use App\Models\ContentCategory;
use Illuminate\Pagination\Paginator;

class SitemapController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $links = [
            base_url('/sitemap/categories') => trans('locale.sitemap.categories'),
            base_url('/sitemap/products') => trans('locale.sitemap.products'),
            base_url('/sitemap/pages') => trans('locale.sitemap.pages'),
            base_url('/sitemap/blog') => trans('locale.sitemap.blog')
        ];

        return view('public.sitemap')
            ->with('links', $links)
            ->with('title', trans('locale.sitemap.title'));
    }

    public function categories($page = 'page-1'){
    	$p = str_replace('page-', '', $page);
	    Paginator::currentPageResolver(function () use ($p) {
		    return $p;
	    });
	    $categories = Category::where('status', 1)->paginate(100);
	    $links = [];
	    foreach ($categories as $category){
		    $links[$category->link()] = $category->name;
	    }
        return view('public.sitemap')
            ->with('links', $links)
            ->with('title', trans('locale.sitemap.category_title'))
            ->with('pagination', $categories);
    }

    public function products($page = 1){
	    $p = str_replace('page-', '', $page);
	    Paginator::currentPageResolver(function () use ($p) {
		    return $p;
	    });
	    $products = Product::where('visible', 1)->paginate(100);
	    $links = [];
	    foreach ($products as $product){
		    $links[$product->link()] = $product->name;
	    }
        return view('public.sitemap')
            ->with('links', $links)
            ->with('title', trans('locale.sitemap.product_title'))
            ->with('pagination', $products);
    }

    public function pages($page = 1){
	    $p = str_replace('page-', '', $page);
	    Paginator::currentPageResolver(function () use ($p) {
		    return $p;
	    });
	    $pages = Page::where('status', 1)->paginate(100);
	    $links = [];
	    foreach ($pages as $page){
		    $links[$page->link()] = $page->name;
	    }
        return view('public.sitemap')
            ->with('links', $links)
            ->with('title', trans('locale.sitemap.page_title'))
            ->with('pagination', $pages);
    }

    public function blog($page = 1){
	    $p = str_replace('page-', '', $page);
	    Paginator::currentPageResolver(function () use ($p) {
		    return $p;
	    });
	    $news = Blog::where('status', 1)->paginate(100);
	    $links = [];
	    foreach ($news as $article){
		    $links[$article->link()] = $article->name;
	    }
        return view('public.sitemap')
            ->with('links', $links)
            ->with('title', trans('locale.sitemap.blog_title'))
            ->with('pagination', $news);
    }
}
