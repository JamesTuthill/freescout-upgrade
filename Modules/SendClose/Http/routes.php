<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\SendClose\Http\Controllers'], function()
{
    Route::get('/', 'SendCloseController@index');
});
