<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\OutOfOffice\Http\Controllers'], function()
{
    Route::get('/', 'OutOfOfficeController@index');
});
