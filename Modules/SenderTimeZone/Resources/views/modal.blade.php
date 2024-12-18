<div class="form-horizontal">

	<div class="row">
	    <label class="col-xs-4 control-label">{{ __('Sent at') }}</label>
	    <div class="col-xs-8">
	    	<label class="control-label">
				<strong>{{ $data['date'] }} (GMT{{ $data['tz'] }})</strong>
			</label>
		</div>
	</div>

	<div class="row">
	    <label class="col-xs-4 control-label">{{ __('Current Time') }}</label>
	    <div class="col-xs-8">
	    	<label class="control-label">
				<strong>{{ $data['sender_time'] }}</strong>
			</label>
		</div>
	</div>

	<div class="row margin-top">
		<div class="col-xs-12">
			<iframe src="https://dayspedia.com/time-zone-map/{{ $map_hours }}/" width="100%" height="400" frameborder="0"></iframe>
		</div>
	</div>
</div>