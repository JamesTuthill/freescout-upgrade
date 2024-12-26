<?php

namespace Modules\Checklists\Entities;

use App\Conversation;
use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_COMPLETED = 3;

    public $timestamps = false;

    public function conversation()
    {
        return $this->belongsTo('App\Conversation');
    }

    public function linked_conversation()
    {
        return $this->belongsTo('App\Conversation');
    }

    public function isCompleted()
    {
        return $this->status == self::STATUS_COMPLETED;
    }

    public function isLinked()
    {
        return $this->linked_conversation_id;
    }

    public function conversationUrl()
    {
    	return Conversation::conversationUrl($this->linked_conversation_id);
    }

    public static function create($conversation_id, $data)
    {
        $checklist_item = new self();
        $checklist_item->conversation_id = $conversation_id;
        $checklist_item->text = $data['text'] ?? '';
        $checklist_item->linked_conversation_id = 0;
        $checklist_item->linked_conversation_number = 0;
        $checklist_item->save();
    }
}