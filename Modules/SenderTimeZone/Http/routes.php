<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\SenderTimeZone\Http\Controllers'], function()
{
    Route::get('/sender-time-zone/modal/{thread_id}', ['uses' => 'SenderTimeZoneController@modal', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('sendertimezone.modal');
});
