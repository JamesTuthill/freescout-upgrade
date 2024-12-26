<?php

namespace Modules\SenderTimeZone\Http\Controllers;

use App\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SenderTimeZoneController extends Controller
{
    /**
     * Translation modal dialog.
     */
    public function modal($thread_id)
    {
        $thread = Thread::find($thread_id);
        if (!$thread) {
            abort(404);
        }

        $data = \SenderTimeZone::parseMailHeaders($thread->headers);

        $map_hours = 0;

        if ($data['tz']) {
            preg_match("/(.)(\d{2}):(\d{2})/", $data['tz'], $m);

            if (!empty($m[1]) && !empty($m[2]) && !empty($m[3])) {
                $map_hours = (int)$m[2]*3600 + (int)$m[3]*60;
                if ($m[1] == '-') {
                    $map_hours = -1*$map_hours;
                }
            }
        }

        return view('sendertimezone::modal', [
            'data' => $data ,
            'map_hours' => $map_hours
        ]);
    }
}
