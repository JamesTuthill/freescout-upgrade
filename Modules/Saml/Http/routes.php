<?php

Route::group(['middleware' => ['web'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Saml\Http\Controllers'], function()
{
    Route::get('/saml/{secret}/metadata', 'SamlController@metadata')->name('saml.sp_metadata');
    Route::get('/saml/{secret}/logout', 'SamlController@singleLogout')->name('saml.single_logout');
    //Route::get('/saml/login', 'SamlController@login')->name('saml.login');
});

Route::group(['middleware' => [\App\Http\Middleware\EncryptCookies::class, \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Session\Middleware\StartSession::class, \App\Http\Middleware\HttpsRedirect::class], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Saml\Http\Controllers'], function()
{
    Route::post('/saml/{secret}/acs', 'SamlController@acsEndpoing')->name('saml.acs');
});
