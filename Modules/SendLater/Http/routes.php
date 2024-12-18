<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\SendLater\Http\Controllers'], function()
{
	Route::post('/conversation/send-later/ajax', ['uses' => 'SendLaterController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('sendlater.ajax');
    Route::get('/conversation/send-later/ajax-html/{conversation_id}/{action}', ['uses' => 'SendLaterController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('sendlater.ajax_html');
});
