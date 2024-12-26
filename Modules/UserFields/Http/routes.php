<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\UserFields\Http\Controllers'], function()
{
	Route::get('/users/fields/ajax-html/{action}/{param?}', ['uses' => 'UserFieldsController@ajaxHtml'])->name('userfields.ajax_html');
    Route::post('/users/fields/ajax-admin', ['uses' => 'UserFieldsController@ajaxAdmin', 'laroute' => true])->name('userfields.ajax_admin');
    Route::get('/users/fields/ajax-search', ['uses' => 'UserFieldsController@ajaxSearch', 'laroute' => true])->name('userfields.ajax_search');
});
