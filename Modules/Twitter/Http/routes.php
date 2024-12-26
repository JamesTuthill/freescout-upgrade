<?php

// Webhook.
Route::group([/*'middleware' => 'web', */'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Twitter\Http\Controllers'], function()
{
    Route::match(['get', 'post'], '/twitter/webhook/{mailbox_id}/{mailbox_secret}', 'TwitterController@webhooks')->name('twitter.webhook');
});

// Admin.
Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Twitter\Http\Controllers'], function()
{
    Route::get('/mailbox/{mailbox_id}/twitter', ['uses' => 'TwitterController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('mailboxes.twitter.settings');
    Route::post('/mailbox/{mailbox_id}/twitter', ['uses' => 'TwitterController@settingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
});