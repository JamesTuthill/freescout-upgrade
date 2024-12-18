<form method="POST" action="" class="followers-add-form">

	@foreach ($users as $user)
		<div class="checkbox">
			<label>
				<input type="checkbox" class="follower-add-user" value="{{ $user->id }}"> <span class="followers-add-name">{{ $user->getFullName() }}</span>
			</label>
		</div>
	@endforeach

	<div class="checkbox margin-top margin-bottom-10">
        <button class="btn btn-primary" data-loading-text="{{ __('Adding') }}…">{{ __('Add') }}</button>
        <button class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
    </div>
</form>
