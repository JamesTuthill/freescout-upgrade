<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Checklists\Http\Controllers'], function()
{
	Route::post('/checklists/ajax', ['uses' => 'ChecklistsController@ajax', 'laroute' => true, 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('checklists.ajax');
    Route::get('/checklists/ajax_html', ['uses' => 'ChecklistsController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('checklists.ajax_html');
});
