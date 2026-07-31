<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Helpers\UnSerialize;

class ImagesController extends Controller
{
    protected $images;
    protected $destinationPath = '/uploads';
    protected $destinationPathSmall = '/uploads/cache';

    public function __construct(Image $images)
    {
        $this->images = $images;
    }

    /**
     * Получение массива изображений в админке
     *
     * @param Request $request
     * @return mixed
     */
    public function loadImages(Request $request)
    {
        $images = $this->images->where('type', '<>', 'thumb')
            ->orderBy('id', 'DESC')
            ->take(10)
            ->offset($request->offset)
            ->get()
            ->toArray();
        return ['data' => $images, 'current_offset' => $request->offset+count($images), 'count' => $this->images->count()];
    }

    /**
     * Загрузка изображения
     * @param Request $request
     * @return string
     */
    public function uploadImages(Request $request)
    {
        $files = $request->file('file');
        $destinationPath = public_path().$this->destinationPath;

        $response = ['data' => []];

        foreach($files as $file){
            //$newFileName = str_random(10).'.'.$file->guessExtension();
            $newFileName = $this->generate_filename($file, $destinationPath);
            $file->move($destinationPath, $newFileName);

            $image = new Image;
            $id = $image->insertGetId(
                ['title' => $file->getClientOriginalName(), 'href' => $newFileName]
            );

            $img = $image->get_image($id);
            $response['data'][] = $img;
            $img->update_image_sizes();

        }

        $response['status'] = 200;

        return json_encode($response);
    }

    /**
     * Загрузка изображения по url
     * @param string $url
     * @return mixed
     */
    public function uploadFromUrlImages($url)
    {
        $destinationPath = public_path().$this->destinationPath;
        $parts = explode('/', $url);
        $originalName = end($parts);
        $newFileName = $originalName;

        if(is_file($destinationPath.'\\'.$newFileName)) {
            $image = new Image;
            $img = $image->where('href', $newFileName)
                    ->take(1)
                    ->get()
                    ->first();
            return $img;
        }

        $fp = fopen($destinationPath.'\\'.$newFileName, 'w');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode == 404 || $httpCode == 301) {
            curl_close($ch);
            fclose($fp);
            unlink($destinationPath.'\\'.$newFileName);
            return false;
        }
        curl_close($ch);
        fclose($fp);

        $image = new Image;
        $id = $image->insertGetId(
            ['title' => $originalName, 'href' => $newFileName]
        );

        $img = $image->get_image($id);
        $img->update_image_sizes();

        return $img;
    }

    /**
     * Генерация уникального имени файла
     *
     * @param $file
     * @param string $path
     * @return mixed
     */
    public function generate_filename($file, $path = ''){
        if(empty($path))
            $path = public_path().$this->destinationPath;

        $originalName = str_replace(' ', '_', $file->getClientOriginalName());

        if(is_file($path.'\\'.$originalName)) {
            $paths = explode('.', $originalName);
            $extension = end($paths);
            $i = 2;
            $originalName = preg_replace('/(.+)(_\(\d+\))?\.'.$extension.'/', '$1_('.$i.').'.$extension, $originalName);
            while(is_file($path.'\\'.$originalName)){
                $originalName = preg_replace('/(.+)(_\(\d+?\))\.'.$extension.'/', '$1_('.$i.').'.$extension, $originalName);
                $i++;
            }
        }

        return $originalName;
    }

    /**
     * Удаление изображений
     * @param Request $request
     * @return mixed
     */
    public function removeImages(Request $request){
        $ids = $request->input('ids');
        if(empty($ids))
            return response()->json(['result' => 'error', 'message' => trans('locale.images.ids_not_provided')]);

        $results = [];
        $with_errors = false;

        foreach ($ids as $id) {
            if ($this->images->is_used($id)) {
                $results[] = ['id' => $id, 'status' => 'error', 'message' => trans('locale.images.in_use')];
                $with_errors = true;
            }else {

                $image = $this->images->get_image($id);
                if ($image !== null) {
                    $image_data = $image->toArray();
                    $un_serialized = new UnSerialize($image_data['sizes']);
                    $created_sizes = $un_serialized->result();
                    if (!is_array($created_sizes))
                        $created_sizes = array();

                    foreach ($created_sizes as $size => $data) {
                        $path = public_path('uploads/' . $data['href']);
                        if (is_file($path))
                            unlink($path);
                    }

                    $path = public_path('uploads/' . $image_data['href']);
                    if (is_file($path))
                        unlink($path);

                    $this->images->remove_image($id);
                    $results[] = ['id' => $id, 'status' => 'deleted', 'message' => trans('locale.images.deleted')];
                } else {
                    $results[] = ['id' => $id, 'status' => 'error', 'message' => trans('locale.images.not_found')];
                    $with_errors = true;
                }

            }
        }

        if($with_errors)
            return response()->json(['result' => 'error', 'results' => $results]);
        else
            $message = count($ids) == 1 ? trans('locale.images.deleted') : trans('locale.images.all_deleted');
            return response()->json(['result' => 'success', 'results' => $results, 'message' => $message]);
    }

    /**
     * Инициализация обновления изображений
     * @param Image $images
     * @return mixed
     */
    public function startUpdatingImages(Image $images)
    {
        $images_count = $images->count();
        $date = date('Y-m-d H:i:s');

        return response()->json(['result' => 'success', 'count' => $images_count])->withCookie(cookie()->forever('start_img_update', $date));
    }

    /**
     * Обновление одного изображения
     * @param Request $request
     * @param Image $images
     * @return mixed
     */
    public function updateImageSize(Request $request, Image $images)
    {
        $start_img_update = $request->cookie('start_img_update');
        if(empty($start_img_update)){
            return response()->json(['result' => 'error', 'message' => trans('locale.images.update_missing_date')]);
        }

        $image = $images->get_not_updated_image($start_img_update);
        if($image === null)
            return response()->json(['result' => 'end', 'message' => trans('locale.images.nothing_to_update')]);

        $image->update_image_sizes();

        return response()->json([
            'result' => 'success', 
            'progress' => $images->get_updating_progress($start_img_update),
            'message' => trans('locale.images.updating_progress')
        ]);
    }

    public function fix_images(Image $images){
    	dd($images->whereLike('like', '% %', 'href')->get());
    }
}
