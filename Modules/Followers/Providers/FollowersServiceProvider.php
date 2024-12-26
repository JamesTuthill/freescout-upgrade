<?php

namespace Modules\Followers\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('FOLLOWERS_MODULE', 'followers');

class FollowersServiceProvider extends ServiceProvider
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
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(FOLLOWERS_MODULE).'/css/module.css';
            return $styles;
        });

        // // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(FOLLOWERS_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(FOLLOWERS_MODULE).'/js/module.js';

            return $javascripts;
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view')) {
                echo 'initFollowers();';
            }
        });

        // Add item to the mailbox menu
        \Eventy::addAction('conversation.after_prev_convs', function($customer, $conversation, $mailbox) {
            $followers = [];

            $show_add = false;
            $max_users = (int)config('followers.followers_max_users');

            // Users are already sorted by full name.
            $users = $mailbox->usersAssignable(true);            

            if (count($users) > $max_users) {
                $show_add = true;
            }

            // Add followers first.
            foreach ($users as $i => $user) {
                foreach ($conversation->followers as $follower) {
                    if ($follower->user_id == $user->id) {
                        $user->subscribed = true;
                        $user->added_by_user_id = $follower->added_by_user_id;
                        $followers[] = $user;
                        $users->forget($i);
                        break;
                    }
                }
            }
            // Add users if needed.
            if ($max_users > 0 && count($followers) < $max_users) {
                $followers_ids = $conversation->followers->pluck('user_id')->all();
                
                foreach ($users as $i => $user) {
                    if (!in_array($user->id, $followers_ids)) {
                        $user->subscribed = false;
                        $followers[] = $user;
                    }
                    if (count($followers) >= $max_users) {
                        break;
                    }
                }
            }

            echo \View::make('followers::partials/sidebar_block', [
                'followers' => $followers,
                'show_add'  => $show_add,
                'conversation' => $conversation,
            ])->render();

        }, 20, 3);

        // Custom menu in conversation
        \Eventy::addAction('conversation.customer.menu', function($customer, $conversation) {
            ?>
                <li role="presentation" class="col3-hidden"><a data-toggle="collapse" href=".collapse-followers" tabindex="-1" role="menuitem"><?php echo __("Followers") ?></a></li>
            <?php
        }, 15, 2);
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
            __DIR__.'/../Config/config.php' => config_path('followers.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'followers'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/followers');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/followers';
        }, \Config::get('view.paths')), [$sourcePath]), 'followers');
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
