<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\ExtendedEditor\Http\Controllers'], function()
{
    Route::get('/', 'ExtendedEditorController@index');
});
