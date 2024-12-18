<?php

namespace Modules\Checklists\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ChecklistsController extends Controller
{
    /**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Add task.
            case 'add':

                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }

                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    \ChecklistItem::create($request->conversation_id, [
                        'text' => $request->text,
                    ]);

                    $response['html'] = $this->getItemsHtml($request->conversation_id);
                    $response['status'] = 'success';
                }
                break;

            case 'change_status':

                $item = \ChecklistItem::find($request->item_id);

                if (!$item) {
                    $response['msg'] = __('Task not found');
                }

                if (!$response['msg']) {
                    $conversation = Conversation::find($item->conversation_id);

                    if (!$conversation) {
                        $response['msg'] = __('Conversation not found');
                    }
                }

                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    if ((int)$request->completed) {
                        $item->status = \ChecklistItem::STATUS_COMPLETED;
                    } else {
                        $item->status = \ChecklistItem::STATUS_ACTIVE;
                    }
                    $item->save();

                    $response['status'] = 'success';
                }
                break;

            case 'link_conversation':

                $conversation = null;
                if (!empty($request->conversation_number)) {
                    $conversation = Conversation::where(Conversation::numberFieldName(), $request->conversation_number)->first();
                    if (!$conversation) {
                        $response['msg'] = __('Conversation with #:number number not found', ['number' => $request->conversation_number]);
                    } elseif (!$conversation->mailbox->userHasAccess($user->id, $user)) {
                        $response['msg'] = __("You don't have access to the #:number conversation", ['number' => $request->conversation_number]);
                    }
                }

                $item = \ChecklistItem::find($request->item_id);

                if (!$item) {
                    $response['msg'] = __('Task not found');
                }

                $item_conversation = null;
                if (!$response['msg']) {
                    $item_conversation = Conversation::find($item->conversation_id);

                    if (!$item_conversation) {
                        $response['msg'] = __('Conversation not found');
                    }
                }

                if (!$response['msg'] && !$user->can('update', $item_conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $item->linked_conversation_id = $conversation->id;
                    $item->linked_conversation_number = $conversation->number;
                    $item->save();
                    $response['html'] = $this->getItemsHtml($item->conversation_id);
                    $response['status'] = 'success';
                }
                break;

            case 'unlink_conversation':

                $item = \ChecklistItem::find($request->item_id);

                if (!$item) {
                    $response['msg'] = __('Task not found');
                }

                $item_conversation = null;
                if (!$response['msg']) {
                    $item_conversation = Conversation::find($item->conversation_id);

                    if (!$item_conversation) {
                        $response['msg'] = __('Conversation not found');
                    }
                }

                if (!$response['msg'] && !$user->can('update', $item_conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $item->linked_conversation_id = null;
                    $item->linked_conversation_number = null;
                    $item->save();
                    $response['html'] = $this->getItemsHtml($item->conversation_id);
                    $response['status'] = 'success';
                }
                break;

            case 'delete':

                $item = \ChecklistItem::find($request->item_id);

                if (!$item) {
                    $response['msg'] = __('Task not found');
                }

                $item_conversation = null;
                if (!$response['msg']) {
                    $item_conversation = Conversation::find($item->conversation_id);

                    if (!$item_conversation) {
                        $response['msg'] = __('Conversation not found');
                    }
                }

                if (!$response['msg'] && !$user->can('update', $item_conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $item->delete();

                    $response['html'] = $this->getItemsHtml($item->conversation_id);
                    $response['status'] = 'success';
                }
                break;

            case 'save':

                $item = \ChecklistItem::find($request->item_id);

                if (!$item) {
                    $response['msg'] = __('Task not found');
                }

                $item_conversation = null;
                if (!$response['msg']) {
                    $item_conversation = Conversation::find($item->conversation_id);

                    if (!$item_conversation) {
                        $response['msg'] = __('Conversation not found');
                    }
                }

                if (!$response['msg'] && !$user->can('update', $item_conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg'] && $request->text) {
                    $item->text = $request->text;
                    $item->save();

                    $response['html'] = $this->getItemsHtml($item->conversation_id);
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

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        $user = auth()->user();
        
        switch ($request->action) {

            case 'link_conversation':
 
                $item = \ChecklistItem::find($request->item_id);

                if (!$item) {
                    \Helper::denyAccess();
                }

                $conversation = Conversation::find($item->conversation_id);

                if (!$conversation) {
                    \Helper::denyAccess();
                }

                if (!$user->can('update', $conversation)) {
                    \Helper::denyAccess();
                }
                
                return view('checklists::ajax_html/link_conversation', [
                    'item_id' => $request->item_id,
                    'mailbox_id' => $conversation->mailbox_id,
                ]);
                break;
        }

        abort(404);
    }

    public function getItemsHtml($conversation_id)
    {
        $items = \ChecklistItem::where('conversation_id', $conversation_id)
            ->orderBy('id')
            ->get();

        return \View::make('checklists::partials/items', [
            'items' => $items,
            //'mailbox_id' => $conversation->mailbox_id,
        ])->render();
    }
}
