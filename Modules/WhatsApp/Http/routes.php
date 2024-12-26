<?php

// Webhook.
Route::group([/*'middleware' => 'web', */'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\WhatsApp\Http\Controllers'], function()
{
    //Route::get('/', 'WhatsAppController@index');
    Route::match(['get', 'post'], '/whatsapp/webhook/{mailbox_id}/{mailbox_secret}/{system?}', 'WhatsAppController@webhooks')->name('whatsapp.webhook');
});

// Admin.
Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\WhatsApp\Http\Controllers'], function()
{
    Route::get('/mailbox/{mailbox_id}/whatsapp', ['uses' => 'WhatsAppController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('mailboxes.whatsapp.settings');
    Route::post('/mailbox/{mailbox_id}/whatsapp', ['uses' => 'WhatsAppController@settingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
});