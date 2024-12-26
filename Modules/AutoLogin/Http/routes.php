<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AutoLogin\Http\Controllers'], function()
{
    Route::get('/', 'AutoLoginController@index');
});
