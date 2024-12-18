@extends('layouts.app')

@section('title', __('Teams'))

@section('content')
	<div class="container">
	    <div class="flexy-container">
	        <div class="flexy-item">
	            <span class="heading">{{ __('Teams') }}</span>
	        </div>
	        <div class="flexy-item margin-left">
	            <a href="{{ route('teams.create') }}" class="btn btn-bordered">{{ __('New Team') }}</a>
	        </div>
	        <div class="flexy-block"></div>
	    </div>

	    <div id="users-list" class="card-list margin-top">
	        @foreach ($teams as $team)
	            <a href="{{ route('teams.update', ['id'=>$team->id]) }}" class="card hover-shade">
	            	<i class="card-icon glyphicon glyphicon-{{ \Team::getIcon($team) }}"></i>
	                <h4 class="user-q">{{ $team->first_name }}</h4>
	                <p class="text-truncate user-q">{{ __('Members') }}: {{ \Team::getMembersCount($team)}}</p>
	            </a>
	        @endforeach
	    </div>
	    
	</div>
@endsection