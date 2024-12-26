<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\SentFolder\Http\Controllers'], function()
{
    Route::get('/', 'SentFolderController@index');
});
