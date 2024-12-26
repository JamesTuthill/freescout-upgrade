<?php

namespace Modules\Followers\Http\Controllers;

use App\Follower;
use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class FollowersController extends Controller
{
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

            // Subscribe user to the conversation.
            case 'subscribe':
            
                // Check if user has access to the mailbox.
                // Skip.
                //$conversation = Conversation::find($request->conversation_id);

                if (!$response['msg'] && $request->conversation_id && $request->user_ids && $user) {

                	foreach ($request->user_ids as $user_id) {
	                    try {
	                        $follower = Follower::where([
	                            'conversation_id' => $request->conversation_id,
	                            'user_id' => $user_id,
	                        ])->first();

	                        if (!$follower) {
	                        	$follower = new Follower();
		                        $follower->conversation_id  = $request->conversation_id;
		                        $follower->user_id          = $user_id;
		                        $follower->added_by_user_id = $user->id;
		                        $follower->save();
		                    }
	                    } catch (\Exception $e) {
	                    	// Exists   
	                    }
	                }

                    $response['status'] = 'success';
                }
                break;

 			// Unsubscribe user from the conversation.
            case 'unsubscribe':
            
                // Check if user has access to the mailbox.
                // Skip.
                //$conversation = Conversation::find($request->conversation_id);

                if (!$response['msg'] && $request->conversation_id && $request->user_ids && $user) {
   
   					foreach ($request->user_ids as $user_id) {
	                    $follower = Follower::where([
	                        'conversation_id' => $request->conversation_id,
	                        'user_id' => $user_id,
	                    ])->first();

	                    if ($follower) {
	                    	if ($follower->added_by_user_id || $user->id == $follower->user_id) {
	                    		$follower->delete();
	                    		$response['status'] = 'success';
	                    	} else {
	                    		// Self-subscribed user can not be unsubscribed.
	                    	}
	                    }
	                }
                }
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

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'add':
            	$conversation = Conversation::findOrFail($request->conversation_id);
            	$users = $conversation->mailbox->usersAssignable(true);

            	// Exclude followers.
            	$followers_ids = $conversation->followers->pluck('user_id')->all();
                
                foreach ($users as $i => $user) {
                    if (in_array($user->id, $followers_ids)) {
                        $users->forget($i);
                    }
                }

                return view('followers::add', [
                    'users' => $users,
                ]);
        }

        abort(404);
    }
}
