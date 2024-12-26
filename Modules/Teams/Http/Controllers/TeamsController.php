<?php

namespace Modules\Teams\Http\Controllers;

use App\Events\UserDeleted;
use App\Folder;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Validator;

class TeamsController extends Controller
{
    public function teams(Request $request)
    {
        //$query = Tag::select('tags.*')->orderBy('name');

        $user = \Auth::user();

        $teams = \Team::getTeams();

        return view('teams::teams', [
            'teams' => $teams
        ]);
    }

    public function create(Request $request)
    {
        $team = new User();

        $members = collect([]);
        $users = User::nonDeleted()->get();

        $mailboxes = Mailbox::get();

        return view('teams::update', [
            'mode' => 'create',
            'team' => $team,
            'members' => $members,
            'users' => $users,
            'team_mailboxes' => $team->mailboxes,
            'mailboxes' => $mailboxes,
        ]);
    }

    public function update(Request $request, $id)
    {
        $team = User::findOrFail($id);

        if (\Team::isDeleted($team)) {
            abort(404);
        }

        $members = \Team::getMembers($team);

        $users = User::nonDeleted()->get();

        // Exclude members from users.
        foreach ($members as $member) {
            foreach ($users as $i => $user) {
                if ($user->id == $member->id) {
                    unset($users[$i]);
                    break;
                }
            }
        }

        $mailboxes = Mailbox::get();

        return view('teams::update', [
            'mode' => 'update',
            'team' => $team,
            'members' => $members,
            'users' => $users,
            'team_mailboxes' => $team->mailboxes,
            'mailboxes' => $mailboxes,
        ]);
    }

    public function updateSave(Request $request)
    {
        if ($request->action == 'create') {
            $team = new User();    
        } else {
            $team = User::findOrFail($request->id);
            if (!\Team::isTeam($team)) {
                abort(404);
            }
        }
        //$invalid = false;

        $rules = [
            'first_name' => 'required|string|max:20',
            //'photo_url'   => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ];
        $validator = Validator::make($request->all(), $rules);

        // $validator->setAttributeNames([
        //     'photo_url'   => __('Team Image'),
        // ]);

        // Save image.
        // $validator->after(function ($validator) use ($team, $request) {
        //     if ($request->hasFile('photo_url')) {
        //         $path_url = $team->savePhoto($request->file('photo_url'));

        //         if ($path_url) {
        //             $team->photo_url = $path_url;
        //         } else {
        //             $invalid = true;
        //             $validator->errors()->add('photo_url', __('Error occured processing the image. Make sure that PHP GD extension is enabled.'));
        //         }
        //     }
        // });

        if ($validator->fails()) {
            if ($request->action == 'create') {
                return redirect()->route('teams.create')
                        ->withErrors($validator)
                        ->withInput();
            } else {
                return redirect()->route('teams.update', ['id' => $request->id])
                        ->withErrors($validator)
                        ->withInput();
            }
        }
        
        $prev_folder_icon = $team->photo_url;

        $team->photo_url = $request->icon;
        $team->first_name = $request->first_name;
        $team->last_name = \Team::TEAM_USER_LAST_NAME;
        $team->role = User::ROLE_USER;
        $team->status = User::STATUS_DELETED;
        $team->type = User::TYPE_ROBOT;
        $team->invite_state = User::INVITE_STATE_ACTIVATED;
        if ($request->action == 'create') {
            $team->email = \Team::generateEmail();
            $team->password = \Hash::make($team->generateRandomPassword());
        }
        \Team::setMembers($team, $request->members);
        //$team = \Eventy::filter('user.create_save', $team, $request);
        $team->save();
        
        // Folders.
        $new_mailbox_ids = $request->mailboxes ?? [];
        // Delete folders.
        $prev_mailbox_ids = $team->mailboxes->pluck('id')->toArray();
        foreach ($prev_mailbox_ids as $mailbox_id) {
            if (!in_array($mailbox_id, $new_mailbox_ids)) {
                Folder::where('mailbox_id', $mailbox_id)
                    ->where('user_id', $team->id)
                    ->delete();
            }
        }
        
        // Create new folders.
        foreach ($new_mailbox_ids as $mailbox_id) {

            if (!in_array($mailbox_id, $prev_mailbox_ids)) {

                // Check if mailbox already have this folder.
                $has_folder = Folder::where('mailbox_id', $mailbox_id)
                    ->where('user_id', $team->id)
                    ->first();
                if ($has_folder) {
                    continue;
                }

                $folder = new Folder();
                $folder->type = \Team::FOLDER_TYPE;
                $folder->mailbox_id = $mailbox_id;       
                $folder->user_id = $team->id;
                if ($request->icon) {
                    $folder->setMeta('icon', $request->icon);
                }
                $folder->save();
            }
        }

        // Update folder icons.
        if ($prev_folder_icon != $request->icon) {
            $folders = Folder::where('user_id', $team->id)->get();
            foreach ($folders as $folder) {
                if ($request->icon) {
                    $folder->setMeta('icon', $request->icon);
                } else {
                    $folder->unsetMeta('icon');
                }
                $folder->save();
            }
        }

        // Mailboxes.
        $team->mailboxes()->sync($new_mailbox_ids);

        if ($request->action == 'create') {
            \Session::flash('flash_success_floating', __('Team created'));
        } else {
            \Session::flash('flash_success_floating', __('Team updated'));
        }

        // Save image.
        // if ($request->hasFile('photo_url')) {
        //     $path_url = $team->savePhoto($request->file('photo_url'));

        //     if ($path_url) {
        //         $team->photo_url = $path_url;
        //         $team->save();
        //     } else {
        //         $validator->errors()->add('photo_url', __('Error occured processing the image. Make sure that PHP GD extension is enabled.'));
        //         if ($request->action == 'create') {
        //             return redirect()->route('teams.create')
        //                     ->withErrors($validator)
        //                     ->withInput();
        //         } else {
        //             return redirect()->route('teams.update', ['id' => $request->id])
        //                     ->withErrors($validator)
        //                     ->withInput();
        //         }
        //     }
        // }
        
        return redirect()->route('teams.update', ['id' => $team->id]);
    }

    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $auth_user = auth()->user();

        switch ($request->action) {

            case 'delete_team':
                $team = User::find($request->team_id);

                if (!$team) {
                    $response['msg'] = __('Team not found');
                } elseif (!$auth_user->can('delete', $team)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {

                    // We have to process conversations one by one to move them to Unassigned folder,
                    // as conversations may be in different mailboxes
                    $mailbox_unassigned_folders = [];

                    $team->conversations->each(function ($conversation) use ($auth_user, $request) {
                        // We don't fire ConversationUserChanged event to avoid sending notifications to users
                        if (!empty($request->assign_user) && !empty($request->assign_user[$conversation->mailbox_id]) && (int) $request->assign_user[$conversation->mailbox_id] != -1) {
                            // Set assignee.
                            $conversation->user_id = $request->assign_user[$conversation->mailbox_id];
                            // In this case conversation stays assigned, just assignee changes.
                        } else {
                            // Set assignee.
                            $conversation->user_id = null;

                            // Change conversation folder to ANASSIGNED.
                            $folder_id = null;
                            if (!empty($mailbox_unassigned_folders[$conversation->mailbox_id])) {
                                $folder_id = $mailbox_unassigned_folders[$conversation->mailbox_id];
                            } else {
                                $folder = $conversation->mailbox->folders()
                                    ->where('type', Folder::TYPE_UNASSIGNED)
                                    ->first();

                                if ($folder) {
                                    $folder_id = $folder->id;
                                    $mailbox_unassigned_folders[$conversation->mailbox_id] = $folder_id;
                                }
                            }
                            if ($folder_id) {
                                $conversation->folder_id = $folder_id;
                            }
                        }

                        $conversation->save();

                        // Create lineitem thread
                        $thread = new Thread();
                        $thread->conversation_id = $conversation->id;
                        $thread->user_id = $conversation->user_id;
                        $thread->type = Thread::TYPE_LINEITEM;
                        $thread->state = Thread::STATE_PUBLISHED;
                        $thread->status = Thread::STATUS_NOCHANGE;
                        $thread->action_type = Thread::ACTION_TYPE_USER_CHANGED;
                        $thread->source_via = Thread::PERSON_USER;
                        $thread->source_type = Thread::SOURCE_TYPE_WEB;
                        $thread->customer_id = $conversation->customer_id;
                        $thread->created_by_user_id = $auth_user->id;
                        $thread->save();
                    });

                    // Recalculate counters for folders
                    // Admin has access to all mailboxes
                    Mailbox::all()->each(function ($mailbox) {
                        $mailbox->updateFoldersCounters();
                    });

                    // Disconnect team from mailboxes.
                    $team->mailboxes()->sync([]);
                    \Team::deleteFolders($team);

                    $team->status = User::STATUS_DELETED;
                    // Update email.
                    $email_suffix = User::EMAIL_DELETED_SUFFIX.date('YmdHis');
                    // We have to truncate email to avoid "Data too long" error.
                    $team->email = mb_substr($team->email, 0, User::EMAIL_MAX_LENGTH - mb_strlen($email_suffix)).$email_suffix;

                    $team->save();

                    event(new UserDeleted($team, $auth_user));

                    \Session::flash('flash_success_floating', __('Team deleted').': '.$team->first_name);

                    $response['status'] = 'success';
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
}
