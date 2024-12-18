<div class="row-container">
	<form class="form-horizontal wb-wallboard-form" method="POST" action="" data-wallboard-id="{{ $wallboard->id }}">

		@include('wallboards::partials/wallboard_form')

		<div class="form-group margin-top margin-bottom-10">
	        <div class="col-sm-10 col-sm-offset-2">
	            <button class="btn btn-primary" data-loading-text="{{ __('Save') }}…">{{ __('Save') }}</button>
	        </div>
	    </div>
	</form>
</div>