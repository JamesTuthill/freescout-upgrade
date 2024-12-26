<h3 class="subheader">{{ __('Attachment Reminder') }}</h3>

<div class="form-group">
    <label for="email_user_history" class="col-sm-2 control-label">{{ __('Phrases') }}</label>

    <div class="col-sm-6">
        <textarea name="settings[extendedattachments.reminder_phrases]" class="form-control input-sized-lg" rows="4" placeholder="{{ __('One phrase per line') }}">{{ old('settings[extendedattachments.reminder_phrases]', $settings['extendedattachments.reminder_phrases']) }}</textarea>
    </div>
</div>