<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App;

class GoogleDrive
{
    public function getAuthLink(){
        $client_id = env('GOOGLE_CLIENT_ID');
        $redirect_uri = env('GOOGLE_REDIRECT');

        return "https://accounts.google.com/o/oauth2/v2/auth/oauthchooseaccount?
            redirect_uri=$redirect_uri&
            prompt=
            consent&response_type=code&
            client_id=$client_id&
            scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.appdata%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.apps.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.file%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.meet.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.metadata%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.metadata.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.photos.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.scripts%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.file%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.file%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.appdata%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.file%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.file%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.file%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.metadata%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.metadata.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive.readonly&
            access_type=offline&
            service=lso&
            o2v=2&
            flowName=GeneralOAuthFlow";
    }

    public function getRefreshToken($code){
        $client_id = env('GOOGLE_CLIENT_ID');
        $client_secret = env('GOOGLE_CLIENT_SECRET');

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => env('GOOGLE_REDIRECT'),
            'grant_type' => 'authorization_code',
            'code' => $code
        ]);

        $refresh_token = json_decode((string) $response->getBody(), true)['refresh_token'];

        return $refresh_token;
    }

    public function token()
    {
        $client_id = env('GOOGLE_CLIENT_ID');
        $client_secret = env('GOOGLE_CLIENT_SECRET');
        $refresh_token = env('GOOGLE_DRIVE_REFRESH_TOKEN');

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        $accessToken = json_decode((string) $response->getBody(), true)['access_token'];

        return $accessToken;
    }

    public function store($file)
    {
        if(empty($file)){
            return false;
        }

        if(!empty($file->gd_id)){
            return $file->gd_id;
        }

        $path = public_path($file->path);
        $name = $file->filename;

        if(Storage::disk('google')->put($name, file_get_contents($path))){
            $disk = Storage::disk('google');
            $service = $disk->getAdapter()->getService();
            $folderId = env('GOOGLE_DRIVE_FOLDER_ID');
            $query = "'$folderId' in parents and name = '$name' and trashed = false";

            $params = [
                'q' => $query,
                'fields' => 'files(id, name, parents)',
                'spaces' => 'drive',
                'orderBy' => 'createdTime desc',
                'pageSize' => 1
            ];

            $results = $service->files->listFiles($params);

            $fileId = null;
            if(count($results->getFiles()) > 0){
                $gdfile = $results->getFiles()[0];
                $fileId = $gdfile->getId();
                $file->gd_id = $fileId;
                $file->save();

                return $fileId;
            }

            return true;
        }

        return false;
    }
}
