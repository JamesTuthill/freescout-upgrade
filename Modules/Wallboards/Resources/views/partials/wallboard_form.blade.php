{{--@if ($mode == 'update' && !$wallboard->isCreatedByUser())
    <div class="form-group">
        <label class="col-sm-3 control-label">{{ __('Author') }}</label>

        <div class="col-sm-9">
            <label class="control-label text-help">
                {{ $wallboard->getCreatedByUserName() }}
            </label>
        </div>
    </div>
@endif --}}

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Name') }}</label>

    <div class="col-sm-9">
        <input class="form-control wb-wallboard-name" name="name" value="{{ $wallboard->name }}" maxlength="75" required/>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ __('Visibility') }}</label>

    <div class="col-sm-9">
    	<select class="form-control wb-wallboard-visibility" name="visibility">
    		<option  @if ($wallboard->visibility == \Wallboard::VISIBILITY_ME) selected="selected" @endif value="{{ \Wallboard::VISIBILITY_ME }}">{{ __('Visible to me only') }}</option>
    		<option @if ($wallboard->visibility == \Wallboard::VISIBILITY_ADMINS) selected="selected" @endif value="{{ \Wallboard::VISIBILITY_ADMINS }}">{{ __('Visible to me and all admins') }}</option>
    		<option @if ($wallboard->visibility == \Wallboard::VISIBILITY_ALL) selected="selected" @endif value="{{ \Wallboard::VISIBILITY_ALL }}">{{ __('Visible to all users') }}</option>
    	</select>
    </div>
</div>