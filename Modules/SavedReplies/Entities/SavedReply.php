<?php
/**
 * Outgoing emails.
 */

namespace Modules\SavedReplies\Entities;

use App\Attachment;
use App\Mailbox;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    /**
     * For caching.
     */
    public static $parent_ids = [];

    public $attachments_cached = null;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Convert to array.
     */
    protected $casts = [
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function (SavedReply $model) {
            $model->sort_order = SavedReply::where('mailbox_id', $model->mailbox_id)->max('sort_order')+1;
        });
    }

    /**
     * Get mailbox.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }

    /**
     * Threads created from saved reply.
     */
    public function threads()
    {
        return $this->hasMany('App\Thread');
    }

    public static function userCanUpdateMailboxSavedReplies(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin() || ($user->hasPermission(User::PERM_EDIT_SAVED_REPLIES) && $mailbox->userHasAccess($user->id))) {
            return true;
        } else {
            return false;
        }
    }

    public static function listToTree($list, $parent_saved_reply_id = 0)
    {
        $tree = [];

        if (!$list) {
            return [];
        }

        foreach ($list as $saved_reply) {
            if ($saved_reply->parent_saved_reply_id != (int)$parent_saved_reply_id) {
                continue;
            }
            
            $saved_reply->saved_replies = self::listToTree($list, $saved_reply->id);
            $tree[] = $saved_reply;
        }

        return $tree;
    }

    public static function listToFlatTree($list, $tree = [], $level = 0, $parent_saved_reply_id = 0)
    {
        if (!count($tree)) {
            $list = $list->sortBy('sort_order');
        }

        if (!$list) {
            return [];
        }

        foreach ($list as $saved_reply) {
            if ($saved_reply->parent_saved_reply_id != (int)$parent_saved_reply_id) {
                continue;
            }
            $saved_reply->level = $level;
            $tree[] = $saved_reply;

            $tree = self::listToFlatTree($list, $tree, $level+1, $saved_reply->id);
        }

        return $tree;
    }

    public function isChild($id, $saved_replies, $list_hash = '')
    {
        $is_child = false;

        if (!empty($list_hash)) {
            if (!isset(self::$parent_ids[$list_hash])) {
                self::$parent_ids[$list_hash] = array_column($saved_replies, 'parent_saved_reply_id');
            }
            if (!in_array($this->id, self::$parent_ids[$list_hash])) {
                return false;
            }
        }

        foreach ($saved_replies as $saved_reply) {
            if ($saved_reply->parent_saved_reply_id == $this->id) {
                if ($saved_reply->id == $id) {
                    return true;
                } else {
                    $is_child = $saved_reply->isChild($id, $saved_replies, $list_hash);
                    if ($is_child) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function savedRepliesListHash($list)
    {
        if (empty($list)) {
            return '';
        }
        return md5(json_encode(array_column($list, 'id')));
    }

    public function getAttachments($cache = false)
    {
        if (empty($this->attachments)) {
            return collect([]);
        }
        if ($cache && $this->attachments_cached !== null) {
            return $this->attachments_cached;
        }
        $this->attachments_cached = Attachment::whereIn('id', $this->attachments)->get();

        return $this->attachments_cached;
    }

    public function deleteSavedReply()
    {
        // Delete refereces to this save reply.
        self::where('parent_saved_reply_id', $this->id)->update(['parent_saved_reply_id' => null]);

        // Delete attachments.
        if (!empty($this->attachments)) {
            $attachments = $this->getAttachments();
            Attachment::deleteForever($attachments);
        }

        $this->delete();
    }
}
