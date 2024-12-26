<div class="row">

	<form method="POST" action="" class="sl-schedule-form col-xs-12">

			<div class="form-group">
				<small class="text-help">{{ __('Current date & time') }}:<br/>{{ App\User::dateFormat(new Illuminate\Support\Carbon()) }} (GMT{{ App\User::dateFormat(new Illuminate\Support\Carbon(), 'O') }})</small>
			</div>

			<div class="form-group margin-top">

				<div class="control-group">
					{{ __('Send In') }} 
					<div class="input-group input-group-flex input-sized-sm">
						<input type="number" class="form-control sl-schedule-number" value="5" min="1"> 
						<select class="form-control sl-schedule-period">
							<option value="minutes">{{ __('minute(s)') }} </option>
							<option value="hours">{{ __('hour(s)') }} </option>
							<option value="days">{{ __('day(s)') }} </option>
							<option value="weeks">{{ __('week(s)') }} </option>
							<option value="months">{{ __('month(s)') }} </option>
							<option value="years">{{ __('year(s)') }} </option>
						</select>
						<span class="input-group-btn">
							<button class="btn btn-primary sl-schedule-apply" type="button" data-toggle="tooltip" title="{{ __('Apply') }}" data-loading-text="..."><small class="glyphicon glyphicon-ok"></small></button>
						</span>
					</div>
				</div>

				<div class="control-group margin-top">
					<label>{{ __('Schedule Sending On') }}</label>

					<div class="input-group input-group-flex input-sized">
						<input type="text" class="form-control sl-schedule-datetime">
						<span class="input-group-btn">
							<button class="btn btn-default sl-datetime-trigger" type="button"><i class="glyphicon glyphicon-calendar"></i></button>
						</span>
					</div>
				</div>
			</div>

			<div class="form-group margin-top margin-bottom-10">
		        <button class="btn btn-primary sl-schedule-btn" type="button" data-loading-text="{{ __('Schedule') }}…" disabled>{{ __('Schedule') }}</button>
		        <button class="btn btn-link sl-schedule-cancel" type="button">{{ __('Cancel') }}</button>
		    </div>

	</form>

</div>