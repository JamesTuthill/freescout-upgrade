<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <input type="hidden" name="settings[dummy]" value="1" />

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Filter by Subject") }}</label>

        <div class="col-sm-6">
            <textarea name="settings[spamfilter.subject]" class="form-control" rows="3" placeholder="{{ __('One phrase per line') }}">{{ old('settings[spamfilter.subject]', $settings['spamfilter.subject']) }}</textarea>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Filter by Body") }}</label>

        <div class="col-sm-6">
            <textarea name="settings[spamfilter.body]" class="form-control" rows="3" placeholder="{{ __('One phrase per line') }}">{{ old('settings[spamfilter.body]', $settings['spamfilter.body']) }}</textarea>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Filter by Sender Email") }}</label>

        <div class="col-sm-6">
            <textarea name="settings[spamfilter.sender]" class="form-control" rows="3" placeholder="{{ __('One phrase per line') }}">{{ old('settings[spamfilter.sender]', $settings['spamfilter.sender']) }}</textarea>
            <p class="form-help">
                {{ __("Use an asterisk (*) where you want any number of any symbols") }} ({{ __("asterisk can't be used in other filters") }}):<br/>
                <blockquote class="text-help">john@example.org<br/>
                john*@example.org<br/>
                *@example.org</blockquote>
            </p>
        </div>
    </div>

    <div class="form-group">
        <label for="spamfilter_auto" class="col-sm-2 control-label">{{ __("Filter Using Spam-Filtering Algorithm & Learning") }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[spamfilter.auto]" value="1" id="spamfilter_auto" class="onoffswitch-checkbox" @if (old('settings[spamfilter.auto]', $settings['spamfilter.auto']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="spamfilter_auto"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>

    @if (count($mailboxes))
        <div class="form-group margin-top">
            <label class="col-sm-2 control-label">{{ __("Learning Memory") }}</label>

            <div class="col-sm-6">
                <p class="block-help">
                    {{ __("When you are marking conversations as spam or non-spam the Spam Filter learns from your actions and stores the information in the learning memory for each mailbox.") }}
                </p>
                <select class="form-control" size="4" id="stat-memory">
                    @foreach($mailboxes as $mailbox)
                        <option value="{{ $mailbox['id'] }}" @if ($loop->index == 0) selected @endif>{{ $mailbox['name'] }} ({{ $mailbox['size'] }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group margin-top margin-bottom">
            <div class="col-sm-6 col-sm-offset-2">
                <button type="button" class="btn btn-default" id="spam-filter-reset" data-loading-text="{{ __('Reset Learning Memory of the Selected Mailbox') }}">
                    {{ __('Reset Learning Memory of the Selected Mailbox') }}
                </button>
            </div>
        </div>
    @endif
</form>

@section('javascript')
    @parent
    initSpamFilterSettings();
@endsection