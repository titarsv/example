<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSession extends Model
{
    protected $table = 'sessions';

    public function getUserActivity($limit = 43200)
    {
        $lastActivity = strtotime(Carbon::now()->subMinutes($limit));

        return $this->where('last_activity', '>=', $lastActivity)->get();

    }

    public function getBrowser($user_agent)
    {
        $browser = 'Other';
        $platform = 'Other';

        //First get the platform
        if (preg_match('/linux/i', $user_agent)) $platform = 'linux';
        elseif (preg_match('/macintosh|mac os x/i', $user_agent)) $platform = 'mac';
        elseif (preg_match('/windows|win32/i', $user_agent)) $platform = 'windows';

        // Get browser name
        if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) $browser = 'Opera';
        elseif (strpos($user_agent, 'Edge')) $browser =  'Edge';
        elseif (strpos($user_agent, 'Chrome')) $browser =  'Chrome';
        elseif (strpos($user_agent, 'Safari')) $browser =  'Safari';
        elseif (strpos($user_agent, 'Firefox')) $browser =  'Firefox';
        elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) $browser =  'Internet Explorer';

        return array(
            'name'      => $browser,
            'platform'  => $platform,
        );
    }
}
