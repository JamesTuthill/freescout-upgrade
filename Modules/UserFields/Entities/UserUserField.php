<?php

namespace Modules\UserFields\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CustomFields\Entities\CustomField;

class UserUserField extends Model
{
    protected $table = 'user_user_field';
    
    public $timestamps = false;

    protected $fillable = [
    	'user_id', 'user_field_id', 'value'
    ];

    /**
     * Get user.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
