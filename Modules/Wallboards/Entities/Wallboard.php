<?php

namespace Modules\Wallboards\Entities;

use App\Mailbox;
use Illuminate\Database\Eloquent\Model;

class Wallboard extends Model
{   
    const VISIBILITY_ME = 1;
    const VISIBILITY_ADMINS = 2;
    const VISIBILITY_ALL = 3;

    protected $casts = [
        'widgets' => 'array',
        //'filters' => 'array',
    ];

    public $timestamps = false;

    /**
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    //protected $fillable = ['mailbox_id'];

    public function created_by_user()
    {
        return $this->belongsTo('App\User');
    }

    public function userCanView($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if ($this->created_by_user_id == $user->id) {
            return true;
        }
        switch ($this->visibility) {
            case self::VISIBILITY_ME:
                return false;
                break;
            
            case self::VISIBILITY_ADMINS:
                return $user->isAdmin();
                break;

            case self::VISIBILITY_ALL:
                return true;
                break;
        }

        return false;
    }

    public function userCanUpdate($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        return ($this->created_by_user_id == $user->id) || $user->isAdmin();
    }

    public function userCanDelete($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if ($this->created_by_user_id == $user->id) {
            return true;
        }
        if ($this->created_by_user && $this->created_by_user->isDeleted() && $user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function deleteWallBoard()
    {
        \Eventy::action('wallboard.before_delete', $this);
        $this->delete();
    }

    public function url()
    {
        if ($this->id) {
            $params['wallboard_id'] = $this->id;
        }
        return \Wallboards::url($params);
    }

    public function getVisibilityName()
    {
        switch ($this->visibility) {
            case self::VISIBILITY_ME:
                return __('Me');
            case self::VISIBILITY_ADMINS:
                return __('Admins');
            case self::VISIBILITY_ALL:
                return __('All');
        }
    }

    public function isCreatedByUser($user_id = null)
    {
        if (!$user_id) {
            $user_id = auth()->id();
        }

        return $this->created_by_user_id == $user_id;
    }

    public function getWidget($widget_id)
    {
        foreach ($this->widgets as $widget) {
            if (!empty($widget['id'] && $widget['id'] == $widget_id)) {
                return self::sanitizeWidget($widget);
            }
        }

        return null;
    }

    public function getCreatedByUserName()
    {
        return $this->created_by_user ? $this->created_by_user->getFullName() : '';
    }

    public static function sanitizeWidget($widget)
    {
        $widget['sort_by'] = $widget['sort_by'] ?? '';
        $widget = array_merge($widget, [
            'id' => $widget['id'] ?? '',
            'title' => $widget['title'] ?? '',
            'metrics' => $widget['metrics'] ?? [\Wallboards::METRIC_ACTIVE, \Wallboards::METRIC_PENDING, \Wallboards::METRIC_CLOSED],
            'metrics_visibility' => $widget['metrics_visibility'] ?? 1,
            'group_by' => $widget['group_by'] ?? '',
            //'rows_count' => $widget['rows_count'] ?? '',
            'sort_by' => $widget['sort_by'] ?: \Wallboards::METRIC_ACTIVE,
            'filters' => $widget['filters'] ?? [],
        ]);

        // Remove empty Custom Field filters.
        if (!empty($widget['filters'][\Wallboards::FILTER_BY_CF])) {
            foreach ($widget['filters'][\Wallboards::FILTER_BY_CF] as $i => $value) {
                if (!isset($value['value']) || $value['value'] === '') {
                    unset($widget['filters'][\Wallboards::FILTER_BY_CF][$i]);
                }
            }
        }

        return $widget;
    }

    public function addWidget($widget_data)
    {
        $widget_data['id'] = $this->generateWidgetId();

        $widgets = $this->widgets;
        $widgets[] = $widget_data;

        $this->widgets = $widgets;
        $this->save();

        return $widget_data;
    }

    public function updateWidget($widget_data)
    {
        if (empty($widget_data['id'])) {
            return false;
        }

        $widgets = $this->widgets;

        foreach ($widgets as $i => $widget) {
            if (!empty($widget['id']) && $widget['id'] == $widget_data['id']) {
                $widgets[$i] = $widget_data;
                
                $this->widgets = $widgets;
                $this->save();

                return $widgets[$i];
            }
        }

        return false;
    }

    public function deleteWidget($widget_id)
    {
        $widgets = $this->widgets;

        foreach ($widgets as $i => $widget) {
            if (!empty($widget['id']) && $widget['id'] == $widget_id) {
                unset($widgets[$i]);
                
                $this->widgets = $widgets;
                $this->save();

                return true;
            }
        }

        return false;
    }

    public function generateWidgetId()
    {
        do {
            $id = \Str::random(8);
        } while(in_array($id, array_column($this->widgets ?? [], 'id')));

        return $id;
    }
}
