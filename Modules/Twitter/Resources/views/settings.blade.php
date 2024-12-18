@extends('layouts.app')

@section('title_full', __('Twitter').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading margin-bottom">
        {{ __('Twitter') }}
    </div>

    <div class="col-xs-12">
  
        @include('partials/flash_messages')

        <form class="form-horizontal margin-bottom" method="POST" action="" autocomplete="off">
            {{ csrf_field() }}

            <div class="form-group">
                <label class="col-sm-2 control-label"></label>

                <div class="col-sm-6">
                    <label class="control-label">
                        <a href="https://freescout.net/module/twitter/" target="_blank">{{ __('Instruction') }} <small class="glyphicon glyphicon-share"></small></a>
                    </label>
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
                <label class="col-sm-2 control-label">{{ __('Consumer API Key') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[consumer_key]" value="{{ $settings['consumer_key'] ?? '' }}" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Consumer API Key Secret') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[consumer_secret]" value="{{ $settings['consumer_secret'] ?? '' }}" required>
                </div>
            </div>       
                 
            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Access Token') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[token]" value="{{ $settings['token'] ?? '' }}" required>
                </div>
            </div>                 

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Access Token Secret') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[token_secret]" value="{{ $settings['token_secret'] ?? '' }}" required>
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