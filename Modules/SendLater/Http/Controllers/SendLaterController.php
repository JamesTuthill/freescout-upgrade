<?php

namespace Modules\SendLater\Http\Controllers;

use Carbon\Carbon;
use App\Conversation;
use App\Thread;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SendLaterController extends Controller
{
    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'schedule':
                $conversation = Conversation::findOrFail($request->conversation_id);

                return view('sendlater::shedule_modal', [
                    
                ]);
        }

        abort(404);
    }

    /**
     * Conversations ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            case 'send_now':
                $thread = Thread::find($request->thread_id);
                if ($thread) {
                    \SendLater::sendThreadNow($thread);
                    $response['status'] = 'success';
                }
                break;

            case 'cancel':
                $thread = Thread::find($request->thread_id);
                if ($thread) {
                    \SendLater::cancelThreadSending($thread);

                    $response['status'] = 'success';
                }
                break;

            case 'calc':
                $datetime = Carbon::now();

                switch ($request->period) {
                    case 'minutes':
                        $datetime->addMinutes($request->number);
                        break;

                    case 'hours':
                        $datetime->addHours($request->number);
                        break;

                    case 'days':
                        $datetime->addDays($request->number);
                        break;

                    case 'weeks':
                        $datetime->addWeeks($request->number);
                        break;

                    case 'months':
                        $datetime->addMonths($request->number);
                        break;

                    case 'years':
                        $datetime->addYears($request->number);
                        break;
                }

                $response['status'] = 'success';
                $response['datetime'] = User::dateFormat($datetime, 'Y-m-d H:i', null, false);
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }
}
