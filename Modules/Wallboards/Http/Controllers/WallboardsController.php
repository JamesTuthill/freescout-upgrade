<?php

namespace Modules\Wallboards\Http\Controllers;

use App\Conversation;
use App\User;
use Modules\Wallboards\Entities\Wallboard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WallboardsController extends Controller
{
    public function show(Request $request)
    {
        if (!\Wallboards::canAccessWallboards()) {
            \Helper::denyAccess();
        }

        $wallboard = null;

        // Params.
        $params = $this->getParams($request);

        $wallboard_id = $params['wallboard_id'] ?? null;
        //$mailbox_id = $params['mailbox_id'] ?? null;

        $user = auth()->user();

        // $mailboxes = $user->mailboxesCanView(true);
        // $mailboxes = $mailboxes->sortBy('name');

        $wallboards = \Wallboards::wallboardsUserCanView($user);

        if ($wallboard_id === null) {
            if (count($wallboards)) {
                return $this->redirectToFirst($wallboards);
            }
        }

        $widgets = [];
        $empty_panel = [];

        if ($wallboard_id) {
            $wallboard = Wallboard::find($wallboard_id);

            if (!$wallboard) {
                return $this->redirectToFirst($wallboards);
            }

            if (!in_array($wallboard_id, $wallboards->pluck('id')->all())) {
                $empty_panel = ['icon' => 'ban-circle', 'empty_text' => __("You don't have access to this wallboard")];
            } else {
                $widgets = $this->getWidgetsWithData($wallboard, $params);
            }
        }

        return view('wallboards::show', [
            'wallboards' => $wallboards,
            'wallboard_id' => $wallboard_id,
            'wallboard' => $wallboard,
            'empty_panel' => $empty_panel,
            'params' => $params,
            'user' => $user,
            'widgets' => $widgets,
        ]);
    }

    public function redirectToFirst($wallboards)
    {
        if (count($wallboards)) {
            return redirect()->route('wallboards.show', ['wb' => ['wallboard_id' => $wallboards->first()->id]]);
        } else {
            return redirect()->route('wallboards.show');
        }
    }

    public function getParams($request)
    {
        $default = [
            'filters' => \Wallboards::$default_filters,
            //'group_by' => null,
        ];

        $params = array_merge($default, $request->wb ?? []);

        // Filters.
        foreach ($params['filters'] as $i => $value) {
            if (!is_array($value)) {
                $params['filters'][$i] = explode(\Wallboards::FILTERS_SEPARATOR, $value);
            }
        }
        $params['filters'] = array_merge($default['filters'], $params['filters']);

        // Sanitize filters.
        if (empty($params['filters']['date']['period'])) {
            $params['filters']['date']['period'] = \Wallboards::DATE_PERIOD_WEEK;
        }
        if (empty($params['filters']['date']['to'])) {
            $params['filters']['date']['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        }
        if (empty($params['filters']['date']['from'])) {
            $params['filters']['date']['from'] = date('Y-m-d', strtotime($params['filters']['date']['to'].' -1 week'));
        }

        // Group by.
        // if ($params['group_by'] == \Wallboards::GROUP_BY_TAG && !\Module::isActive('tags')) {
        //     $params['group_by'] = null;
        // }
        // if ($params['group_by'] == \Wallboards::GROUP_BY_CF && !\Module::isActive('customfields')) {
        //     $params['group_by'] = null;
        // }

        return $params;
    }

    public function getWidgetsWithData($wallboard, $params)
    {
        $widgets = $wallboard->widgets ?? [];

        foreach ($widgets as $i => $widget) {

            $widget = \Wallboard::sanitizeWidget($widget);

            // Calculate metrics
            if ((int)$widget['metrics_visibility']) {
                $query = Conversation::select(['status', \DB::raw('COUNT(*) as conv_count')])
                    ->where('state', Conversation::STATE_PUBLISHED)
                    ->groupBy('status');

                // If table is grouped by custom field, count in metrics
                // only conversations where this custom field is set.
                if (strstr($widget['group_by'], 'custom_field:')) {
                    if (\Module::isActive('customfields')) {
                        $custom_field_id = (int)preg_replace("#.*:#", '', $widget['group_by']);
                        $custom_field = \Modules\CustomFields\Entities\CustomField::find($custom_field_id);

                        if ($custom_field) {
                            $query->join('conversation_custom_field', function ($join) {
                                    $join->on('conversation_custom_field.conversation_id', '=', 'conversations.id');
                                })
                                ->where('conversation_custom_field.custom_field_id', $custom_field_id);
                        }
                    }
                }

                $this->applyFilters($query, $widget, $params);

                $metrics_data = $query->get()->toArray();

                foreach ($metrics_data as $data_item) {
                    $widget['data']['metrics'][$data_item['status']] = (int)$data_item['conv_count'];
                }

                if (empty($widget['metrics'])) {
                    $widget['metrics'] = \Wallboards::$default_metrics;
                }
            }

            // Get table data.
            if (!empty($widget['group_by'])) {
                
                // Group by assignee.
                if ($widget['group_by'] == \Wallboards::GROUP_BY_ASSIGNEE) {

                    $widget['data']['group_by_entity_name'] = __('User');

                    $query = Conversation::select(['user_id', 'status', \DB::raw('COUNT(*) as conv_count')])
                        ->where('state', Conversation::STATE_PUBLISHED)
                        ->groupBy(['user_id', 'status']);

                    $this->applyFilters($query, $widget, $params);

                    $table_data = $query->get()->toArray();

                    $users = User::select(['id', 'first_name', 'last_name'])->get();

                    foreach ($table_data as $data_item) {
                        $widget['data']['table'][$data_item['user_id']]['metrics'][$data_item['status']] = (int)$data_item['conv_count'];

                        // Title.
                        if ($data_item['user_id'] == '') {
                            $user_name = __('Unassigned');
                        } else {
                            $user = $users->find($data_item['user_id']);
                            $user_name = $user ? $user->getFullName() : '?';
                        }
                        $widget['data']['table'][$data_item['user_id']]['entity_title'] = $user_name;
                    }
                }

                // Group by conversation type.
                if ($widget['group_by'] == \Wallboards::GROUP_BY_TYPE) {

                    $widget['data']['group_by_entity_name'] = __('Type');

                    $query = Conversation::select(['type', 'status', \DB::raw('COUNT(*) as conv_count')])
                        ->where('state', Conversation::STATE_PUBLISHED)
                        ->groupBy(['type', 'status']);

                    $this->applyFilters($query, $widget, $params);

                    $table_data = $query->get()->toArray();

                    foreach ($table_data as $data_item) {
                        $widget['data']['table'][$data_item['type']]['metrics'][$data_item['status']] = (int)$data_item['conv_count'];

                        // Title.
                        $widget['data']['table'][$data_item['type']]['entity_title'] = Conversation::typeToName($data_item['type']);
                    }
                }

                // Group by tag.
                if ($widget['group_by'] == \Wallboards::GROUP_BY_TAG) {
                    $widget['data']['group_by_entity_name'] = __('Tag');

                    if (\Module::isActive('tags')) {
                        $query = Conversation::select(['conversation_tag.tag_id as tag_id', 'status', \DB::raw('COUNT(*) as conv_count')])
                            ->where('state', Conversation::STATE_PUBLISHED)
                            ->join('conversation_tag', function ($join) {
                                $join->on('conversation_tag.conversation_id', '=', 'conversations.id');
                            })
                            ->groupBy(['conversation_tag.tag_id', 'status']);

                        $this->applyFilters($query, $widget, $params);
                        $table_data = $query->get()->toArray();

                        $tags = \Modules\Tags\Entities\Tag::select(['id', 'name'])->pluck('name', 'id');

                        foreach ($table_data as $data_item) {
                            $widget['data']['table'][$data_item['tag_id']]['metrics'][$data_item['status']] = (int)$data_item['conv_count'];
                            // Title.
                            $widget['data']['table'][$data_item['tag_id']]['entity_title'] = $tags[$data_item['tag_id']] ?? '?';
                        }
                    }
                }

                // Group by Custom Field.
                if (strstr($widget['group_by'], 'custom_field:')) {
                    
                    if (\Module::isActive('customfields')) {
                        $custom_field_id = (int)preg_replace("#.*:#", '', $widget['group_by']);
                        $custom_field = \Modules\CustomFields\Entities\CustomField::find($custom_field_id);

                        if ($custom_field) {
                            $widget['data']['group_by_entity_name'] = $custom_field->name;

                            $query = Conversation::select([\DB::raw('MAX(conversation_custom_field.custom_field_id) as custom_field_id'), 'conversation_custom_field.value as value', 'status', \DB::raw('COUNT(*) as conv_count')])
                                ->where('state', Conversation::STATE_PUBLISHED)
                                ->where('conversation_custom_field.custom_field_id', $custom_field_id)
                                ->join('conversation_custom_field', function ($join) {
                                    $join->on('conversation_custom_field.conversation_id', '=', 'conversations.id');
                                })
                                ->groupBy(['conversation_custom_field.value', 'status']);

                            $this->applyFilters($query, $widget, $params);
                            $table_data = $query->get()->toArray();
// echo "<pre>";
// print_r($custom_field_id);
// print_r($table_data);
// print_r($query->toSql());
// exit();
                            if (count($table_data)) {
                                // $custom_fields = \Modules\CustomFields\Entities\CustomField::select(['id', 'name'])
                                //     ->whereIn('id', array_column($table_data, 'custom_field_id'))
                                //     ->pluck('name', 'id');

                                foreach ($table_data as $data_item) {
                                    $custom_field_id = $data_item['custom_field_id'];
                                    $custom_field->value = $data_item['value'];

                                    $widget['data']['table'][$custom_field->value]['metrics'][$data_item['status']] = (int)$data_item['conv_count'];
                                    $widget['data']['table'][$custom_field->value]['entity_title'] = $custom_field->getAsText();
                                }
                            }
                        } else {
                            $widget['data']['group_by_entity_name'] = __('Custom Field not found');
                        }
                    } else {
                        $widget['data']['group_by_entity_name'] = __('Custom Field');
                    }
                }

                // Sort table.
                if (!empty($widget['sort_by']) && !empty($widget['data']['table'])) {
                    $sort_by = $widget['sort_by'];
                    usort($widget['data']['table'], function($a, $b) use ($sort_by) {
                        return ($b['metrics'][$sort_by] ?? 0) <=> ($a['metrics'][$sort_by] ?? 0);
                    });
                }
            }

            $widget = \Wallboard::sanitizeWidget($widget);
            $widgets[$i] = $widget;
        }

        return $widgets;
    }

    public function applyFilters($query, $widget, $params)
    {
        // Widget parameters.
        if (!empty($widget['metrics'])) {
            $query->whereIn('status', $widget['metrics']);
        }

        // Mailbox.
        // todo: support multiple mailboxes.
        if (!empty($widget['filters']['mailbox']) && !empty($widget['filters']['mailbox'][0])) {
            $query->where('mailbox_id', $widget['filters']['mailbox'][0]);
        }
        // Type.
        if (!empty($widget['filters']['type']) && !empty($widget['filters']['type'][0])) {
            $query->where('type', $widget['filters']['type'][0]);
        }
        // Assignee.
        if (!empty($widget['filters']['user_id']) && !empty($widget['filters']['user_id'][0])) {
            $query->where('user_id', $widget['filters']['user_id'][0]);
        }
        // Tag.
        if (!empty($widget['filters']['tag']) && !empty($widget['filters']['tag'][0])) {
            if (\Module::isActive('tags')) {
                if (!Conversation::queryContainsStr($query->toSql(), '`conversation_tag`')) {
                    $query->join('conversation_tag', function ($join) {
                        $join->on('conversation_tag.conversation_id', '=', 'conversations.id');
                    });
                }
                $query->where('conversation_tag.tag_id', $widget['filters']['tag'][0]);
            }
        }

        // Date period.
        $date_from = '';
        $date_to = '';

        if (!empty($widget['filters']['date']['period'])) {
            switch ($widget['filters']['date']['period']) {
                case \Wallboards::DATE_PERIOD_TODAY:
                    $date_from = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
                    $date_to = $date_from;
                    break;
                case \Wallboards::DATE_PERIOD_YESTERDAY:
                    $date_from = User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 day')), 'Y-m-d', null, false);
                    $date_to = $date_from;
                    break;
                case \Wallboards::DATE_PERIOD_WEEK:
                    $date_from = User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 week')), 'Y-m-d', null, false);
                    break;
                case \Wallboards::DATE_PERIOD_MONTH:
                    $date_from = User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 month')), 'Y-m-d', null, false);
                    break;
                case \Wallboards::DATE_PERIOD_YEAR:
                    $date_from = User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 year')), 'Y-m-d', null, false);
                    break;
            }
        } else {
            // Global date filter.
            if (($params['filters']['date']['period'] ?? \Wallboards::DATE_PERIOD_ALL_TIME) != \Wallboards::DATE_PERIOD_ALL_TIME) {
                if (!empty($params['filters']['date']['from'])) {
                    $date_from = $params['filters']['date']['from'];
                }
                if (!empty($params['filters']['date']['to'])) {
                    $date_to = $params['filters']['date']['to'];
                }
            }
        }
        if (!empty($date_from)) {
            $query->where('last_reply_at', '>=', date('Y-m-d 00:00:00', strtotime($date_from)));
        }
        if (!empty($date_to)) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($date_to)));
        }

        // Custom fields.
        if (!empty($widget['filters'][\Wallboards::FILTER_BY_CF]) 
            && is_array($widget['filters'][\Wallboards::FILTER_BY_CF])
            && \Module::isActive('customfields')
        ) {
            if (!Conversation::queryContainsStr($query->toSql(), 'ccf_filter')) {
                $query->join('conversation_custom_field as ccf_filter', function ($join) {
                    $join->on('ccf_filter.conversation_id', '=', 'conversations.id');
                });
            }
            $cf_filters = $widget['filters'][\Wallboards::FILTER_BY_CF];

            $query->where(function ($q) use ($cf_filters) {
                foreach ($cf_filters as $custom_field_id => $filter) {
                    if (!isset($filter['value'])) {
                        continue;
                    }
                    $q->orWhere('ccf_filter.custom_field_id', '=', $custom_field_id)
                        ->where('ccf_filter.value', '='/*$filter['op']*/, $filter['value']);
                }
            });
        }

        return $query;
    }

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

            // Show wallboard
            case 'show':
                $params = $this->getParams($request);

                $wallboard_id = $params['wallboard_id'] ?? '';
                $wallboard = null;

                if ($wallboard_id) {
                    $wallboard = Wallboard::find($wallboard_id);
                    if (!$wallboard) {
                        $response['msg'] = __('Wallboard not found');
                    }
                }

                if (!$response['msg'] && $wallboard && !$wallboard->userCanView($user)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    
                    $data = [
                        'wallboard_id' => $wallboard_id,
                        'wallboard' =>  $wallboard,
                        'widgets' =>  $this->getWidgetsWithData($wallboard, $params),
                    ];

                    $response['html'] = \View::make('wallboards::partials/widgets')->with($data)->render();
                    $response['status'] = 'success';
                }
                break;

            // Move widget.
            case 'move_widget':
                $wallboard = Wallboard::find($request->wallboard_id);

                if (!$wallboard) {
                    $response['msg'] = __('Wallboard not found');
                } elseif (!$wallboard->userCanUpdate($user)) {
                    $response['msg'] = __('Not enough permissions');
                } else {
                    // Sort widgets
                    $widgets = $wallboard->widgets ?? [];
                    $sorted_widgets = [];
                    foreach ($request->widgets as $widget_id) {
                        foreach ($widgets as $widget) {
                            if (!empty($widget['id']) && $widget['id'] == $widget_id) {
                                $sorted_widgets[] = $widget;
                                break;
                            }
                        }
                    }

                    $wallboard->widgets = $sorted_widgets;
                    $wallboard->save();

                    $response['status'] = 'success';
                }
                break;

            // New wallboard.
            case 'new_wallboard':
                $wallboard = new Wallboard();
                $wallboard->name = $request->name;
                $wallboard->visibility = $request->visibility;
                $wallboard->created_by_user_id = $user->id;
                $wallboard->save();

                $response['status'] = 'success';
                $response['wallboard_url'] = route('wallboards.show', ['wb' => ['wallboard_id' => $wallboard->id]]);
                break;

            // Update wallboard.
            case 'update_wallboard':
                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard) {
                    $response['msg'] = __('Wallboard not found');
                } elseif (!$wallboard->userCanUpdate($user)) {
                    $response['msg'] = __('Not enough permissions');
                } else {
                    $wallboard->name = $request->name;
                    $wallboard->visibility = $request->visibility;
                    $wallboard->save();

                    $response['status'] = 'success';
                }
                break;

            // New widget.
            case 'new_widget':
                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard) {
                    $response['msg'] = __('Wallboard not found');
                } elseif (!$wallboard->userCanUpdate($user)) {
                    $response['msg'] = __('Not enough permissions');
                } else {
                    $widget_data = [
                        'title' => $request->title,
                        'metrics' => $request->metrics ?? ($request->group_by ? [] : null),
                        'metrics_visibility' => $request->metrics_visibility,
                        'group_by' => $request->group_by,
                        'sort_by' => $request->sort_by,
                        'filters' => $request->filters,
                    ];
                    $widget = $wallboard->addWidget($widget_data);

                    $response['status'] = 'success';
                    $response['msg_success'] = __("Widget created");
                }
                
                //$response['wallboard_url'] = route('wallboards.show', ['wb' => ['wallboard_id' => $request->wallboard_id]]);
                break;

            // Update widget.
            case 'update_widget':
                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard) {
                    $response['msg'] = __('Wallboard not found');
                } elseif (!$wallboard->userCanUpdate($user)) {
                    $response['msg'] = __('Not enough permissions');
                } else {
                    $widget_data = [
                        'id' => $request->id,
                        'title' => $request->title,
                        'metrics' => $request->metrics ?? ($request->group_by ? [] : null),
                        'metrics_visibility' => $request->metrics_visibility,
                        'group_by' => $request->group_by,
                        'sort_by' => $request->sort_by,
                        'filters' => $request->filters,
                    ];
                    $widget = $wallboard->updateWidget($widget_data);

                    if ($widget) {
                        $response['status'] = 'success';
                        $response['msg_success'] = __("Widget updated");
                    } else {
                        $response['msg'] = __("Widget not found");
                    }
                }
                break;

            // Delete wallboard.
            case 'delete_wallboard':
                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard) {
                    $response['msg'] = __('Wallboard not found');
                } elseif (!$wallboard->userCanDelete($user)) {
                    $response['msg'] = __('Not enough permissions');
                } else {
                    $wallboard->deleteWallboard();
                    \Session::flash('flash_success_floating', __('Wallboard deleted'));
                    $response['status'] = 'success';
                }
                break;

            // Delete widget.
            case 'delete_widget':
                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard) {
                    $response['msg'] = __('Wallboard not found');
                } elseif (!$wallboard->userCanUpdate($user)) {
                    $response['msg'] = __('Not enough permissions');
                } else {
                    $widget_data = [
                        'id' => $request->id,
                        'title' => $request->title,
                        'metrics' => $request->metrics,
                        'group_by' => $request->group_by,
                        'sort_by' => $request->sort_by,
                        'filters' => $request->filters,
                    ];
                    $wallboard->deleteWidget($request->widget_id);

                    $response['status'] = 'success';
                    $response['msg_success'] = __("Widget deleted");
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occurred';
        }

        return \Response::json($response);
    }

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'new_wallboard':
                $wallboard = new Wallboard();
                return view('wallboards::ajax_html/update_wallboard', [
                    'mode' => 'create',
                    'wallboard' => $wallboard,
                ]);
                break;

            case 'update_wallboard':
                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard || !$wallboard->userCanUpdate()) {
                    \Helper::denyAccess();
                }
                return view('wallboards::ajax_html/update_wallboard', [
                    'mode' => 'update',
                    'wallboard' => $wallboard,
                ]);
                break;

            case 'new_widget':
                $widget = Wallboard::sanitizeWidget([]);

                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard || !$wallboard->userCanUpdate()) {
                    \Helper::denyAccess();
                }

                $custom_fields = $this->getCustomFields();

                return view('wallboards::ajax_html/update_widget', [
                    'mode' => 'create',
                    'widget' => Wallboard::sanitizeWidget($widget),
                    'wallboard' => $wallboard,
                    'custom_fields' => $custom_fields,
                ]);
                break;

            case 'update_widget':
                $wallboard = Wallboard::find($request->wallboard_id);
                if (!$wallboard || !$wallboard->userCanView()) {
                    \Helper::denyAccess();
                }
                $widget = $wallboard->getWidget($request->widget_id);
                
                if (!$widget) {
                    \Helper::denyAccess();
                }

                $custom_fields = $this->getCustomFields();

                return view('wallboards::ajax_html/update_widget', [
                    'mode' => 'update',
                    'widget' => $widget,
                    'wallboard' => $wallboard,
                    'custom_fields' => $custom_fields,
                ]);
                break;
        }

        abort(404);
    }

    public function getCustomFields()
    {
        if (!\Module::isActive('customfields')) {
            return [];
        }

        $mailboxes = auth()->user()->mailboxesCanView(true);

        $custom_fields = \Modules\CustomFields\Entities\CustomField::whereIn('mailbox_id', $mailboxes->pluck('id'))
            ->orderby('sort_order')
            ->get();

        foreach ($custom_fields as $i => $custom_field) {
            $mailbox = $mailboxes->find($custom_field->mailbox_id);
            if ($mailbox) {
                $custom_fields[$i]->mailbox = $mailbox;
            } else {
                unset($custom_fields[$i]);
            }
        }

        return $custom_fields;
    }
}
