<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;
use App\Models\Category;
use App\Models\Seo;
use App\Models\Redirect;
use Carbon\Carbon;
use Modules\Blog\Models\Blog;
use Modules\Blog\Models\ContentCategory;

class XMLSitemap extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'xmlsitemap'; //название нашей команды

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generation Sitemap.xml';//описание нашей команды

    protected $xmlbase = null;
    protected $xmlbaseen = null;
    protected $xmlbaseimg = null;
    protected $xmlbaseimgen = null;
    protected $site_url = null;
    protected $locales = [];
    protected $locale = '';
    protected $redirects = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->locales = config()->get('app.locales');
        $this->locale = config()->get('app.locale');
        $this->site_url = env('APP_URL');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Relation::morphMap([
            'Pages' => \App\Models\Page::class,
            'News' => \App\Models\News::class,
            'Seo' => \App\Models\Seo::class,
            'Blog' => Blog::class,
            'Categories' => \App\Models\Category::class,
            'ContentCategories' => ContentCategory::class,
//            'Attributes' => \App\Models\Attribute::class,
//            'Values' => \App\Models\AttributeValue::class,
            'Products' => \App\Models\Product::class,
//            'Sales' => \App\Models\Sale::class,
        ]);

        foreach(Redirect::all() as $redirect){
            $this->redirects[$this->site_url.$redirect->old_url] = $this->site_url.$redirect->new_url;
        }

        $base = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
            </urlset>';
        foreach($this->locales as $locale){
//            $this->{'xmlbase'.($locale != $this->locale ? $locale : '')} = new \SimpleXMLElement($base);
            $this->{'xmlbaseimg'.($locale != $this->locale ? $locale : '')} = new \SimpleXMLElement($base);
        }

        $this->addLinks('', null, date("c"), "always", "1");


        // Товары
        foreach (Product::where('visible', 1)->with(['seo', 'image', 'gallery'])->get() as $result) {
            $date = !empty($result->updated_at) ? $result->updated_at->format( "Y-m-d\TH:i:sP") : (!empty($result->created_at) ? $result->created_at->format("Y-m-d\TH:i:sP") : Carbon::now()->format("Y-m-d\TH:i:sP"));
            $gallery = $result->gallery;
            $images = [];
            $videos = [];
            foreach($gallery as $file){
                if($file->image->type == 'image')
                    $images[] = $file->image->url();
                else
                    $videos[] = $file->image->url();
            }

            $title = [];
            foreach($this->locales as $locale){
                $t = $result->seo->localize($locale, 'meta_title');
                if($t){
                    $title[$locale] = $t . ' | Bridal Space';
                }
            }

            $this->addLinks($result->seo->url, !empty($images) ? $images : (!empty($result->image) ? $result->image->url() : null), $date, "weekly", "0.4", $videos, $title);
        }

        // Категории
        foreach(Category::where('status', 1)->with('seo', 'image', 'attributes.values')->get() as $result){
            $params = ['and', 'count', 'product_visible', 'category_'.$result->id];
            Redis::command('bitop', $params);
            $count = Redis::bitcount('count');
            if(!empty($count)){
                $this->addLinks($result->seo->url, !empty($result->image) ? $result->image->url() : null, $result->created_at->format("Y-m-d\TH:i:sP"), "daily", "0.8");

                foreach($result->attributes()->where('attributes.id', '!=', 3)->where('attributes.id', '!=', 4)->get() as $attribute){
                    $attr_slug = $attribute->slug;
                    foreach($attribute->values as $value){
                        $value_slug = $value->value;

                        $params = ['and', 'count', 'product_visible', 'category_'.$result->id, 'attribute_'.$value->id];
                        Redis::command('bitop', $params);
                        $count = Redis::bitcount('count');
                        $seo = Seo::where('seotable_type', 'Catalog')
                            ->where('url', $result->seo->url.'/'.$attr_slug.'_'.$value_slug)
                            ->where('robots', 'like', '%noindex%')
                            ->first();
                        if(!empty($count) && empty($seo)){
                            $this->addLinks($result->seo->url.'/'.$attr_slug.'_'.$value_slug, !empty($result->image) ? $result->image->url() : null, $result->created_at->format("Y-m-d\TH:i:sP"), "weekly", "0.6");
                        }
                    }
                }
            }
        }

        $links = Seo::whereNotIn('seotable_type', ['Products', 'Categories', 'Catalog', 'Checkout', 'ContentCategories'])
            ->where(function ($query) {
                $query->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');

            })
            ->get();
        foreach($links as $link){
            if($link->seotable && $link->url != '/' && $link->seotable->status !== 0){
                $this->addLinks($link->url, null, $link->updated_at->format("Y-m-d\TH:i:sP"), "monthly", "0.2");
            }
        }

        // Путь куда нужно сохранять файл
        foreach($this->locales as $locale){
//            $this->{'xmlbase'.($locale != $this->locale ? $locale : '')}->saveXML(public_path()."/sitemap-pages.xml");
            $this->{'xmlbaseimg'.($locale != $this->locale ? $locale : '')}->saveXML(public_path()."/sitemap.xml");
        }

/*        $base = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';*/
//        foreach($this->locales as $locale){
//            $base .= '<sitemap><loc>'.$this->site_url.'/sitemap-'.$locale.'.xml</loc><lastmod>'.Carbon::now()->format( "Y-m-d\TH:i:sP" ).'</lastmod></sitemap>';
//        }
//        $base .= '</sitemapindex>';
//        $xmlbaseindex = new \SimpleXMLElement($base);
//        $xmlbaseindex->saveXML(public_path()."/sitemap.xml");

/*        $base = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';*/
//        foreach($this->locales as $locale){
//            $base .= '<sitemap><loc>'.$this->site_url.'/sitemap-pages-'.$locale.'.xml</loc><lastmod>'.Carbon::now()->format( "Y-m-d\TH:i:sP" ).'</lastmod></sitemap>';
//        }
//        $base .= '</sitemapindex>';
//        $xmlbaseindex = new \SimpleXMLElement($base);
//        $xmlbaseindex->saveXML(public_path()."/sitemap.xml");
    }

    private function addLinks($link, $images = null, $lastmod = null, $changefreq = null, $priority = null, $videos = null, $title = null){
        foreach($this->locales as $locale){
            $this->addLink(rtrim($this->site_url.($locale != $this->locale ? '/'.$locale : '').$link, '/'), $images, $lastmod, $changefreq, $priority, $locale != $this->locale ? $locale : null, true, $videos, !empty($title) && isset($title[$locale]) ? $title[$locale] : null);
//            $this->addLink(rtrim($this->site_url.($locale != $this->locale ? '/'.$locale : '').$link, '/'), $images, $lastmod, $changefreq, $priority, $locale != $this->locale ? $locale : null, false, $videos, isset($title[$locale]) ? $title[$locale] : null);
        }
    }

    private function addLink($link, $images = null, $lastmod = null, $changefreq = null, $priority = null, $lang = '', $with_img = true, $videos = null, $title = null){
        $row = $this->{'xmlbase'.($with_img ? 'img' : '').$lang}->addChild("url");

        if(isset($this->redirects[$link])){
            $row->addChild("loc", $this->redirects[$link]);
        }else{
            $row->addChild("loc", $link);
        }

        if(!empty($lastmod))
            $row->addChild("lastmod", $lastmod);
        if(!empty($changefreq))
            $row->addChild("changefreq", $changefreq);
        if(!empty($priority))
            $row->addChild("priority", $priority);

        if($with_img){
            if(!empty($images)){
                if(is_array($images)){
                    foreach($images as $image){
                        $img = $row->addChild("image:image", null, 'http://www.google.com/schemas/sitemap-image/1.1');
                        $img->addChild("image:loc", $image, 'http://www.google.com/schemas/sitemap-image/1.1');
                    }
                }else{
                    $img = $row->addChild("image:image", null, 'http://www.google.com/schemas/sitemap-image/1.1');
                    $img->addChild("image:loc", $images, 'http://www.google.com/schemas/sitemap-image/1.1');
                }
            }


            if(!empty($videos)){
                foreach($videos as $video){
                    $img = $row->addChild("video:video", null, 'http://www.google.com/schemas/sitemap-video/1.1');
                    $img->addChild("video:content_loc", $video, 'http://www.google.com/schemas/sitemap-video/1.1');
                    $img->addChild("video:title", $title, 'http://www.google.com/schemas/sitemap-video/1.1');
                }
            }
        }
    }

    /**
     * @param mixed|mixed[]|object|string|null $locale
     * @return XMLSitemap
     */
    public function setLocale(mixed $locale): XMLSitemap
    {
        $this->locale = $locale;
        return $this;
    }
}
