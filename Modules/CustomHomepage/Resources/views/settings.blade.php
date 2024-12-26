<form class="form-horizontal margin-top margin-bottom" method="POST" action="" enctype="multipart/form-data">
    {{ csrf_field() }}

    <div class="form-group">
        <label for="url" class="col-sm-2 control-label">{{ __('Dashboard Address') }}</label>
        <div class="col-sm-6">
            <div class="input-group">
                <span class="input-group-addon input-group-addon-grey">/</span>
                <input type="text" class="form-control" name="settings[customhomepage.dashboard_path]" value="{{ $settings['customhomepage.dashboard_path'] }}" id="ch-dashboard-path" placeholder="{{ __('Only latin characters or numbers') }}">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="url" class="col-sm-2 control-label">{{ __('Login Address') }}</label>
        <div class="col-sm-6">
            <div class="input-group">
                <span class="input-group-addon input-group-addon-grey">/</span>
                <input type="text" class="form-control" id="ch-login-path" name="settings[customhomepage.login_path]" value="{{ $settings['customhomepage.login_path'] }}" placeholder="{{ __('(optional)') }}">
            </div>
        </div>
    </div>

    <div id="ch-options" @if (empty($settings['customhomepage.dashboard_path'])) class="hidden" @endif>
        <div class="form-group">
            <label for="url" class="col-sm-2 control-label">{{ __('Homepage') }}</label>
            <div class="col-sm-6">
                <select class="form-control" id="ch-type">
                    <option value="page">{{ __('Custom page') }}</option>
                    <option value="redirect" @if (!empty($settings['customhomepage.homepage_redirect'])) selected @endif>{{ __('Redirect to custom URL') }}</option>
                </select>
            </div>
        </div>

        <div class="form-group ch-homepage-redirect @if (empty($settings['customhomepage.homepage_redirect'])) hidden @endif">
            <label for="url" class="col-sm-2 control-label"></label>
            <div class="col-sm-6">
                <input type="url" class="form-control" name="settings[customhomepage.homepage_redirect]" value="{{ $settings['customhomepage.homepage_redirect'] }}" id="ch-homepage-redirect" placeholder="{{ __('Redirect URL') }}">
            </div>
        </div>

        <div class="form-group ch-homepage-page @if (!empty($settings['customhomepage.homepage_redirect'])) hidden @endif">
            <label for="" class="col-sm-2 control-label"></label>

            <div class="col-sm-6">
                @php
                    $homepage_html = $settings['customhomepage.homepage_html'];
                    if (!$homepage_html || $homepage_html == '<div><br></div>') {
                        $homepage_html = '<br/><br/><br/><div class="banner">
                            <img alt="" src="'.\Eventy::filter('login.banner', asset('img/banner.png')).'">
                            </div>
                            <h3 style="text-align: center;">
                            <a href="'.route('login').'">'.__('Login').'</a></h3>';
                    }
                @endphp
                <textarea id="ch_homepage_html" class="form-control" name="settings[customhomepage.homepage_html]" rows="17">{{ old('settings[customhomepage.homepage_html]', $homepage_html) }}</textarea>
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
</form>

@include('partials/editor')

@section('javascript')
    @parent
    chInit();
@endsection