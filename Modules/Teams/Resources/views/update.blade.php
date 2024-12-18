@extends('layouts.app')

@if ($mode == 'create')
	@section('title', __('New Team'))
@else
	@section('title', __('Team').' - '.$team->first_name)
	@section('body_attrs')@parent data-team_id="{{ $team->id }}"@endsection
@endif

@section('content')
<div class="container">
	<div class="row">
	    <div class="col-md-8 col-md-offset-2">
	        <div class="panel panel-default panel-wizard">
	            <div class="panel-body">
	            	<div class="wizard-header">
	            		@if ($mode == 'create')
		            		<h1>{{ __('Create a New Team') }}</h1>
		            	@else
		            		<h1>{{ __('Team') }}</h1>
		            	@endif
		            </div>
		            <div class="wizard-body">
					 	@include('partials/flash_messages')

				        <div class="row">
				            <div class="col-xs-12">
				                <form class="form-horizontal margin-top" method="POST" action="" enctype="multipart/form-data">
				                    {{ csrf_field() }}

				                    <input type="hidden" name="action" value="{{ $mode }}" />

				                    <div class="form-group">
				                    	<div class="col-sm-6 col-sm-offset-4">
				                    		<a href="{{ route('teams.teams') }}" class="pull-right">« {{ __('Back to Teams') }}</a>
				                    	</div>
				                    </div>

				                    <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
				                        <label for="first_name" class="col-sm-4 control-label">{{ __('Team Name') }}</label>

				                        <div class="col-sm-6">
				                            <input id="first_name" type="text" class="form-control" name="first_name" value="{{ old('first_name', $team->first_name) }}" maxlength="20" required autofocus>

				                            @include('partials/field_error', ['field'=>'first_name'])
				                        </div>
				                    </div>
                                    
				                    <div class="form-group{{ $errors->has('photo_url') ? ' has-error' : '' }}">
				                        <label for="photo_url" class="col-sm-4 control-label">{{ __('Icon') }}</label>

				                        <div class="col-sm-6">
									        <select name="icon" class="form-control">
									            <option value="">{{ ucwords(str_replace('-', ' ', \Team::DEFAULT_ICON)) }}</option>
									            @foreach(config('teams.icons') as $icon)
									            	@if ($icon != \Team::DEFAULT_ICON)
									                	<option value="{{ $icon }}" {{ ($team->photo_url == $icon)? 'selected' : '' }}>{{ ucwords(str_replace('-', ' ', $icon)) }}</option>
									                @endif
									            @endforeach
									        </select>
									        <div class="form-help">
									            <a href="https://glyphicons.bootstrapcheatsheets.com/" target="_blank">{{ __('Icons') }}</a>
									        </div>
				                        </div>
				                    </div>

				                    {{--<div class="form-group{{ $errors->has('photo_url') ? ' has-error' : '' }}">
				                        <label for="photo_url" class="col-sm-4 control-label">{{ __('Team Image') }}</label>

				                        <div class="col-sm-6">
				                            <div class="controls">
				                                @if ($team && $team->photo_url)
				                                    <div id="team-image">
				                                        <img src="{{ $team->getPhotoUrl() }}" alt="{{ __('Team Image') }}" width="50" height="50"><br/>
				                                        <a href="#" id="team-image-delete" data-loading-text="{{ __('Delete') }}…">{{ __('Delete') }}</a>
				                                    </div>
				                                @endif

				                                <input type="file" name="photo_url">
				                                <p class="block-help">{{ __('Image will be re-sized to :dimensions. JPG, GIF, PNG accepted.', ['dimensions' => '50x50']) }}</p>
				                            </div>
				                            @include('partials/field_error', ['field'=>'photo_url'])
				                        </div>
				                    </div>--}}

				                    @if (count($members) || count($users))
					                    <div class="form-group{{ $errors->has('users') ? ' has-error' : '' }} margin-bottom-0">
					                        <label for="users" class="col-sm-4 control-label">{{ __('Members') }}</label>

					                        <div class="col-sm-6 control-padded">

					                            <fieldset id="team-members">
							                        @foreach ($members as $member)
							                            <div class="control-group">
							                                <div class="controls">
							                                    <label class="control-label checkbox" for="member-{{ $member->id }}">
							                                        <input type="checkbox" name="members[]" id="member-{{ $member->id }}" value="{{ $member->id }}" @if ((is_array(old('members')) && in_array($member->id, old('members'))) || !is_array(old('members'))) checked="checked" @endif> {{ $member->getFullName() }}
							                                    </label>
							                                </div>
							                            </div>
							                        @endforeach
							                        @foreach ($users as $user)
							                            <div class="control-group">
							                                <div class="controls">
							                                    <label class="control-label checkbox" for="user-{{ $user->id }}">
							                                        <input type="checkbox" name="members[]" id="user-{{ $user->id }}" value="{{ $user->id }}" @if (is_array(old('members')) && in_array($user->id, old('members'))) checked="checked" @endif> {{ $user->getFullName() }}
							                                    </label>
							                                </div>
							                            </div>
							                        @endforeach
							                    </fieldset>

					                            @include('partials/field_error', ['field'=>'members'])
					                        </div>
					                    </div>

										<div class="col-sm-6 col-sm-offset-4">
											<hr class="form-divider margin-top margin-bottom">
										</div>
				                    @endif

				                    @if (count($mailboxes))
					                    <div class="form-group{{ $errors->has('users') ? ' has-error' : '' }} margin-bottom-0">
					                        <label for="users" class="col-sm-4 control-label">{{ __('Mailbox Access') }}</label>

					                        <div class="col-sm-6 control-padded">
					                            <div><a href="#" class="sel-all">{{ __('all') }}</a> / <a href="#" class="sel-none">{{ __('none') }}</a></div>

					                            <fieldset id="permissions-fields" class="team-mailboxes">
							                        @foreach ($mailboxes as $mailbox)
							                            <div class="control-group">
							                                <div class="controls">
							                                    <label class="control-label checkbox" for="mailbox-{{ $mailbox->id }}">
							                                        <input type="checkbox" name="mailboxes[]" id="mailbox-{{ $mailbox->id }}" value="{{ $mailbox->id }}" @if ((is_array(old('mailboxes')) && in_array($mailbox->id, old('mailboxes'))) || (!is_array(old('mailboxes')) && $team_mailboxes->contains($mailbox))) checked="checked" @endif> {{ $mailbox->name }}
							                                    </label>
							                                </div>
							                            </div>
							                        @endforeach
							                    </fieldset>

					                            @include('partials/field_error', ['field'=>'mailboxes'])
					                        </div>
					                    </div>

										<div class="col-sm-6 col-sm-offset-4">
											<hr class="form-divider margin-top margin-bottom">
										</div>
				                    @endif

				                    <div class="form-group">
				                        <div class="col-sm-6 col-sm-offset-4">
				                            <button type="submit" class="btn btn-primary">
				                            	@if ($mode == 'create')
				                                	{{ __('Create Team') }}
				                                @else
				                                	{{ __('Save') }}
				                                @endif
				                            </button>

				                            <a href="{{ route('teams.teams') }}" class="btn btn-link">{{ __('Cancel') }}</a>

				                            @if ($mode != 'create')
				                            	<a href="#" id="team-delete" class="btn btn-link text-danger">{{ __('Delete') }}</a>
				                            @endif
				                        </div>
				                    </div>

				                </form>
				            </div>
				        </div>
					    
	                </div>
	                <div class="wizard-footer">
	                	
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
</div>

@if ($mode != 'create')
	<div id="team_delete_modal" class="hidden">
        <div>
	        <div class="text-center">
	            <div class="col-sm-10 col-sm-offset-1 text-large margin-top-10 margin-bottom">{!! __("Deleting :name team will assign it's conversations to:", ['name' => '<strong>'.htmlspecialchars($team->first_name).'</strong>']) !!}</div>
	            <form class="team-assign-form form-horizontal">
	                @foreach (App\Mailbox::all() as $mailbox)
	                    <div class="col-sm-9 col-sm-offset-1">
	                        <div class="form-group">
	                            <label class="col-sm-5 control-label">{{ $mailbox->name }}</label>
	                            <div class="col-sm-7">
	                                <select name="assign_user[{{ $mailbox->id }}]" class="form-control input-sized">
	                                    <option value="-1">{{ __("Anyone") }}</option>
	                                    @foreach ($mailbox->usersHavingAccess() as $assign_user)
	                                        @if ($assign_user->id != $team->id)
	                                            <option value="{{ $assign_user->id }}">{{ $assign_user->getFullName() }}</option>
	                                        @endif
	                                    @endforeach
	                                </select>
	                            </div>
	                        </div>
	                    </div>
	                @endforeach
	            </form>
	            <div class="col-sm-12 text-large margin-top">{!! __("If you are sure, type :delete and click the red button.", ['delete' => '<span class="text-danger">DELETE</span>']) !!}</div>
	            <div class="col-sm-6 col-sm-offset-3 margin-top-10 margin-bottom">
	                <div class="input-group">
	                    <input type="text" class="form-control input-delete-user" placeholder="{!! __("Type :delete", ['delete' => '&quot;DELETE&quot;']) !!}">
	                    <span class="input-group-btn">
	                        <button class="btn btn-danger button-delete-team" disabled="disabled"><i class="glyphicon glyphicon-ok"></i></button>
	                    </span>
	                </div>
	            </div>
	            <div class="clearfix"></div>
	        </div>
        </div>
    </div>

@endif
@endsection

@section('javascript')
    @parent
    teamUpdateInit();
    permissionsInit();
@endsection