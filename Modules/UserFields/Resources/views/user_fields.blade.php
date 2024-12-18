@extends('layouts.app')

@section('title', __('User Fields'))

@section('content')
	<div class="section-heading">
        {{ __('User Fields') }}<a href="{{ route('userfields.ajax_html', ['action' => 'create_user_field']) }}" class="btn btn-primary margin-left new-custom-field" data-trigger="modal" data-modal-title="{{ __('New User Field') }}" data-modal-no-footer="true" data-modal-size="lg" data-modal-on-show="ufInitNewUserField">{{ __('New User Field') }}</a>
    </div>
    @if (count($settings['user_fields']))
	    <div class="row-container">
	    	<div class="col-md-11">
				<div class="panel-group accordion margin-top" id="uf-user-fields-index">
					@foreach ($settings['user_fields'] as $user_field)
				        <div class="panel panel-default panel-sortable" id="uf-user-field-{{ $user_field->id }}" data-user-field-id="{{ $user_field->id }}">
				            <div class="panel-heading">
				            	<span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
				                <h4 class="panel-title">
				                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse-{{ $user_field->id }}">
				                    	<span>{{ $user_field->name }} <small>(ID: {{ $user_field->id }})</small>@if ($user_field->required) <i class="required-asterisk"></i>@endif</span>
				                    </a>
				                </h4>
				            </div>
				            <div id="collapse-{{ $user_field->id }}" class="panel-collapse collapse">
				                <div class="panel-body">
									<form class="form-horizontal uf-user-field-form" method="POST" action="" data-user_field_id="{{ $user_field->id }}" >

										@include('userfields::partials/user_fields_form_update', ['mode' => 'update'])

										<div class="form-group margin-top margin-bottom-10">
									        <div class="col-sm-10 col-sm-offset-2">
									            <button class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save Field') }}</button> 
									            <a href="#" class="btn btn-link text-danger uf-user-field-delete" data-loading-text="{{ __('Deleting') }}…" data-user_field_id="{{ $user_field->id }}">{{ __('Delete') }}</a>
									        </div>
									    </div>
									</form>
				                </div>
				            </div>
				        </div>
				    @endforeach
			    </div>
			</div>
		</div>
	@else
		@include('partials/empty', ['icon' => 'list-alt', 'empty_header' => __("User Fields")])
	@endif
@endsection

@section('javascript')
    @parent
    ufInitUserFieldsAdmin();
@endsection