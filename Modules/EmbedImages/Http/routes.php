<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\EmbedImages\Http\Controllers'], function()
{
    Route::get('/', 'EmbedImagesController@index');
});
