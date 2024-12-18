<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Teams\Http\Controllers'], function()
{
	Route::post('/teams/ajax', ['uses' => 'TeamsController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['admin'], 'laroute' => true])->name('teams.ajax');
    Route::get('/teams', ['uses' => 'TeamsController@teams', 'middleware' => ['auth', 'roles'], 'roles' => ['admin'], 'laroute' => true])->name('teams.teams');
    Route::get('/teams/new', ['uses' => 'TeamsController@create', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('teams.create');
    Route::post('/teams/new', ['uses' => 'TeamsController@updateSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
    Route::get('/teams/{id}', ['uses' => 'TeamsController@update', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('teams.update');
    Route::post('/teams/{id}', ['uses' => 'TeamsController@updateSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
    //Route::get('/teams/ajax-html/{action}/{param}', ['uses' => 'TeamsController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['admin', 'user']])->name('teams.ajax_html');
});
