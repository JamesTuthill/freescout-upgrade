<?php

namespace Modules\Snooze\Providers;

use App\Conversation;
use App\Folder;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('SNOOZE_MODULE', 'snooze');

class SnoozeServiceProvider extends ServiceProvider
{
    const FOLDER_TYPE = 180; // max 255
    const FOLDER_ICON = 'time';

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
            $javascripts[] = \Module::getPublicPath(SNOOZE_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(SNOOZE_MODULE).'/js/module.js';

            return $javascripts;
        });

        // JavaScript at the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view')) {
                echo 'snoozeInit();';
            }
        });

        \Eventy::addAction('conversation.prepend_action_buttons', function($conversation, $mailbox) {
            if (!in_array($conversation->status, [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING, Conversation::STATUS_CLOSED])
                || $conversation->snoozed_until
            ) {
                return;
            }
            ?>
                <li><a href="<?php echo route('snooze.ajax_html', ['action' => 'snooze', 't' => time()]) ?>" data-trigger="modal" data-modal-no-footer="true" data-modal-title="<?php echo __('Snooze until') ?>…"  data-modal-size="sm" data-modal-on-show="snoozeInitModal"><i class="glyphicon glyphicon-time"></i> <?php echo __("Snooze") ?></a></li>
            <?php
        }, 100, 2);

        // Show block in conversation.
        \Eventy::addAction('conversation.after_subject_block', function($conversation, $mailbox) {
            if (!$conversation->snoozed_until) {
                return;
            }
            ?>
                <div class="conv-top-block" style="background-color: #f9fafa;"">
                    <?php echo __('Snoozed until') ?>: <span class="text-help"><?php echo User::dateFormat($conversation->snoozed_until); ?></span>
                    <a href="#" class="pull-right snooze-unsnooze" data-loading-text="<?php echo __('Unsnooze') ?>…"><?php echo __('Unsnooze') ?></a>
                </div>
            <?php
        }, 20, 2);

        \Eventy::addFilter('mailbox.folders.public_types', function($list) {
            $list[] = self::FOLDER_TYPE;

            return $list;
        }, 20, 1);

         \Eventy::addFilter('folder.type_icon', function($icon, $folder) {
             if ($folder->type == self::FOLDER_TYPE) {
                 return self::FOLDER_ICON;
             }

             return $icon;
         }, 20, 2);

        \Eventy::addFilter('folder.type_name', function($name, $folder) {
            if ($folder->type == self::FOLDER_TYPE) {
                return __('Snoozed');
            }

            return $name;
        }, 20, 2);

        \Eventy::addFilter('folder.conversations_query', function($query, $folder, $user_id) {
            if ($folder->type != self::FOLDER_TYPE) {
                return $query;
            }

            $query = Conversation::select('conversations.*')
                ->where('mailbox_id', $folder->mailbox_id)
                ->where('status', Conversation::STATUS_CLOSED)
                ->where('snoozed_until', '!=', null);

            return $query;
        }, 20, 3);

        \Eventy::addFilter('folder.conversations_order_by', function($order_by, $folder_type) {
            if ($folder_type != self::FOLDER_TYPE) {
                return $order_by;
            }
            $order_by[] = ['closed_at' => 'desc'];

            return $order_by;
        }, 20, 2);

        \Eventy::addFilter('conversation.is_in_folder_allowed', function($is_allowed, $folder, $conversation) {
            if ($folder->type != self::FOLDER_TYPE || empty($conversation)) {
                return $is_allowed;
            }
            if ($conversation->snoozed_until && $conversation->status == Conversation::STATUS_CLOSED) {
                return true;
            } else {
                return false;
            }
        }, 20, 3);

        \Eventy::addFilter('conversation.get_nearby_query', function($query, $conversation, $mode, $folder) {
            if ($folder->type != self::FOLDER_TYPE) {
                return $query;
            }

            $query = Conversation::select('conversations.*')
                ->where('status', Conversation::STATUS_CLOSED)
                ->where('snoozed_until', '!=', null);

            return $query;
        }, 20, 4);

        // \Eventy::addFilter('folder.update_counters', function($updated, $folder) {
        //     if ($folder->type != self::FOLDER_TYPE) {
        //         return $updated;
        //     }

        //     return self::setFolderCounters($folder);
        // }, 20, 2);
        
        \Eventy::addAction('conversation.status_changed', function($conversation, $user, $changed_on_reply) {
            if ($conversation->snoozed_until && $conversation->status != Conversation::STATUS_CLOSED) {
                $conversation->snoozed_until = null;
                $conversation->save();
            }
        }, 20, 3);

        // Unsnooze on customer reply.
        \Eventy::addAction('conversation.customer_replied', function($conversation, $thread, $customer) {
            if ($conversation->snoozed_until && $conversation->status != Conversation::STATUS_CLOSED) {
                $conversation->snoozed_until = null;
                $conversation->save();
            }
        }, 20, 3);

        \Eventy::addAction('conversations_table.before_subject', function($conversation) {
            if ($conversation->snoozed_until) {
                echo '<i class="glyphicon glyphicon-time text-primary" data-toggle="tooltip" title="'.__('Snoozed until').': '.User::dateFormat($conversation->snoozed_until).'"></i>';
            }

        }, 20, 2);

        \Eventy::addAction('snooze.unsnooze', function($conversation_id, $user_id, $snoozed_until) {
            $conversation = Conversation::find($conversation_id);
            if (!$conversation) {
                return;
            }
            if ($conversation->snoozed_until != $snoozed_until) {
                return;
            }
            $user = User::find($user_id);
            if (!$user) {
                return;
            }
            \Snooze::unsnooze($conversation, $user);
        }, 20, 3);

        // Show block in conversation
        \Eventy::addAction('conversation.after_subject_block', function($conversation, $mailbox) {
            echo \View::make('snooze::partials/datepicker')->render();
        }, 20, 2);
    }

    public static function unsnooze($conversation, $user)
    {
        $conversation->snoozed_until = null;
        $conversation->changeStatus(Conversation::STATUS_ACTIVE, $user);
    }
    
    // public static function setFolderCounters($folder)
    // {
    //     $query = Conversation::where('conversations.mailbox_id', $folder->mailbox_id)
    //         ->where('conversations.state', Conversation::STATE_PUBLISHED)
    //         ->where('user_id', $folder->user_id ?? 0);

    //     $active_query = clone $query;
    //     $folder->active_count = $active_query
    //         ->where('conversations.status', Conversation::STATUS_ACTIVE)
    //         ->count();

    //     $total_query = clone $query;

    //     $folder->total_count = $total_query->count();
    //     $folder->save();

    //     return true;
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
            __DIR__.'/../Config/config.php' => config_path('snooze.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'snooze'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/snooze');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/snooze';
        }, \Config::get('view.paths')), [$sourcePath]), 'snooze');
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
