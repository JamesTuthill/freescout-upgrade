@foreach($widgets as $widget_i => $widget)
    <div class="wb-widget" data-widget-id="{{ $widget['id'] }}">
        <div class="widget-inside">
            <div class="wb-widget-header">
                {{--<div class="wb-handle"><i class="glyphicon glyphicon-menu-hamburger"></i></div>--}}
                <div class="wb-widget-title">@if ($widget['title']){{ $widget['title'] }}@else&nbsp;@endif</div>
                <div class="wb-widget-cog" data-remote="{{ route('wallboards.ajax_html', ['action' => 'update_widget', 'wallboard_id'=>$wallboard_id, 'widget_id'=>$widget['id']]) }}" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('Edit Widget') }}" data-modal-on-show="wbInitWidgetModal"><a href="" class="link-grey"><i class="glyphicon glyphicon-cog"></i></a></div>
            </div>
            <div class="wb-widget-body">
                @if ((int)$widget['metrics_visibility'])
                    <div class="wb-widget-metrics">
                        @if (in_array(\Wallboards::METRIC_ACTIVE, $widget['metrics']))
                            <div class="wb-metric">
                                <div class="wb-metric-value text-success">{{ $widget['data']['metrics'][\Wallboards::METRIC_ACTIVE] ?? 0 }}</div>
                                <div class="wb-metric-title">{{ __('Active') }}</div>
                            </div>
                        @endif
                        @if (in_array(\Wallboards::METRIC_PENDING, $widget['metrics']))
                            <div class="wb-metric">
                                <div class="wb-metric-value text-warning">{{ $widget['data']['metrics'][\Wallboards::METRIC_PENDING] ?? 0 }}</div>
                                <div class="wb-metric-title">{{ __('Pending') }}</div>
                            </div>
                        @endif
                        @if (in_array(\Wallboards::METRIC_CLOSED, $widget['metrics']))
                            <div class="wb-metric">
                                <div class="wb-metric-value">{{ $widget['data']['metrics'][\Wallboards::METRIC_CLOSED] ?? 0 }}</div>
                                <div class="wb-metric-title">{{ __('Closed') }}</div>
                            </div>
                        @endif
                    </div>
                @endif
                @if (!empty($widget['group_by']))
                    <div class="wb-widget-table">
                        <table class="table table-narrow table-striped">
                            <tr>
                                <th>{{ $widget['data']['group_by_entity_name'] }}</th>
                                @if (in_array(\Wallboards::METRIC_ACTIVE, $widget['metrics']))
                                    <th>{{ __('Active') }}@if ($widget['sort_by'] == \Wallboards::METRIC_ACTIVE) ↓@endif</th>
                                @endif
                                @if (in_array(\Wallboards::METRIC_PENDING, $widget['metrics']))
                                    <th>{{ __('Pending') }}@if ($widget['sort_by'] == \Wallboards::METRIC_PENDING) ↓@endif</th>
                                @endif
                                @if (in_array(\Wallboards::METRIC_CLOSED, $widget['metrics']))
                                    <th>{{ __('Closed') }}@if ($widget['sort_by'] == \Wallboards::METRIC_CLOSED) ↓@endif</th>
                                @endif
                            </tr>
                            @foreach($widget['data']['table'] ?? [] as $entity_id => $row)
                                <tr>
                                    <td>{{ $row['entity_title'] }}</td>
                                    @foreach($widget['metrics'] as $metric)
                                        <td>{{ $row['metrics'][$metric] ?? 0 }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>
            @if (!empty($widget['filters']['date']['period']))
                <div class="wb-widget-footer">
                    <span class="text-help">{{ __('Date') }}: <strong>{{ \Wallboards::getPeriodName($widget['filters']['date']['period']) }}</strong></span>
                    {{--<a href="{{ \Wallboards::conversationsLink($widget) }}" class="wb-conv-list-link" target="_blank">{{ __('View Conversations') }} →</a>--}}
                </div>
            @endif
        </div>
    </div>
@endforeach

@if ($wallboard && $wallboard->userCanUpdate())
    <div class="wb-widget wb-widget-new link-grey-blue" data-remote="{{ route('wallboards.ajax_html', ['action' => 'new_widget', 'wallboard_id'=>$wallboard_id]) }}" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('Add Widget') }}" data-modal-on-show="wbInitWidgetModal">
        <i class="glyphicon glyphicon-plus-sign" data-toggle="tooltip" title="{{ __('Add Widget') }}"></i>
    </div>
@endif