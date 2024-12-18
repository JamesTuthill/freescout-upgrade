<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['user', 'admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Followers\Http\Controllers'], function()
{
	Route::post('/conversation/followers/ajax', ['uses' => 'FollowersController@ajax', 'laroute' => true])->name('conversations.followers.ajax');
    Route::get('/conversation/followers/ajax-html/{conversation_id}/{action}', ['uses' => 'FollowersController@ajaxHtml'])->name('conversations.followers.ajax_html');
});
