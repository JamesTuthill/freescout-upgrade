<?php

namespace Modules\OutOfOffice\Providers;

use App\Conversation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class OutOfOfficeServiceProvider extends ServiceProvider
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
        \Eventy::addAction('user.edit.before_first_name', function($user) {
            ?>
                <div class="form-group">
                    <label for="available" class="col-sm-2 control-label"><?php echo __('Available') ?></label>

                    <div class="col-sm-6">
                        <div class="controls">
                            <div class="onoffswitch-wrap">
                                <div class="onoffswitch">
                                    <input type="checkbox" name="available" value="1" id="available" class="onoffswitch-checkbox" <?php if (old('available', $user->available)): ?>checked="checked" <?php endif ?> >
                                    <label class="onoffswitch-label" for="available"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
        });

        \Eventy::addFilter('user.save_profile', function($user, $request) {
            $user->available = (bool)$request->input('available');

            return $user;
        }, 20, 2);

        \Eventy::addFilter('assignee_list.a_class', function($class, $user) {
            if (!$user->available) {
                $class .= ' disabled';
            }

            return $class;
        }, 20, 2);

        \Eventy::addAction('menu.user.name_append', function($user) {
            if (!$user->available) {
                echo ' <b>(OFF)</b>';
            }
        });

        \Eventy::addAction('assignee_list.item_append', function($user) {
            if (!$user->available) {
                echo ' ('.__('Out of Office').')';
            }
        });

        \Eventy::addAction('assignee_list.option_attrs', function($user) {
            if (!$user->available) {
                echo ' disabled';
            }
        });

        // Make conversation Unassigned if Assignee is unavailable
        \Eventy::addAction('conversation.customer_replied', function($conversation) {
            if ($conversation->user_id && $conversation->user && !$conversation->user->available) {
                $conversation->changeUser(Conversation::USER_UNASSIGNED, $conversation->user, $create_thread = true);
            }
        }, 20);
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
            __DIR__.'/../Config/config.php' => config_path('outofoffice.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'outofoffice'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/outofoffice');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/outofoffice';
        }, \Config::get('view.paths')), [$sourcePath]), 'outofoffice');
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
