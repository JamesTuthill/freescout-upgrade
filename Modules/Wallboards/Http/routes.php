<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Wallboards\Http\Controllers'], function()
{
    Route::get('/wallboards', ['uses' => 'WallboardsController@show', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('wallboards.show');
    Route::post('/wallboards/ajax', ['uses' => 'WallboardsController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('wallboards.ajax');
    Route::get('/wallboards/ajax_html', ['uses' => 'WallboardsController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('wallboards.ajax_html');
});
