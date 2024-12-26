<?php

namespace Modules\UserFields\Providers;

use Modules\UserFields\Entities\UserField;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('UF_MODULE', 'userfields');

class UserFieldsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public static $search_user_fields = [];

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
            $javascripts[] = \Module::getPublicPath(UF_MODULE).'/js/laroute.js';
            if (!preg_grep("/html5sortable\.js$/", $javascripts)) {
                $javascripts[] = '/js/html5sortable.js';
            }
            $javascripts[] = \Module::getPublicPath(UF_MODULE).'/js/module.js';
            return $javascripts;
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            $user_fields = \UserField::getUserFields();
            if (count($user_fields)) {
                foreach ($user_fields as $user_field) {
                    $user_vars['user.'.$user_field->getNameEncoded()] = $user_field->name.' ('.$user_field->getNameEncoded().')';
                }
                
                echo 'ufInit('.json_encode($user_vars).');';
            }
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['user-fields'] = ['title' => __('User Fields'), 'icon' => 'list-alt', 'order' => 250];

            return $sections;
        }, 16);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != 'user-fields') {
                return $settings;
            }
           
            return [
                'user_fields' => \UserField::getUserFields()
            ];
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != 'user-fields') {
                return $params;
            }

            $params = [
                'settings' => [
                    
                ],
            ];

            return $params;
        }, 20, 2);

        // Settings view name.
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'user-fields') {
                return $view;
            } else {
                return 'userfields::user_fields';
            }
        }, 20, 2);

        // JS messages.
        \Eventy::addAction('js.lang.messages', function() {
            ?>
                "uf_confirm_delete_user_field": "<?php echo __("Deleting this User Field will remove all historical data. Delete this custom field?") ?>",
                "uf_confirm_delete_option": "<?php echo __("Deleting this dropdown option will remove all historical data. Delete this dropdown option?") ?>",
            <?php
        });

        // Show fields in user profile.
        \Eventy::addAction('user.edit.before_photo', function($user) {

            $user_fields = UserField::getUserFieldsWithValues($user->id);

            if (!$user_fields) {
                return;
            }

            echo \View::make('userfields::partials/user_fields_edit', ['user_fields' => $user_fields])->render();
        });

        \Eventy::addAction('user.set_data', function($user, $data, $replace_data) {

            $user_fields = UserField::getUserFields();

            if (!$user_fields) {
                return;
            }

            foreach ($user_fields as $user_field) {
                foreach ($data as $data_field => $data_value) {
                    if ($data_field == $user_field->getNameEncoded()) {
                        if (!$user->id) {
                            $user->save();
                        }
                        UserField::setValue($user->id, $user_field->id, $data_value);
                        break;
                    }
                }
            }
        }, 20, 3);

        \Eventy::addFilter('mail_vars.replace', function($vars, $data) {
            if (empty($data['user'])) {
                return $vars;
            }
            $user_fields = UserField::getUserFieldsWithValues($data['user']->id);

            if (!$user_fields) {
                return $vars;
            }

            foreach ($user_fields as $user_field) {
                $vars['{%user.'.$user_field->getNameEncoded().'%}'] = $user_field->value;
            }

            return $vars;
        }, 20, 2);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
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
            __DIR__.'/../Config/config.php' => config_path('userfields.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'userfields'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/userfields');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/userfields';
        }, \Config::get('view.paths')), [$sourcePath]), 'userfields');
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
