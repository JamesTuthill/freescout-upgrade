<?php

namespace Modules\UserFields\Http\Controllers;

use Modules\UserFields\Entities\UserField;
use Modules\UserFields\Entities\UserUserField;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class UserFieldsController extends Controller
{
    /**
     * Ajax controller.
     */
    public function ajaxAdmin(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            // Create/update saved reply
            case 'user_field_create':
            case 'user_field_update':
                
                if (!$response['msg']) {
                    $name = $request->name;

                    if (!$name) {
                        $response['msg'] = __('Name is required');
                    }
                }
                
                // Check unique name.
                if (!$response['msg']) {
                    $name_exists = UserField::where('name', $name);

                    if ($request->action == 'user_field_update') {
                        $name_exists->where('id', '!=', $request->user_field_id);
                    }
                    $name_exists = $name_exists->first();

                    if ($name_exists) {
                        $response['msg'] = __('A User Field with this name already exists.');
                    }
                }

                if (!$response['msg']) {

                    if ($request->action == 'user_field_update') {
                        $user_field = UserField::find($request->user_field_id);
                        if (!$user_field) {
                            $response['msg'] = __('User Field not found');
                        }
                    } else {
                        $user_field = new UserField();
                        $user_field->setSortOrderLast();
                    }

                    if (!$response['msg']) {
                        //$user_field->mailbox_id = $mailbox->id;
                        $user_field->name = $name;
                        if ($request->action != 'user_field_update') {
                            $user_field->type = $request->type;
                        }
                        if ($user_field->type == UserField::TYPE_DROPDOWN) {
                            
                            if ($request->action == 'user_field_create') {
                                $options = [];
                                $options_tmp = preg_split('/\r\n|[\r\n]/', $request->options ?? '');
                                // Remove empty
                                $option_index = 1;
                                foreach ($options_tmp as $i => $value) {
                                    $value = trim($value);
                                    if ($value) {
                                        $options[$option_index] = $value;
                                        $option_index++;
                                    }
                                }
                                if (empty($options)) {
                                    $options = [1 => ''];
                                }
                            } else {
                                $options = $request->options;
                            }

                            $user_field->options = $options;

                            // Remove values.
                            if ($user_field->id) {
                                UserUserField::where('user_field_id', $user_field->id)
                                    ->whereNotIn('value', array_keys($request->options))
                                    ->delete();
                            }
                        } elseif (isset($request->options)) {
                            $user_field->options = $request->options;
                        } else {
                            $user_field->options = '';
                        }
                        $user_field->required = $request->filled('required');
                        $user_field->save();

                        $response['id']     = $user_field->id;
                        $response['name']   = $user_field->name;
                        $response['required']   = (int)$user_field->required;
                        $response['status'] = 'success';

                        if ($request->action == 'user_field_update') {
                            $response['msg_success'] = __('User field updated');
                        } else {
                            // Flash
                            \Session::flash('flash_success_floating', __('User field created'));
                        }
                    }
                }
                break;

            // Delete
            case 'user_field_delete':

                if (!$response['msg']) {
                    $user_field = UserField::find($request->user_field_id);

                    if (!$user_field) {
                        $response['msg'] = __('User Field not found');
                    }
                }

                if (!$response['msg']) {
                    \Eventy::action('user_field.before_delete', $user_field);
                    $user_field->delete();

                    // Delete links to users;
                    UserUserField::where('user_field_id', $request->user_field_id)->delete(); 

                    $response['status'] = 'success';
                    $response['msg_success'] = __('User Field deleted');

                    \Eventy::action('user_field.after_delete', $request->user_field_id);
                }
                break;

            // Update sort order.
            case 'user_field_update_sort_order':

                if (!$response['msg']) {

                    $user_fields = UserField::whereIn('id', $request->user_fields)->select('id', 'sort_order')->get();

                    if (count($user_fields)) {
                        foreach ($request->user_fields as $i => $request_user_field_id) {
                            foreach ($user_fields as $user_field) {
                                if ($user_field->id != $request_user_field_id) {
                                    continue;
                                }
                                $user_field->sort_order = $i+1;
                                $user_field->save();
                            }
                        }
                        $response['status'] = 'success';
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
     * Ajax html.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'create_user_field':
                return view('userfields::ajax_html/user_field_create', [
                    'user_field' => new \UserField,
                ]);
        }

        abort(404);
    }

    /**
     * Ajax search.
     */
    public function ajaxSearch(Request $request)
    {
        $response = [
            'results'    => [],
            'pagination' => ['more' => false],
        ];

        $query = UserUserField::select('value')
            ->where('user_field_id', UserField::decodeName($request->user_field_id))
            ->where('value', 'like', '%'.$request->q.'%')
            ->orderBy('value')
            ->groupBy('value');

        $user_fields = $query->paginate(20);

        foreach ($user_fields as $row) {
            $response['results'][] = [
                'id'   => $row->value,
                'text' => $row->value,
            ];
        }

        $response['pagination']['more'] = $user_fields->hasMorePages();

        return \Response::json($response);
    }
}
