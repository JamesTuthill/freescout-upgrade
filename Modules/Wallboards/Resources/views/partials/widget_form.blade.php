@php
    $mailboxes = auth()->user()->mailboxesCanView(true);
@endphp
<input type="hidden" name="wallboard_id" value="{{ $wallboard->id }}" />
<input type="hidden" name="id" value="{{ $widget['id'] ?? '' }}" />

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Title') }}</label>

    <div class="col-sm-9">
        <input class="form-control wb-widget-form-title" name="title" value="{{ $widget['title'] }}" placeholder="{{ __('(optional)') }}" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Metrics') }}</label>

    <div class="col-sm-9">
        <div class="control-group">
            <label class="checkbox" for="wb-metric-{{ \Wallboards::METRIC_ACTIVE }}">
                <input type="checkbox" name="metrics[]" value="{{ \Wallboards::METRIC_ACTIVE }}" id="wb-metric-{{ \Wallboards::METRIC_ACTIVE }}" @if (in_array(\Wallboards::METRIC_ACTIVE, $widget['metrics'])) checked="checked" @endif> {{ __('Active Conversations') }}
            </label>
            <label class="checkbox" for="wb-metric-{{ \Wallboards::METRIC_PENDING }}">
                <input type="checkbox" name="metrics[]" value="{{ \Wallboards::METRIC_PENDING }}" id="wb-metric-{{ \Wallboards::METRIC_PENDING }}"  @if (in_array(\Wallboards::METRIC_PENDING, $widget['metrics'])) checked="checked" @endif> {{ __('Pending Conversations') }}
            </label>
            <label class="checkbox" for="wb-metric-{{ \Wallboards::METRIC_CLOSED }}">
                <input type="checkbox" name="metrics[]" value="{{ \Wallboards::METRIC_CLOSED }}" id="wb-metric-{{ \Wallboards::METRIC_CLOSED }}"  @if (in_array(\Wallboards::METRIC_CLOSED, $widget['metrics'])) checked="checked" @endif> {{ __('Closed Conversations') }}
            </label>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Metrics Visibility') }}</label>

    <div class="col-sm-9">
        <select class="form-control" name="metrics_visibility">
            <option value="1" @if ((int)$widget['metrics_visibility']) selected="selected" @endif>{{ __('Show') }}</option>
            <option value="0" @if (!(int)$widget['metrics_visibility']) selected="selected" @endif>{{ __('Hide') }}</option>
        </select>
    </div>
</div>

<h3 class="subheader">{{ __('Table') }}</h3>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Group By') }}</label>

    <div class="col-sm-9">
        <select class="form-control" name="group_by">
            <option value=""></option>
            <option value="{{ \Wallboards::GROUP_BY_ASSIGNEE }}" @if ($widget['group_by'] == \Wallboards::GROUP_BY_ASSIGNEE) selected="selected" @endif>{{ __('Assignee') }}</option>
            <option value="{{ \Wallboards::GROUP_BY_TYPE }}" @if ($widget['group_by'] == \Wallboards::GROUP_BY_TYPE) selected="selected" @endif>{{ __('Type') }}</option>
            @if (\Module::isActive('tags'))
                <option value="{{ \Wallboards::GROUP_BY_TAG }}" @if ($widget['group_by'] == \Wallboards::GROUP_BY_TAG) selected="selected" @endif>{{ __('Tags') }}</option>
            @endif
            @if (count($custom_fields))
                @foreach($custom_fields as $custom_field)
                    @if ($custom_field->mailbox && in_array($custom_field->type, [CustomField::TYPE_DROPDOWN, CustomField::TYPE_SINGLE_LINE, CustomField::TYPE_SINGLE_LINE, CustomField::TYPE_NUMBER, CustomField::TYPE_DATE]))
                        <option value="{{ \Wallboards::GROUP_BY_CF }}:{{ $custom_field->id }}" @if ($widget['group_by'] == \Wallboards::GROUP_BY_CF.':'
                        .$custom_field->id) selected="selected" @endif class="wb-mailbox-filter" data-mailbox-id="{{ $custom_field->mailbox_id }}">{{ $custom_field->name }} ({{ $custom_field->mailbox->name }})</option>
                    @endif
                @endforeach
            @endif
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Sort By') }}</label>

    <div class="col-sm-9">
        <select class="form-control" name="sort_by">
            <option value=""></option>
            <option value="{{ \Wallboards::METRIC_ACTIVE }}" @if ($widget['sort_by'] == \Wallboards::METRIC_ACTIVE) selected="selected" @endif>{{ __('Active Conversations') }}</option>
            <option value="{{ \Wallboards::METRIC_PENDING }}" @if ($widget['sort_by'] == \Wallboards::METRIC_PENDING) selected="selected" @endif>{{ __('Pending Conversations') }}</option>
            <option value="{{ \Wallboards::METRIC_CLOSED }}" @if ($widget['sort_by'] == \Wallboards::METRIC_CLOSED) selected="selected" @endif>{{ __('Closed Conversations') }}</option>
        </select>
    </div>
</div>

<h3 class="subheader">{{ __('Parameters') }}</h3>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Date') }}</label>

    <div class="col-sm-9">
        <select class="form-control" name="filters[date][period]">
            <option value=""></option>
            <option value="{{ \Wallboards::DATE_PERIOD_TODAY }}" @if (($widget['filters']['date']['period'] ?? '') == \Wallboards::DATE_PERIOD_TODAY) selected @endif>{{ \Wallboards::getPeriodName(\Wallboards::DATE_PERIOD_TODAY) }}</option>
            <option value="{{ \Wallboards::DATE_PERIOD_YESTERDAY }}" @if (($widget['filters']['date']['period'] ?? '') == \Wallboards::DATE_PERIOD_YESTERDAY) selected @endif>{{ \Wallboards::getPeriodName(\Wallboards::DATE_PERIOD_YESTERDAY) }}</option>
            <option value="{{ \Wallboards::DATE_PERIOD_WEEK }}" @if (($widget['filters']['date']['period'] ?? '') == \Wallboards::DATE_PERIOD_WEEK) selected @endif>{{ \Wallboards::getPeriodName(\Wallboards::DATE_PERIOD_WEEK) }}</option>
            <option value="{{ \Wallboards::DATE_PERIOD_MONTH }}" @if (($widget['filters']['date']['period'] ?? '') == \Wallboards::DATE_PERIOD_MONTH) selected @endif>{{ \Wallboards::getPeriodName(\Wallboards::DATE_PERIOD_MONTH) }}</option>
            <option value="{{ \Wallboards::DATE_PERIOD_YEAR }}" @if (($widget['filters']['date']['period'] ?? '') == \Wallboards::DATE_PERIOD_YEAR) selected @endif>{{ \Wallboards::getPeriodName(\Wallboards::DATE_PERIOD_YEAR) }}</option>
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Mailbox') }}</label>

    <div class="col-sm-9">
        <select class="form-control wb-filter-mailbox" name="filters[mailbox][]">
            <option value=""></option>
            @foreach($mailboxes as $mailbox)
                <option value="{{ $mailbox->id }}" @if (in_array($mailbox->id, $widget['filters']['mailbox'] ?? [])) selected="selected" @endif>{{ $mailbox->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Type') }}</label>

    <div class="col-sm-9">
        <select class="form-control" name="filters[type][]">
            <option value=""></option>
            @foreach(App\Conversation::$types as $type_code => $type_name)
                <option value="{{ $type_code }}" @if (in_array($type_code, $widget['filters']['type'] ?? [])) selected="selected" @endif>{{ App\Conversation::typeToName($type_code) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Assignee') }}</label>

    <div class="col-sm-9">
        <select class="form-control" name="filters[user_id][]">
            <option value=""></option>
            @foreach(auth()->user()->whichUsersCanView() as $user)
                <option value="{{ $user->id }}" @if (in_array($user->id, $widget['filters']['user_id'] ?? [])) selected="selected" @endif>{{ $user->getFullName() }}</option>
            @endforeach
        </select>
    </div>
</div>

@if (\Module::isActive('tags'))
    <div class="form-group">
        <label class="col-sm-3 control-label">{{ __('Tag') }}</label>

        <div class="col-sm-9">
            <select class="form-control" name="filters[tag][]">
                <option value=""></option>
                @foreach(Modules\Tags\Entities\Tag::orderBy('name')->get() as $tag)
                    <option value="{{ $tag->id }}" @if (in_array($tag->id, $widget['filters']['tag'] ?? [])) selected="selected" @endif>{{ $tag->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
@endif

@if (\Module::isActive('customfields'))
    @foreach($mailboxes as $mailbox)
        @php
            $mailbox_name_shown = false;
        @endphp
        @foreach($custom_fields as $custom_field)
            @if ($custom_field->mailbox_id == $mailbox->id)
                @if (!$mailbox_name_shown)
                    <div class="form-group wb-mailbox-filter" data-mailbox-id="{{ $mailbox->id }}">
                        <div class="col-sm-9 col-sm-offset-3">
                            <strong>{{ $mailbox->name }}</strong>
                        </div>
                    </div>
                    @php
                        $mailbox_name_shown = true;
                    @endphp
                @endif
                <div class="form-group wb-mailbox-filter" data-mailbox-id="{{ $mailbox->id }}">
                    <label class="col-sm-3 control-label">{{ $custom_field->name }}</label>

                    {{--<div class="col-xs-2 col-sm-2">
                        <select class="form-control input-sized-sm" name="filters[custom_field][op][{{ $custom_field->id }}]">
                            <option value="=" {{ ($custom_field->op == '=') ? 'selected' : '' }}>=</option>
                            <option value="&lt;" {{ ($custom_field->op == '<') ? 'selected' : '' }}>&lt;</option>
                            <option value="&gt;" {{ ($custom_field->op == '>') ? 'selected' : '' }}>&gt;</option>
                            <option value="&lt;=" {{ ($custom_field->op == '<=') ? 'selected' : '' }}>&lt;=</option>
                            <option value="&gt;=" {{ ($custom_field->op == '>=') ? 'selected' : '' }}>&gt;=</option>
                        </select>
                    </div>
                    <div class="col-xs-10 col-sm-7">--}}
                    <div class="col-sm-9">
                        @if ($custom_field->type == CustomField::TYPE_DROPDOWN)
                            <select class="form-control" name="filters[custom_field][{{ $custom_field->id }}][value]">
                                <option value=""></option>
                                @foreach($custom_field->options as $option_key => $option_name)
                                    <option value="{{ $option_key }}" {{ (($widget['filters']['custom_field'][$custom_field->id]['value'] ?? '') == $option_key) ? 'selected' : '' }}>{{ $option_name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input name="filters[custom_field][{{ $custom_field->id }}][value]" class="form-control @if ($custom_field->type == CustomField::TYPE_DATE) wb-type-date @endif" value="{{ $widget['filters']['custom_field'][$custom_field->id]['value'] ?? '' }}"
                                @if ($custom_field->type == CustomField::TYPE_NUMBER)
                                    type="number"
                                @else
                                    type="text"
                                @endif
                            />
                        @endif
                    </div>
                </div>
            @endif

        @endforeach
    @endforeach
@endif