<?php

namespace Modules\GlobalMailbox\Http\Controllers;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class GlobalMailboxController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * View mailbox.
     */
    public function view($request_folder_type_id = null)
    {
        $user = auth()->user();

        $conversations = [];
        
        $folders_data = \GlobalMailbox::getFolders($user, $request_folder_type_id);

        $folders = $folders_data['folders'];
        $folder = $folders_data['folder'];
        $mailboxes = $folders_data['mailboxes'];

        \GlobalMailbox::$folders = $folders;

        $query_conversations = \GlobalMailbox::getConversationsQuery($folder, $user, $mailboxes->pluck('id'));
        $conversations = $folder->queryAddOrderBy($query_conversations)->paginate(Conversation::DEFAULT_LIST_SIZE);

        $mailbox = new Mailbox();
        $mailbox->id = \GlobalMailbox::MAILBOX_ID;
        $mailbox->name = __('Global Mailbox');

        return view('mailboxes/view', [
            'mailbox'       => $mailbox,
            'folders'       => $folders,
            'folder'        => $folder,
            'conversations' => $conversations,
            'params'        => [
                'show_mailbox' => true,
                'target_blank' => (int)config('globalmailbox.target_blank'),
            ],
        ]);
    }

    
}
