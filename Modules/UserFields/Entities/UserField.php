<?php

namespace Modules\UserFields\Entities;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class UserField extends Model
{
    use Rememberable;

    // This is obligatory.
    public $rememberCacheDriver = 'array';

    public $timestamps = false;

    const NAME_PREFIX       = 'uf_';

	const TYPE_DROPDOWN   = 1;
	const TYPE_SINGLE_LINE = 2;
	const TYPE_MULTI_LINE = 3;
	const TYPE_NUMBER     = 4;
	const TYPE_DATE       = 5;
	
	public static $types = [
		self::TYPE_DROPDOWN   => 'Dropdown',
		self::TYPE_SINGLE_LINE => 'Single Line',
		self::TYPE_MULTI_LINE => 'Multi Line',
		self::TYPE_NUMBER     => 'Number',
		self::TYPE_DATE       => 'Date',
	];

    protected $fillable = [
    	'name', 'type', 'required', 'options'
    ];

	protected $attributes = [
        'type' => self::TYPE_DROPDOWN,
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * To make types traanslatable.
     */
    public static function getTypes()
    {
    	return [
			1 => __('Dropdown'),
			2 => __('Single Line'),
			3 => __('Multi Line'),
			4 => __('Number'),
			5 => __('Date'),
    	];
    }

    public function setSortOrderLast()
    {
    	$this->sort_order = (int)UserField::max('sort_order')+1;
    }

    public static function getUserFields($cache = false)
    {
    	$query = UserField::orderby('sort_order');
        if ($cache) {
            $query->rememberForever();
        }
        return $query->get();
    }

    public static function getUserFieldsWithValues($user_id)
    {
        return UserField::
            select(['user_fields.*', 'user_user_field.value'])
            ->orderby('user_fields.sort_order')
            ->leftJoin('user_user_field', function ($join) use ($user_id) {
                $join->on('user_user_field.user_field_id', '=', 'user_fields.id')
                    ->where('user_user_field.user_id', '=', $user_id);
            })
            ->get();
    }

    public static function getValue($user_id, $user_field_id)
    {
        $field = UserUserField::where('user_id', $user_id)
            ->where('user_field_id', $user_field_id)
            ->first();

        if ($field) {
            return $field->value;
        } else {
            return '';
        }
    }

    public static function setValue($user_id, $user_field_id, $value)
    {
        try {
            $field = UserUserField::firstOrNew([
                'user_id' => $user_id,
                'user_field_id' => $user_field_id,
            ]);

            $field->user_id = $user_id;
            $field->user_field_id = $user_field_id;
            $field->value = $value;
            $field->save();

            \Eventy::action('userfields.user_field.value_updated', $field, $user_id);
        } catch (\Exception $e) {
            
        }
    }

    public function getNameEncoded()
    {
        return self::NAME_PREFIX.$this->id;
    }

    public static function decodeName($field_name)
    {
        return preg_replace("/^".self::NAME_PREFIX."/", '', $field_name);
    }

    public static function sanitizeValue($value, $field)
    {
        if ($field->type == UserField::TYPE_DROPDOWN) {
            if (!is_numeric($value) && array_search($value, $field->options)) {
                $value = array_search($value, $field->options);
            }
        } elseif ($field->type == UserField::TYPE_DATE) {
            if (!preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $value)) {
                $value = date("Y-m-d", strtotime($value));
            }
        } elseif ($field->type == UserField::TYPE_NUMBER) {
            if ($value) {
                $value = (int)$value;
            }
        }

        return $value;
    }

    public function getAsText()
    {
        if ($this->type == self::TYPE_DROPDOWN) {
            return $this->options[$this->value] ?? $this->value;
        } else {
            return $this->value;
        }
    }
}