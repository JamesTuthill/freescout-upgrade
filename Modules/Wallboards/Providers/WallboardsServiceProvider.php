<?php

namespace Modules\Wallboards\Providers;

use App\Conversation;
use Modules\Wallboards\Entities\Wallboard;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('WALLBOARDS_MODULE', 'wallboards');

class WallboardsServiceProvider extends ServiceProvider
{
    const PERM_ACCESS_WALLBOARDS = 101;

    const METRIC_ACTIVE = 1;
    const METRIC_PENDING = 2;
    const METRIC_CLOSED = 3;

    public static $default_metrics = [
        self::METRIC_ACTIVE,
        self::METRIC_PENDING,
        self::METRIC_CLOSED,
    ];

    const GROUP_BY_ASSIGNEE = 'user_id';
    const GROUP_BY_TYPE = 'type';
    const GROUP_BY_TAG = 'tag';
    const GROUP_BY_CF = 'custom_field';

    const FILTER_BY_DATE = 'date';
    const FILTER_BY_MAILBOX = 'mailbox';
    const FILTER_BY_TYPE = 'type';
    const FILTER_BY_STATUS = 'status';
    const FILTER_BY_ASSIGNEE = 'user_id';
    const FILTER_BY_TAG = 'tag';
    const FILTER_BY_CF = 'custom_field';

    const DATE_PERIOD_ALL_TIME = 'all';
    const DATE_PERIOD_TODAY = 'today';
    const DATE_PERIOD_YESTERDAY = 'yesterday';
    const DATE_PERIOD_WEEK = 'week';
    const DATE_PERIOD_MONTH = 'month';
    const DATE_PERIOD_YEAR = 'year';
    const DATE_PERIOD_CUSTOM = 'custom';

    const DEFAULT_ROWS_COUNT = 20;

    // Text separating filters values.
    const FILTERS_SEPARATOR = '|';

    public static $default_filters = [
        \Wallboards::FILTER_BY_DATE => ['period' => self::DATE_PERIOD_WEEK],
        \Wallboards::FILTER_BY_MAILBOX => [],
        \Wallboards::FILTER_BY_TYPE => [],
        \Wallboards::FILTER_BY_STATUS => [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING, Conversation::STATUS_CLOSED],
        \Wallboards::FILTER_BY_ASSIGNEE => [],
        \Wallboards::FILTER_BY_TAG => [],
        \Wallboards::FILTER_BY_CF => [],
    ];

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
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(WALLBOARDS_MODULE).'/css/module.css';
            return $styles;
        });
        
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(WALLBOARDS_MODULE).'/js/laroute.js';

            if (!preg_grep("/html5sortable\.js$/", $javascripts)) {
                $javascripts[] = '/js/html5sortable.js';
            }

            $javascripts[] = \Module::getPublicPath(WALLBOARDS_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Add item to the mailbox menu
        \Eventy::addAction('menu.manage.after_mailboxes', function($mailbox) {
            if (self::canAccessWallboards()) {
                echo \View::make('wallboards::partials/main_menu_item', [])->render();
            }
        }, 16);

        // Select main menu item.
        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['manage']['wallboards'] = [
                'wallboards.show'
            ];

            return $menu;
        });

        \Eventy::addFilter('user_permissions.list', function($list) {
            $list[] = self::PERM_ACCESS_WALLBOARDS;
            return $list;
        });

        \Eventy::addFilter('user_permissions.name', function($name, $permission) {
            if ($permission != self::PERM_ACCESS_WALLBOARDS) {
                return $name;
            }
            return __('Users are allowed to access wallboards');
        }, 20, 2);

        \Eventy::addAction('dashboard.heading_append', function() {
            if (self::canAccessWallboards()) {
                echo ' / <a href="'.route('wallboards.show').'">'.__('Wallboards').'</a>';
            }
        });
    }

    public static function wallboardsUserCanView($user)
    {       
        $wallboards = Wallboard::all();

        // Remove non-accessible wallboards.
        foreach ($wallboards as $i => $wallboard) {
            if (!$wallboard->userCanView($user)) {
                $wallboards->forget($i);
            }
        }

        return $wallboards;
    }

    public static function canAccessWallboards($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if (!$user) {
            return false;
        }
        return $user->isAdmin() || $user->hasPermission(self::PERM_ACCESS_WALLBOARDS);
    }

    public static function url($params)
    {
        return route('wallboards.show', ['wb' => $params]);
    }

    public static function getPeriodName($period)
    {
        switch ($period) {
            case self::DATE_PERIOD_ALL_TIME:
                return ('All time');
            case self::DATE_PERIOD_TODAY:
                return ('Today');
            case self::DATE_PERIOD_YESTERDAY:
                return ('Yesterday');
            case self::DATE_PERIOD_WEEK:
                return ('Last 7 days');
            case self::DATE_PERIOD_MONTH:
                return ('Last 30 days');
            case self::DATE_PERIOD_YEAR:
                return ('Last 365 days');
            case self::DATE_PERIOD_CUSTOM:
                return ('Custom');
            
            default:
                return '?';
        }
    }

    // public static function conversationsLink($widget)
    // {
    //     $f = [];
        
    //     return route('conversations.search', ['f' => $f]);
    // }

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
            __DIR__.'/../Config/config.php' => config_path('wallboards.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'wallboards'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/wallboards');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/wallboards';
        }, \Config::get('view.paths')), [$sourcePath]), 'wallboards');
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
