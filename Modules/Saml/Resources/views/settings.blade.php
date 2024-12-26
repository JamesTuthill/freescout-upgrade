<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

	 <div class="form-group{{ $errors->has('settings[saml.enabled]') ? ' has-error' : '' }}">
        <label for="saml_enabled" class="col-sm-2 control-label">{{ __('Enabled') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.enabled]" value="on" id="saml_enabled" class="onoffswitch-checkbox" @if (old('settings[saml.enabled]', $settings['saml.enabled'])) checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_enabled"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3 class="subheader">{{ __('Identity Provider (IdP)') }}</h3>

    <div class="form-group{{ $errors->has('settings.saml.idp_entity_id') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml.idp_entity_id" class="col-sm-2 control-label">{{ __('Entity ID') }}</label>

        <div class="col-sm-6">
            <input id="saml.idp_entity_id" type="text" class="form-control input-sized-lg"
                   name="settings[saml.idp_entity_id]" value="{{ old('settings.saml.idp_entity_id', $settings['saml.idp_entity_id']) }}" placeholder="{{ __('Also known as Issuer') }}">
            @include('partials/field_error', ['field'=>'settings.saml.idp_entity_id'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.saml.idp_signin_url') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml.idp_signin_url" class="col-sm-2 control-label">{{ __('Signin URL') }}</label>

        <div class="col-sm-6">
            <input id="saml_idp_signin_url" type="text" class="form-control input-sized-lg"
                   name="settings[saml.idp_signin_url]" value="{{ old('settings.saml.idp_signin_url', $settings['saml.idp_signin_url']) }}" placeholder="(HTTP Redirect)">
        </div>
    </div>

	<div class="form-group{{ $errors->has('settings.saml.idp_logout_url') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml.idp_logout_url" class="col-sm-2 control-label">{{ __('Logout URL') }}</label>

        <div class="col-sm-6">
            <input id="saml_idp_logout_url" type="text" class="form-control input-sized-lg"
                   name="settings[saml.idp_logout_url]" value="{{ old('settings.saml.idp_logout_url', $settings['saml.idp_logout_url']) }}" placeholder="(optional)">

            <p class="help-block">
                {{ __('This is a regular logout URL of your IdP which logs users out (not a Single Logout URL).') }}
                {{ __('Example') }}: <strong>https://manage.auth0.com/logout</strong>
            </p>
        </div>
    </div>

    <div class="form-group margin-bottom-10{{ $errors->has('settings.saml.idp_cert') ? ' has-error' : '' }}">
        <label for="saml.idp_cert" class="col-sm-2 control-label">{{ __('x509 Certificate') }}</label>

        <div class="col-sm-6">
            <textarea id="saml.idp_cert" type="text" class="form-control input-sized-lg" rows="5"
                   name="settings[saml.idp_cert]">{{ old('settings.saml.idp_cert', $settings['saml.idp_cert']) }}</textarea>
        </div>
    </div>

    <h3 class="subheader">{{ __('Service Provider (SP)') }}</h3>

    <div class="form-group margin-bottom-10">
        <label class="col-sm-2 control-label">{{ __('ACS URL') }}</label>
        <div class="col-sm-6">
            <label class="control-label">
                <a href="{{ route('saml.acs', ['secret' => \Saml::getSecret()])  }}" target="_blank">{{ route('saml.acs', ['secret' => \Saml::getSecret()])  }}</a> (HTTP POST)
            </label>
        </div>
    </div>

    <div class="form-group margin-bottom-10">
        <label class="col-sm-2 control-label">{{ __('Login URL') }}</label>
        <div class="col-sm-6">
            <label class="control-label">
                <a href="{{ route('login')  }}" target="_blank">{{ route('login') }}</a>
            </label>
        </div>
    </div>

    <div class="form-group margin-bottom-10 hidden">
        <label class="col-sm-2 control-label">{{ __('Logout URL') }}</label>
        <div class="col-sm-6">
            <label class="control-label">
                <a href="{{ route('saml.single_logout', ['secret' => \Saml::getSecret()])  }}" target="_blank">{{ route('saml.single_logout', ['secret' => \Saml::getSecret()]) }}</a> (HTTP Redirect)
            </label>
        </div>
    </div>

    <div class="form-group">
        <label for="saml.login_url" class="col-sm-2 control-label">{{ __('SP Metadata') }}</label>
        <div class="col-sm-6">
            <label class="control-label">
                <a href="{{ route('saml.sp_metadata', ['secret' => \Saml::getSecret()]) }}" target="_blank">{{ route('saml.sp_metadata', ['secret' => \Saml::getSecret()]) }}</a>
            </label>
        </div>
    </div>

    <h3 class="subheader">{{ __('Additional Settings') }}</h3>

	<div class="form-group{{ $errors->has('settings[saml.auto_create_users]') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml_auto_create_users" class="col-sm-2 control-label">{{ __('Auto-Create Users') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.auto_create_users]" value="1" id="saml_auto_create_users" class="onoffswitch-checkbox" @if (old('settings[saml.auto_create_users]', $settings['saml.auto_create_users'])) checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_auto_create_users"></label>
                    </div>
                </div>
            </div>
            <p class="form-help">
                {{ __('Automatically create non-existing users in FreeScout upon successful sign-in to SSO IdP.') }}
            </p>
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.saml.mapping') ? ' has-error' : '' }}">
        <label for="saml.mapping" class="col-sm-2 control-label">{{ __('User Fields Mapping') }}</label>

        <div class="col-sm-6">

            <textarea id="saml.mapping" type="text" class="form-control input-sized-lg" rows="3"
                   name="settings[saml.mapping]">{{ old('settings.saml.mapping', $settings['saml.mapping']) }}</textarea>

            <p class="form-help">
                <a href="#saml_mapping_help" data-toggle="collapse" aria-expanded="false">{{ __('Help') }} <span class="caret"></span></a>
                <div id="saml_mapping_help" class="collapse text-help">
                    {{ __('Mapping is used to map user fields when auto-creating users. Each field should be placed on a separate line in the following format: idp_user_attribute>>freescout_user_field. IdP user attributes can be obtained from logs by enabling Debug Mode. FreeScout user fields are: :fs_fields. Mapping example:', ['fs_fields' => implode(', ', \Saml::getMappableFields()) ]) }}
                    <br/><blockquote>firstName&gt;&gt;first_name<br/>lastName&gt;&gt;last_name</blockquote>
                </div>
            </p>
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.saml.mapping') ? ' has-error' : '' }}">
        <label for="saml.auth_context" class="col-sm-2 control-label">{{ __('Authentication Context') }}</label>

        <div class="col-sm-6">

            <input type="text" class="form-control input-sized-lg" name="settings[saml.auth_context]" value="{{ old('settings.saml.auth_context', $settings['saml.auth_context']) }}" placeholder="{{ __('(optional)') }}" />

            <p class="form-help">
                {{ __('Comma separated list of authentication context classes.') }}
                {!! __('Example for Azure:') !!} <strong>urn:oasis:names:tc:SAML:2.0:ac:classes:X509</strong>
            </p>
        </div>
    </div>

	<div class="form-group{{ $errors->has('settings[saml.force_saml_login]') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml_force_saml_login" class="col-sm-2 control-label">{{ __('Force SAML Login') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.force_saml_login]" value="1" id="saml_force_saml_login" class="onoffswitch-checkbox" @if (old('settings[saml.force_saml_login]', $settings['saml.force_saml_login'])) checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_force_saml_login"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Disable standard login form and require to sign-in using SSO IdP.') }}
            </p>
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[saml.strict]') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml_strict" class="col-sm-2 control-label">{{ __('Strict Mode') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.strict]" value="1" id="saml_strict" class="onoffswitch-checkbox" @if (old('settings[saml.strict]', $settings['saml.strict'])) checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_strict"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {!! __('Reject messages if the SAML standard is not strictly followed.') !!}
            </p>
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[saml.debug]') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml_debug" class="col-sm-2 control-label">{{ __('Debug Mode') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.debug]" value="1" id="saml_debug" class="onoffswitch-checkbox" @if (old('settings[saml.debug]', $settings['saml.debug'])) checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_debug"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {!! __('Enable debug mode to see more details in :%a_begin%Manage » Logs » SAML:%a_end%.', ['%a_begin%' => '<a href="'.route('logs', ['name' => 'saml']).'" target="_blank">', '%a_end%' => '</a>']) !!}
            </p>
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
