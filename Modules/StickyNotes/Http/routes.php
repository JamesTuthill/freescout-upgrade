<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\StickyNotes\Http\Controllers'], function()
{
    Route::get('/', 'StickyNotesController@index');
});
