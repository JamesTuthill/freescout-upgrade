<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\CustomHomepage\Http\Controllers'], function()
{
    //Route::get('/', 'CustomHomepageController@index');
});
