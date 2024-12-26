<div class="row">

	<form method="POST" action="" class="snooze-form col-xs-12">

		<div class="form-group">
			<small class="text-help"><strong>{{ __('Current date & time') }}</strong>:<br/>{{ App\User::dateFormat(new Illuminate\Support\Carbon()) }} (GMT{{ App\User::dateFormat(new Illuminate\Support\Carbon(), 'O') }})</small>
		</div>

		<div class="form-group margin-top">

			<div class="control-group">
				{{ __('Snooze for') }} 
				<div class="input-group input-group-flex input-sized-sm">
					<input type="number" class="form-control snooze-number" value="5" min="1"> 
					<select class="form-control snooze-period">
						<option value="minutes">{{ __('minute(s)') }} </option>
						<option value="hours">{{ __('hour(s)') }} </option>
						<option value="days">{{ __('day(s)') }} </option>
						<option value="weeks">{{ __('week(s)') }} </option>
						<option value="months">{{ __('month(s)') }} </option>
						<option value="years">{{ __('year(s)') }} </option>
					</select>
					<span class="input-group-btn">
						<button class="btn btn-primary snooze-apply" type="button" data-toggle="tooltip" title="{{ __('Apply') }}" data-loading-text="..."><small class="glyphicon glyphicon-ok"></small></button>
					</span>
				</div>
			</div>

			<div class="control-group margin-top">
				<label>{{ __('Pick date & time') }}</label>

				<div class="input-group input-group-flex  input-sized-sm">
					<input type="text" class="form-control snooze-datetime">
					<span class="input-group-btn">
						<button class="btn btn-default snooze-datetime-trigger" type="button"><i class="glyphicon glyphicon-calendar"></i></button>
					</span>
				</div>
			</div>
		</div>

		<div class="form-group margin-top margin-bottom-10">
	        <button class="btn btn-primary snooze-btn" type="button" data-loading-text="{{ __('Snooze') }}…" disabled>{{ __('Snooze') }}</button>
	        <button class="btn btn-link snooze-cancel" type="button">{{ __('Cancel') }}</button>
	    </div>

	</form>

</div>

@include('partials/include_datepicker')