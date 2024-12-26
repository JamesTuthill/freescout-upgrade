<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\GlobalMailbox\Http\Controllers'], function()
{
	Route::get('/global-mailbox/{folder_type_id}', 'GlobalMailboxController@view')->name('globalmailbox.view.folder');
});
