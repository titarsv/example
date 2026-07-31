<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    protected $files;
    protected $destinationDir = 'uploads';
    protected $destinationPath = DIRECTORY_SEPARATOR.'uploads';
    protected $destinationPathSmall = DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'cache';

    public function __construct(File $files){
        $this->files = $files;
        $this->destinationDir = env('UPLOADS_DIR', 'uploads');
        $this->destinationPath = DIRECTORY_SEPARATOR.env('UPLOADS_DIR', 'uploads');
        $this->destinationPathSmall = DIRECTORY_SEPARATOR.env('UPLOADS_DIR', 'uploads').DIRECTORY_SEPARATOR.'cache';
    }

    /**
     * Страница медиафайлов
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function adminIndexAction(){
        $pageConfigs = ['isContentSidebar' => true, 'bodyCustomClass' => 'file-manager-application'];

        $files = new File();
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $total_space = $files->sizeFormat($total);
        $used_space = $files->sizeFormat($total - $free);
        $percent_space = ($total - $free) * 100 / $total;

        return view('admin.media.index')
            ->with('pageConfigs', $pageConfigs)
            ->with('active', File::count())
            ->with('is_trash', false)
            ->with('trashed', File::onlyTrashed()->count())
            ->with('total_space', $total_space)
            ->with('used_space', $used_space)
            ->with('percent_space', $percent_space);
    }

    public function adminTrashAction(){
        return view('admin.media.index')->with('trash', true)
            ->with('active', File::count())
            ->with('is_trash', true)
            ->with('trashed', File::onlyTrashed()->count());
    }

    /**
     * Загрузка медиафайла
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUploadAction(Request $request){
        $file = $request->file('async-upload');
        $destinationPath = public_path().$this->destinationPath;

        $type = $file->guessExtension();
        $newFileName = $this->generate_filename($file, $destinationPath);
        $file->move($destinationPath, $newFileName);
        $hash = md5_file($destinationPath.DIRECTORY_SEPARATOR.$newFileName);
        $isset = File::where('hash', $hash)->first();
        if(empty($isset)){
            $file = $this->files->createFile([
                'title' => $file->getClientOriginalName(),
                'path' => $this->destinationDir.DIRECTORY_SEPARATOR.$newFileName,
                'type' => in_array($type, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : ($type == 'mp4' ? 'video' : $type),
                'hash' => $hash
            ]);

            $response = [
                'success' => true,
                'data' => $file->data
            ];
        }else{
            if(is_file($destinationPath.DIRECTORY_SEPARATOR.$newFileName))
                unlink($destinationPath.DIRECTORY_SEPARATOR.$newFileName);

            $response = [
                'success' => false,
                'data' => [
                    'message' => trans('locale.media.file_exists', ['name' => $isset->title])
                ]
            ];
        }

        return response()->json($response);
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

        $originalName = str_replace(' ', '_', translit($file->getClientOriginalName()));

        if(is_file($path.DIRECTORY_SEPARATOR.$originalName)) {
            $paths = explode('.', $originalName);
            $extension = end($paths);

            $type = $file->guessExtension();
            $extensions = [
                'jpeg' => 'jpg',
                'jpg' => 'jpg',
                'png' => 'png',
                'gif' => 'gif'
            ];
            if(isset($extensions[$type])){
                $try_extension = $extensions[$type];
            }else{
                $try_extension = strtolower($extension);
            }

            $i = 2;
            $originalName = preg_replace('/(.+)(_\(\d+\))?\.'.$extension.'/', '$1_('.$i.').'.$try_extension, $originalName);
            while(is_file($path.DIRECTORY_SEPARATOR.$originalName)){
                $originalName = preg_replace('/(.+)(_\(\d+?\))\.'.$extension.'/', '$1_('.$i.').'.$try_extension, $originalName);
                $i++;
            }
        }

        return $originalName;
    }

    /**
     * Формирование размера файла
     *
     * @param $value
     *
     * @return mixed
     */
    public function convert_hr_to_bytes($value){
        $value = strtolower(trim($value));
        $bytes = (int) $value;

        if ( false !== strpos( $value, 'g' ) ) {
            $bytes *= 1024*1024*1024;
        } elseif ( false !== strpos( $value, 'm' ) ) {
            $bytes *= 1024*1024;
        } elseif ( false !== strpos( $value, 'k' ) ) {
            $bytes *= 1024;
        }

        // Deal with large (float) values which run into the maximum integer size.
        return min( $bytes, PHP_INT_MAX );
    }

    public function adminAjaxAction(Request $request){
        if($request->action == 'query-attachments'){
            return $this->queryAttachments($request->toArray());
        }elseif($request->action == 'image-editor'){
            return $this->imageEditor($request->toArray());
        }elseif($request->action == 'imgedit-preview'){
            return $this->imgeditPreview($request->toArray());
        }elseif($request->action == 'delete-post'){
            $id = intval($request->id);
            return $this->deletePost($id);
        }elseif($request->action == 'save-attachment'){
            $id = intval($request->id);
            if($request->changes['status'] == 'trash'){
                return $this->deletePost($id);
            }
        }elseif($request->action == 'send-attachment-to-editor'){
            return $this->imageHtml($request->attachment['id']);
        }

        return response()->json(['success' => false]);
    }

    public function queryAttachments($request){
        $files = new File();
        $response = ['success' => true];
        $orderby = 'date';
        $order = 'DESC';
        $posts_per_page = 40;
        $paged = 1;
        if(!empty($request['query'])){
            if(!empty($request['query']['orderby'])){
                $orderby = $request['query']['orderby'];
            }
            if(!empty($request['query']['order'])){
                $order = $request['query']['order'];
            }
            if(!empty($request['query']['posts_per_page'])){
                $posts_per_page = $request['query']['posts_per_page'];
            }
            if(!empty($request['query']['paged'])){
                $paged = $request['query']['paged'];
            }
            if(!empty($request['query']['post_mime_type'])){
                $mime = $request['query']['post_mime_type'];
            }
        }
        if($orderby == 'date'){
            $orderby = 'created_at';
        }
        $offset = $posts_per_page * ($paged - 1);

        $query = $files->orderBy($orderby, $order)->offset($offset)->take($posts_per_page);

        if(!empty($request['query']['year']) && $request['query']['year'] != 'false' && !empty($request['query']['monthnum']) && $request['query']['monthnum'] != 'false'){
            $monthnum = $request['query']['monthnum'];
            $timfrom = $request['query']['year'].'-'.($monthnum < 10 ? '0'.$monthnum : $monthnum).'-01 00:00:00';
            if($request['query']['monthnum'] == 12){
                $timto = ($request['query']['year']+1).'-01-01 00:00:00';
            }else{
                $monthnum++;
                $timto = $request['query']['year'].'-'.($monthnum < 10 ? '0'.$monthnum : $monthnum).'-01 00:00:00';
            }
            $query->whereBetween('created_at',[$timfrom,$timto]);
        }

        if(!empty($mime_type)){
            $types = [''];
            foreach($mime_type as $type){
                $types = array_merge($types, [$type]);
            }
            $query->whereIn('type', $types);
        }

        if(!empty($request['query']['s'])){
            $query->where('title', 'like', '%'.$request['query']['s'].'%');
        }

        if((!empty($request['query']['trash']) && $request['query']['trash'] == 'true') || (!empty($request['query']['post_status']) && $request['query']['post_status'] == 'trash')){
            $query->onlyTrashed();
        }

        if(!empty($mime)){
            $query->where('type', $mime);
        }

        $files = $query->get();
        foreach($files as $file){
            if(empty($file->data)){
                $file->updateData();
            }
        }
        $data = $files->pluck('data')->toArray();

        $response['data'] = $data;
        return response()->json($response);
    }

    public function imageEditor($request){
        if(!empty($request['postid'])){
            $image = File::where(['id' => $request['postid'], 'type' => 'image']);
        }

        return view('admin.media.editor')
            ->with('image', !empty($image) ? $image : null)
            ->with('nonce', $request['_ajax_nonce']);
    }

    public function imgeditPreview($request) {
        $post_id = intval($request['postid']);
        if ( empty($post_id) )
            return '-1';

        $image = File::find($post_id);
        $path = public_path() . '/uploads/' . $image->href;
        $data = $image->fileData();
        $im = $image->imagecreatefromfile($path);

        switch ( $data['mime'] ) {
            case 'image/jpeg':
                header( 'Content-Type: image/jpeg' );
                return imagejpeg( $im, null, 90 );
            case 'image/png':
                header( 'Content-Type: image/png' );
                return imagepng( $im );
            case 'image/gif':
                header( 'Content-Type: image/gif' );
                return imagegif( $im );
            default:
                return '';
        }
    }

    public function deletePost($id){
        if(empty($id))
            return '-1';
        $file = File::withTrashed()->find($id);

        if($file !== null) {

            if($file->trashed()){
                $file_path = public_path($file->path);
                if(is_file($file_path)){
                    unlink($file_path);
                }
                $file->forceDelete();
            }else{
                DB::transaction(function() use($file){
                    foreach ($file->images as $image ) {
                        $image->delete();
                    }
                    $file->delete();
                });
            }

            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Вставка изображения в эдитор
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function imageHtml($id){
        $file = File::find($id);

        return response()->json(['success' => true, 'data' => $file->webp('full', ['alt' => $file->alt, 'title' => $file->title], 'editor')]);
    }
}
