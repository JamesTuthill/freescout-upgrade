<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Inbox\Http\Controllers'], function()
{
    Route::get('/', 'InboxController@index');
});
