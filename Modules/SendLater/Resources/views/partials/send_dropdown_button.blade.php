<li class="divider"></li>
<li>
	<input type="hidden" name="sl_schedule_date" value=""/>
	<a href="@if ($conversation_id){{ route('sendlater.ajax_html', ['action' => 'schedule', 'conversation_id' => $conversation_id]) }}@endif" id="sl-modal-trigger" data-trigger="modal" data-modal-title="{{ __('Schedule send') }}" data-modal-no-footer="true" data-modal-on-show="slInitScheduleModal" data-modal-size="sm">{{ __('Schedule send') }}…</a>
</li>