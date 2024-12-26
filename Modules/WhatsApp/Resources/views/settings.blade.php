@extends('layouts.app')

@section('title_full', __('WhatsApp').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading margin-bottom">
        {{ __('WhatsApp') }}
    </div>

    <div class="col-xs-12">
  
        @include('partials/flash_messages')

        <form class="form-horizontal margin-bottom" method="POST" action="" autocomplete="off">
            {{ csrf_field() }}

            <div class="form-group">
                <label class="col-sm-2 control-label"></label>

                <div class="col-sm-6">
                    <label class="control-label">
                        <a href="https://freescout.net/module/whatsapp/" target="_blank">{{ __('Instructions') }} <small class="glyphicon glyphicon-share"></small></a>
                    </label>
                <p class="form-help">
                    {!! __('API errors can be found in :%a_begin%Manage » Logs » WhatsApp Errors:%a_end%.', ['%a_begin%' => '<a href="'.route('logs', ['name' => \WhatsApp::LOG_NAME]).'" target="_blank">', '%a_end%' => '</a>']) !!}
                </p>
                </div>
            </div>

            <div class="form-group{{ $errors->has('auto_reply_enabled') ? ' has-error' : '' }}">
                <label for="settings_enabled" class="col-sm-2 control-label">{{ __('Enabled') }}</label>

                <div class="col-sm-6">
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="settings[enabled]" value="1" id="settings_enabled" class="onoffswitch-checkbox" @if (!empty($settings['enabled']))checked="checked"@endif >
                                <label class="onoffswitch-label" for="settings_enabled"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-6 col-sm-offset-2">
                    <label for="settings_system_{{ \WhatsApp::SYSTEM_CHATAPI }}" class="radio inline plain">
                        <input type="radio" name="settings[system]" value="{{ \WhatsApp::SYSTEM_CHATAPI }}" id="settings_system_{{ \WhatsApp::SYSTEM_CHATAPI }}" @if (\WhatsApp::getSystem($settings) == \WhatsApp::SYSTEM_CHATAPI ) checked @endif> <strong>1msg.io</strong>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Channel ID') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[instance]" value="{{ $settings['instance'] ?? '' }}" >
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Token') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[token]" value="{{ $settings['token'] ?? '' }}" >
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-6 col-sm-offset-2">
                    <label for="settings_system_{{ \WhatsApp::SYSTEM_TWILIO }}" class="radio inline plain">
                        <input type="radio" name="settings[system]" value="{{ \WhatsApp::SYSTEM_TWILIO }}" id="settings_system_{{ \WhatsApp::SYSTEM_TWILIO }}" @if (\WhatsApp::getSystem($settings) == \WhatsApp::SYSTEM_TWILIO ) checked @endif> <strong>Twilio</strong>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Webhook URL') }}</label>

                <div class="col-sm-6">
                    <label class="control-label text-help">
                        <small>{{ \WhatsApp::getWebhookUrl($mailbox->id, \WhatsApp::SYSTEM_TWILIO) }}</small>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Account SID') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[twilio_sid]" value="{{ $settings['twilio_sid'] ?? '' }}" >
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Auth Token') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[twilio_token]" value="{{ $settings['twilio_token'] ?? '' }}" >
                </div>
            </div>
            
            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Twilio Phone Number') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[twilio_phone_number]" value="{{ $settings['twilio_phone_number'] ?? '' }}" >
                </div>
            </div>

            <div class="form-group margin-top">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection