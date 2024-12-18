<?php

namespace Modules\Snooze\Http\Controllers;

use Carbon\Carbon;
use App\Conversation;
use App\Folder;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SnoozeController extends Controller
{
    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {

            case 'snooze':
                // $conversation = Conversation::find($request->conversation_id);
                // if (!$conversation || !auth()->user()->can('update', $conversation)) {
                //     \Helper::denyAccess();
                // }
                return view('snooze::ajax_html/snooze', [
 
                ]);
                break;
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

            case 'snooze':
                $conversation = Conversation::find($request->conversation_id);
                if (!$conversation || !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg'] && !$request->snooze_date) {
                    $response['msg'] = __('Choose date & time');
                }
                if (!$response['msg'] && $conversation) {
                    $date_system = Carbon::createFromFormat('Y-m-d H:i:00', \Helper::sanitizeDatepickerDatetime($request->snooze_date).':00', $user->timezone)
                        ->setTimezone(config('app.timezone'));

                    // If date in the past.
                    if ($date_system->lessThanOrEqualTo(now())) {
                        $date_system = now()->addMinutes(5);
                    }

                    $conversation->snoozed_until = $date_system->format('Y-m-d H:i:00');
                    $conversation->save();

                    // Create folder.
                    $folder = Folder::where('mailbox_id', $conversation->mailbox_id)
                        ->where('type', \Snooze::FOLDER_TYPE)
                        ->first();
                    if (!$folder) {
                        Folder::create([
                            'mailbox_id' => $conversation->mailbox_id,
                            'type' => \Snooze::FOLDER_TYPE,
                        ]);
                    }

                    // Create background task.
                    \Helper::backgroundAction('snooze.unsnooze', [
                        $conversation->id,
                        $user->id,
                        $conversation->snoozed_until,
                    ], $date_system);

                    $response['msg_success'] = __('Conversation snoozed');
                    $response['status'] = 'success';
                }
                break;

            case 'unsnooze':
                $conversation = Conversation::find($request->conversation_id);
                if (!$conversation || !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }
                // For now we change status simply to Active.
                if (!$response['msg'] && $conversation) {
                    \Snooze::unsnooze($conversation, $user);
                    \Session::flash('flash_success_floating', __('Conversation unsnoozed'));
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
