<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Snooze\Http\Controllers'], function()
{
	Route::post('/conversation/snooze/ajax', ['uses' => 'SnoozeController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('snooze.ajax');
    Route::get('/conversation/snooze/ajax_html', ['uses' => 'SnoozeController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('snooze.ajax_html');
});
