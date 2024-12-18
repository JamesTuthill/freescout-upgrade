<div class="row-container">
	<form class="form-horizontal wb-widget-form" method="POST" action="" data-widget-id="{{ $widget['id'] ?? '' }}">

		@include('wallboards::partials/widget_form')

		@if ($wallboard->userCanUpdate())
			<div class="form-group margin-top margin-bottom-10">
		        <div class="col-sm-10 col-sm-offset-2">
		            <button class="btn btn-primary wb-widget-submit" data-loading-text="{{ __('Save') }}…">{{ __('Save') }}</button>
		            @if ($mode == 'update')
		            	<a href="#" id="wb-delete-widget" class="btn btn-link text-danger" data-loading-text="{{ __('Delete Widget') }}…">{{ __('Delete Widget') }}</a>
		            @endif
		        </div>
		    </div>
		@endif
	</form>
</div>