<?php

namespace Modules\CustomHomepage\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('CH_MODULE', 'customhomepage');

class CustomHomepageServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            //$javascripts[] = \Module::getPublicPath(CUST_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(CH_MODULE).'/js/module.js';
            return $javascripts;
        });
        
        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[CH_MODULE] = ['title' => __('Custom Homepage'), 'icon' => 'home', 'order' => 550];

            return $sections;
        }, 45);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != CH_MODULE) {
                return $settings;
            }
           
            $settings['customhomepage.dashboard_path'] = config('customhomepage.dashboard_path');
            $settings['customhomepage.login_path'] = config('customhomepage.login_path');
            $settings['customhomepage.homepage_redirect'] = config('customhomepage.homepage_redirect');
            $settings['customhomepage.homepage_html'] = base64_decode(config('customhomepage.homepage_html'));

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != CH_MODULE) {
                return $params;
            }

            $params['settings'] = [
                'customhomepage.dashboard_path' => [
                    'env' => 'CUSTOMHOMEPAGE_DASHBOARD_PATH',
                ],
                'customhomepage.login_path' => [
                    'env' => 'CUSTOMHOMEPAGE_LOGIN_PATH',
                ],
                'customhomepage.homepage_redirect' => [
                    'env' => 'CUSTOMHOMEPAGE_HOMEPAGE_REDIRECT',
                ],
                'customhomepage.homepage_html' => [
                    'env' => 'CUSTOMHOMEPAGE_HTML',
                    'env_encode' => true,
                ],
            ];

            return $params;
        }, 20, 2);

        // Settings view name.
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != CH_MODULE) {
                return $view;
            } else {
                return 'customhomepage::settings';
            }
        }, 20, 2);

        // \Eventy::addFilter('footer.text', function($footer_text) {
        //     if (\Route::currentRouteAction() == '\Modules\CustomHomepage\Http\Controllers\CustomHomepageController@home') {
        //         return '&nbsp;';
        //     }
        //     return $footer_text;
        // });

        // When routes/web.php is processed, events are not assigned yet.
        // \Eventy::addFilter('routes.home.controller', function($controller) {
        //     return 'Modules\CustomHomepage\Http\Controllers\CustomHomepageController@home';
        // });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // When routes/web.php is processed, events are not assigned yet.
        \Config::set('app.dashboard_path', config('customhomepage.dashboard_path'));
        if (config('customhomepage.login_path')) {
            \Config::set('app.login_path', config('customhomepage.login_path'));
        }
        \Config::set('app.home_controller', '\Modules\CustomHomepage\Http\Controllers\CustomHomepageController@home');

        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('customhomepage.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'customhomepage'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/customhomepage');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/customhomepage';
        }, \Config::get('view.paths')), [$sourcePath]), 'customhomepage');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
