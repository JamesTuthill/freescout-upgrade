@extends('layouts.app')

@section('title', __('Wallboards'))
@section('content_class', 'content-full')
@section('body_class', 'footer-hide')

@section('body_attrs')@parent data-wallboard_id="{{ $wallboard_id }}"@endsection

@section('content')
<div class="container">
<div class="main-heading" id="wb-heading">
        {{ __('Wallboards') }}

        <div class="in-heading" id="wb-toolbar">
            <div class="in-heading-item">

                @if (count($wallboards))
                    <select class="form-control" id='wb-wallboards-list' autocomplete="off">
                        
                        @foreach ($wallboards as $wallboard_item)
                            <option value="{{ \Wallboards::url(['wallboard_id' => $wallboard_item->id]) }}" @if ($wallboard_item->id == $wallboard_id) selected @endif>{{ $wallboard_item->name }} (@if ($wallboard_item->isCreatedByUser()){{ $wallboard_item->getVisibilityName() }}@else{{ __('Author').': '.$wallboard_item->getCreatedByUserName() }}@endif)</option>
                        @endforeach
                    </select>

                    <span class="dropdown">
                        <a href="" class="btn btn-primary btn-input-size" data-toggle="dropdown"><span class="caret"></span></a>
                        <ul class="dropdown-menu with-icons pull-right">
                            @if ($wallboard && $wallboard->userCanUpdate($user))
                                <li><a href="{{ route('wallboards.ajax_html', ['action' => 'update_wallboard', 'wallboard_id' => $wallboard_id]) }}" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ $wallboard->name }}" data-modal-on-show="wbInitWallboardModal"><i class="glyphicon glyphicon-cog"></i> {{ __('Wallboard Settings') }}</a></li>
                            @endif
                            <li>
                                <a href="{{ route('wallboards.ajax_html', ['action' => 'new_wallboard']) }}" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('New Wallboard') }}" data-modal-on-show="wbInitWallboardModal"><i class="glyphicon glyphicon-plus"></i> {{ __('New Wallboard') }}</a>
                            </li>
                            @if ($wallboard && $wallboard->userCanDelete($user))
                                <li><a href="" id="wb-delete-wallboard"><i class="glyphicon glyphicon-trash"></i> {{ __('Delete Wallboard') }}</a></li>
                            @endif
                            {{--<li><a href="" id="wb-copy-link"><i class="glyphicon glyphicon-link"></i> {{ __('Copy Link') }}</a></li>--}}
                        </ul>
                    </span>
                @else
                    <span data-toggle="tooltip" title="{{ __('New Wallboard') }}">
                        <a href="{{ route('wallboards.ajax_html', ['action' => 'new_wallboard']) }}" class="btn btn-primary btn-input-size" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('New Wallboard') }}" data-modal-on-show="wbInitWallboardModal"><i class="glyphicon glyphicon-plus-sign"></i></a>
                    </span>
                @endif

                @if (count($wallboards))
                    &nbsp;
                    <span class="wb-param">
                        <nobr><input type="text" name="from" class="form-control wb-filter-date" value="{{ $params['filters']['date']['from'] }}" />-<input type="text" name="to" class="form-control wb-filter-date" value="{{ $params['filters']['date']['to'] }}" />
                            <select class="form-control" id="wb-date-period">
                                <option value="{{ \Wallboards::DATE_PERIOD_ALL_TIME }}" @if ($params['filters']['date']['period'] == \Wallboards::DATE_PERIOD_ALL_TIME) selected @endif>{{ __('All time') }}</option>
                                <option value="{{ \Wallboards::DATE_PERIOD_TODAY }}" data-date-from="{{ App\User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false) }}" data-date-to="{{ App\User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false) }}" @if ($params['filters']['date']['period'] == \Wallboards::DATE_PERIOD_TODAY) selected @endif>{{ __('Today') }}</option>
                                <option value="{{ \Wallboards::DATE_PERIOD_YESTERDAY }}" data-date-from="{{ App\User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 day')), 'Y-m-d', null, false) }}" data-date-to="{{ App\User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 day')), 'Y-m-d', null, false) }}" @if ($params['filters']['date']['period'] == \Wallboards::DATE_PERIOD_YESTERDAY) selected @endif>{{ __('Yesterday') }}</option>
                                <option value="{{ \Wallboards::DATE_PERIOD_WEEK }}" data-date-from="{{ App\User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 week')), 'Y-m-d', null, false) }}" data-date-to="{{ App\User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false) }}" @if ($params['filters']['date']['period'] == \Wallboards::DATE_PERIOD_WEEK) selected @endif>{{ __('Last 7 days') }}</option>
                                <option value="{{ \Wallboards::DATE_PERIOD_MONTH }}" data-date-from="{{ App\User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 month')), 'Y-m-d', null, false) }}" data-date-to="{{ App\User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false) }}" @if ($params['filters']['date']['period'] == \Wallboards::DATE_PERIOD_MONTH) selected @endif>{{ __('Last 30 days') }}</option>
                                <option value="{{ \Wallboards::DATE_PERIOD_YEAR }}" data-date-from="{{ App\User::dateFormat(date('Y-m-d H:i:s', strtotime('-1 year')), 'Y-m-d', null, false) }}" data-date-to="{{ App\User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false) }}" @if ($params['filters']['date']['period'] == \Wallboards::DATE_PERIOD_YEAR) selected @endif>{{ __('Last 365 days') }}</option>
                                <option value="{{ \Wallboards::DATE_PERIOD_CUSTOM }}" @if ($params['filters']['date']['period'] == \Wallboards::DATE_PERIOD_CUSTOM) selected @endif>{{ __('Custom') }}</option>
                            </select>
                        </nobr>
                    </span>

                    {{--<span class="dropdown wb-param" data-toggle="tooltip" title="{{ __('Filters') }}" data-param="filters">
                        <a href="" class="btn btn-primary btn-input-size" data-toggle="dropdown"><i class="glyphicon glyphicon-filter"></i><span class="wb-param-counter"></span> <span class="caret"></span></a>
                        <ul class="dropdown-menu pull-right">

                            <li>Mailbox</li>
                            <li>Type</li>
                            
                            <li @if (!empty($params['filters']['user_id'])) class="active" @endif data-param-name="{{ \Wallboards::FILTER_BY_ASSIGNEE }}" data-param-value="{{ implode(\Wallboards::FILTERS_SEPARATOR, $params['filters']['user_id']) }}"><a href="{{ route('wallboards.ajax_html', ['action' => 'filter', 'wallboard_id'=>$wallboard_id, 'filter'=>\Wallboards::FILTER_BY_ASSIGNEE, 'selected' => $params['filters']['user_id']]) }}" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('Filter') }}: {{ __('Assignee') }}" data-modal-size="sm" data-modal-on-show="wbInitFilterModal">{{ __('Assignee') }} <span class="wb-filter-counter">@if (count($params['filters']['user_id']))({{ count($params['filters']['user_id']) }})@endif</span></a></li>
                            
                            <li @if (!empty($params['filters']['tag'])) class="active" @endif data-param-name="{{ \Wallboards::FILTER_BY_TAG }}" data-param-value="{{ implode(\Wallboards::FILTERS_SEPARATOR, $params['filters']['tag']) }}"><a href="{{ route('wallboards.ajax_html', ['action' => 'filter', 'wallboard_id'=>$wallboard_id, 'filter'=>\Wallboards::FILTER_BY_TAG]) }}" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('Filter') }}: {{ __('Tag') }}" data-modal-size="sm" data-modal-on-show="wbInitFilterModal">{{ __('Tag') }} <span class="wb-filter-counter">@if (count($params['filters']['tag']))({{ count($params['filters']['tag']) }})@endif</span></a></li>
                            
                            @if (\Module::isActive('customfields'))
                                <li @if (!empty($params['filters']['custom_field'])) class="active" @endif data-param-name="{{ \Wallboards::FILTER_BY_CF }}" data-param-value="{{ implode(\Wallboards::FILTERS_SEPARATOR, $params['filters']['custom_field']) }}"><a href="{{ route('wallboards.ajax_html', ['action' => 'filter', 'wallboard_id'=>$wallboard_id, 'filter'=>\Wallboards::FILTER_BY_CF, 'selected' => $params['filters']['custom_field']]) }}" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('Filter') }}: {{ __('Custom Fields') }}" data-modal-on-show="wbInitFilterModal">{{ __('Custom Fields') }} <span class="wb-filter-counter">@if (count($params['filters']['custom_field']))({{ count($params['filters']['custom_field']) }})@endif</span></a></li>
                            @endif

                            <li class="divider wb-reset-filter @if (\Wallboards::$default_filters == $params['filters']) hidden @endif"></li>
                            <li class="wb-reset-filter @if (\Wallboards::$default_filters == $params['filters']) hidden @endif"><a href="{{ \Wallboards::url(['wallboard_id' => $wallboard_id]) }}" id="wb-reset-filter">{{ __('Reset Filters') }}</a></li>
                        </ul>
                    </span>--}}

                    <span>
                        <a href="#" id="wb-btn-refresh" class="btn btn-primary btn-input-size" data-toggle="tooltip" title="{{ __('Refresh') }}"><i class="glyphicon glyphicon-refresh"></i></a>
                    </span>
                @endif

            </div>
        </div>
    </div>

    @if (!empty($empty_panel))
        @include('partials/empty', $empty_panel ?: ['icon' => 'dashboard'])
    @else
        <div id="wb-wallboard">
            @include('wallboards::partials/widgets')
        </div>
    @endif

</div>
@include('partials/include_datepicker')
@endsection

@section('javascript')
    @parent
    wbInit("{{ __("Are you sure you want to delete this wallboard?") }}");
    var wb_text_delete_card = '{{ __("Are you sure you want to delete this widget?") }}';
@endsection