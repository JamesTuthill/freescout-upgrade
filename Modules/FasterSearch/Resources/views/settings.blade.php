<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    @if ($auth_error)
        <div class="alert alert-danger">
            <strong>{{ __('Meilisearch API authentication error') }}</strong><br/>{{ $auth_error }}
        </div>
    @endif

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Status') }}</label>
        <div class="col-sm-6">
            <label class="control-label">
                @if (\FasterSearch::isSearchEnabled())
                    <strong class="text-success"><i class="glyphicon glyphicon-ok"></i> {{ __('Active') }}</strong>
                @else
                    <strong class="text-warning">{{ __('Inactive') }}</strong>
                @endif
            </label>
            @if (\Option::get('fastersearch.active'))
                <pre class="margin-bottom-0 margin-top-10 input-sized-lg">{{ $health }}</pre>
            @endif
            @if ($last_log_message)
                <div class="margin-top-10 text-help">{{ __('Last log message') }} (<a href="{{ route('logs', ['name' => \FasterSearch::LOG_NAME]) }}" target="_blank">{{ __('View log') }}</a>):</div>
                <pre class="margin-bottom-0 margin-top-5 input-sized-lg alert alert-warning">[{{ App\User::dateFormat($last_log_message->created_at) }}] {{ $last_log_message->description }}</pre>
            @endif
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Meilisearch URL') }}</label>
        <div class="col-sm-6">
            <input type="url" class="form-control input-sized-lg" name="settings[fastersearch.url]" value="{{ $settings['fastersearch.url'] }}" placeholder="https://ms-1234567.sfo.meilisearch.io">
            {{--@if ($settings['fastersearch.url'])
                <p class="form-help">
                    <a href="{{ $settings['fastersearch.url'] }}/health">{{ __('Check health') }}</a>
                </p>
            @endif--}}
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Admin API Key') }}</label>
        <div class="col-sm-6">
            <input type="password" class="form-control input-sized-lg" name="settings[fastersearch.api_key]" value="{{ $settings['fastersearch.api_key'] }}" autocomplete="new-password">
        </div>
    </div>

    @if (\Option::get('fastersearch.index_created'))
        <div class="form-group">
            <label class="col-sm-2 control-label">{{ __('Max Conversations In Search Results') }}</label>
            <div class="col-sm-6">
                <input type="text" class="form-control input-sized-lg" name="settings[fastersearch.max_total_hits]" value="{{ $settings['fastersearch.max_total_hits'] }}" maxlength="10">
                <p class="form-help">
                    {{ __('This value determines the maximum number of conversations which may be shown in the search results. The larger this value the slower the search.') }}
                </p>
            </div>
        </div>
    @endif

    @if (\Option::get('fastersearch.active'))
        <div class="form-group">

            <div class="col-sm-6 col-sm-offset-2">
                {{--<strong>{{ __('Meilisearch Health') }}</strong>
                <pre>{{ $health }}</pre>--}}

                <div><strong>{{ __('Threads Indexing') }}</strong></div>

                <p class="margin-top-10">
                    @if (\Option::get('fastersearch.index_created'))
                        <p>
                            <strong class="@if ($indexed_threads < $total_threads) text-warning @else text-success @endif">{{ $indexed_threads }}</strong> / {{ $total_threads }}</strong> <a href="?fs_rebuild_index=1" class="btn btn-xs btn-bordered margin-left-10">{{ __('Rebuild Index') }}</a>
                        </p>
                        <p>
                            <span class="text-help">{{ __('Threads Updating Queue') }}:</span> {{ count(\Option::get('fastersearch.index_queue', [])) }}
                            &nbsp;&nbsp;&nbsp;
                            <span class="text-help ">{{ __('Threads Deleting Queue') }}:</span> {{ count(\Option::get('fastersearch.index_delete_queue', [])) }}
                        </p>
                    @else
                        <span class="text-danger">{{ __('Index not created in Meilisearch') }}</span>
                        @if ($index_error)
                            <div class="alert alert-danger">
                                <strong>{{ __('Could not create an index in Meilisearch') }}</strong><br/>{{ $index_error }}
                            </div>
                        @endif
                    @endif
                </p>
            </div>
        </div>
    @endif

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
