<div class="row-container">
	<form class="form-horizontal new-folder-form" method="POST" action="">

		@include('customfolders::partials/form_update', ['mode' => 'create'])

		<div class="form-group margin-top margin-bottom-10">
	        <div class="col-sm-10 col-sm-offset-2">
	            <button class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save') }}</button>
	        </div>
	    </div>
	</form>
</div>