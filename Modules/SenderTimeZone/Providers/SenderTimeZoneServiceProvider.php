<?php

namespace Modules\SenderTimeZone\Providers;

use App\Thread;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class SenderTimeZoneServiceProvider extends ServiceProvider
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
        // Show menu item.
        \Eventy::addAction('thread.menu.append', function($thread) {
            if ($thread->type != Thread::TYPE_CUSTOMER) {
                return;
            }

            $data = self::parseMailHeaders($thread->headers);

            if (empty($data['date'])) {
                return;
            }
            ?>
            <li>
                <a href="<?php echo route('sendertimezone.modal', ['thread_id' => $thread->id]) ?>" data-trigger="modal" data-modal-title="<?php echo __("Sender Time Zone") ?>" data-modal-size="lg" data-modal-no-footer="true"><small class="glyphicon glyphicon-send"></small>&nbsp; <small class="text-help"><?php echo $data['date'] ?> (GMT<?php echo $data['tz'] ?>)</small><br/><small class="glyphicon glyphicon-time"></small>&nbsp; <small class="text-help"><?php echo __("Current Time") ?>: <?php echo $data['sender_time'] ?></small></a>
            </li>
            <?php
        }, 100);
    }

    public static function parseMailHeaders($headers)
    {
        $result = [
            'date' => '',
            'tz' => '',
            'sender_time' => '',
        ];

        $data = imap_rfc822_parse_headers($headers ?: '');

        if (empty($data->date)) {
            return $result;
        }

        $carbon_date = \Helper::parseDateToCarbon($data->date);

        $result['date'] = User::dateFormat($carbon_date, "H:i", null, true, false);
        $tz = $carbon_date->getTimeZone();
        $result['tz'] = $tz->getName();

        if ($result['tz']) {
            $sender_time = Carbon::now()->setTimezone($result['tz']);
            $result['sender_time'] = User::dateFormat($sender_time, "H:i", null, true, false);
        }

        return $result;
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
            __DIR__.'/../Config/config.php' => config_path('sendertimezone.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'sendertimezone'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/sendertimezone');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/sendertimezone';
        }, \Config::get('view.paths')), [$sourcePath]), 'sendertimezone');
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
