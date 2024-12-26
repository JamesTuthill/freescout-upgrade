<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\MailboxIcons\Http\Controllers'], function()
{
    Route::get('/', 'MailboxIconsController@index');
});
