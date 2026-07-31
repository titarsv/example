<?php // routes/breadcrumbs.php

// Note: Laravel will automatically resolve `Breadcrumbs::` without
// this import. This is nice for IDE syntax and refactoring.
use Diglactic\Breadcrumbs\Breadcrumbs;

// This import is also not required, and you could replace `BreadcrumbTrail $trail`
//  with `$trail`. This is nice for IDE type checking and completion.
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Home
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push(__('Home'), rtrim(base_url('/'), '/'));
});

// Home > Blog
Breadcrumbs::for('blog', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push(__('Blog'), base_url('/blog'));
});

// Home > Blog > Category
Breadcrumbs::for('content_category', function (BreadcrumbTrail $trail, $category) {
    $trail->parent('blog');
    $trail->push($category->name);
});

// Home > Blog > Article
Breadcrumbs::for('blog_item', function (BreadcrumbTrail $trail, $article) {
    $trail->parent('blog');
    $trail->push($article->name);
});

// Home > Page
Breadcrumbs::for('page', function(BreadcrumbTrail $trail, $page) {
    $trail->parent('home');
    if(!empty($page->parent_id)){
        $trail->push($page->parent->name, $page->parent->link());
    }
    $trail->push($page->name);
});

// Home > Login
Breadcrumbs::for('login', function(BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push(__('Login'), base_url('/login'));
});

// Home > Catalog
Breadcrumbs::for('catalog', function(BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push(__('Catalog'), base_url('/catalog'));
});

// Home > Catalog > Category
Breadcrumbs::for('categories', function($breadcrumbs, $category) {
    $breadcrumbs->parent('catalog');
    $link = '';
    if(!empty($category[0])) {
        $categories = array_reverse($category[0]->get_parent_categories());
        foreach ($categories as $i => $category) {
            if (!empty($category)) {
                if (is_object($category[0])) {
                    $name = $category[0]->seo->name;
                    $link = $category[0]->link();
                } else {
                    $name = $category['name'];
                    $link = $category->link();
                }
                $breadcrumbs->push($name, $link);
            }
        }
    }elseif(is_object($category)){
        $categories = array_reverse($category->get_parent_categories());
        foreach ($categories as $i => $category) {
            if (!empty($category)) {
                if (is_object($category)) {
                    $name = $category->seo->name;
                    $link = $category->link();
                } else {
                    $name = $category['name'];
                    $link = $category->link();
                }
                $breadcrumbs->push($name, $link);
            }
        }
    }else{
        if (!empty($category)) {
            if (is_object($category[0])) {
                $name = $category[0]->seo->name;
                $link = $category[0]->link();
            } else {
                $name = $category['name'];
                $link = $category->link();
            }
            $breadcrumbs->push($name, $link);
        }
    }
});

// Home > Category > Filter
Breadcrumbs::for('filter', function($breadcrumbs, $category, $additional_crumb) {
    $breadcrumbs->parent('categories', $category);
    $breadcrumbs->push($additional_crumb->name);
});

// Home > Category > Product
Breadcrumbs::for('product', function($breadcrumbs, $product, $category) {
    if($category->count()) {
        $breadcrumbs->parent('categories', $category);
    }else{
        $breadcrumbs->parent('home');
    }
    $breadcrumbs->push($product->name);
});

// Home > Search
Breadcrumbs::for('search', function($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Search Results', base_url('search'));
});

// Home > Sale
Breadcrumbs::for('sale', function($breadcrumbs, $sale) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push($sale->name);
});

// Home > Cart
Breadcrumbs::for('cart', function($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Basket');
});

// Home > Checkout
Breadcrumbs::for('checkout', function($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Checkout');
});

// Home > Thanks
Breadcrumbs::for('thanks', function($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Order details');
});
