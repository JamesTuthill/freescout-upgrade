<?php

namespace Modules\ExtendedAttachments\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('EA_MODULE', 'extendedattachments');

class ExtendedAttachmentsServiceProvider extends ServiceProvider
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
        $this->registerCommands();
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
            $styles[] = \Module::getPublicPath(EA_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(EA_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(EA_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Add item to the mailbox menu
        \Eventy::addAction('conversation.after_prev_convs', function($customer, $conversation, $mailbox) {
           
            $attachments = [];

            if (!$conversation->has_attachments) {
                return;
            }

            $threads = $conversation->threads->sortByDesc('created_at');

            foreach ($threads as $thread) {
                if ($thread->has_attachments) {
                    foreach ($thread->attachments as $attachment) {
                        $attachments[] = $attachment;
                    }
                }
            }

            if (!count($attachments)) {
                return;
            }

            echo \View::make('extendedattachments::partials/sidebar_block', [
                'attachments' => $attachments,
                'thread' => $thread,
                'conversation' => $conversation,
                'mailbox' => $mailbox,
            ])->render();

        }, 40, 3);

        // Custom menu in conversation
        \Eventy::addAction('conversation.customer.menu', function($customer, $conversation) {
            ?>
                <li role="presentation" class="col3-hidden"><a data-toggle="collapse" href=".collapse-attachments" tabindex="-1" role="menuitem"><?php echo __("Attachments") ?></a></li>
            <?php
        }, 40, 2);

        // Search filters.
        \Eventy::addFilter('search.filters_list', function($filters_list) {
            $filters_list[] = self::searchFilterName();

            return $filters_list;
        }, 10);

        // Display search filters.
        \Eventy::addAction('search.display_filters', function($filters) {
            echo \View::make('extendedattachments::partials/search_filter', [
                'filters'       => $filters,
            ])->render();
        });

        // Search filters apply.
        \Eventy::addFilter('search.conversations.apply_filters', function($query_conversations, $filters, $q) {

            if (!empty($filters[self::searchFilterName()])) {
                $query_conversations->join('attachments as attachment_names', function ($join) use ($filters) {
                    $join->on('attachment_names.thread_id', 'threads.id');
                    $join->where('attachment_names.file_name', 'like', '%'.$filters[self::searchFilterName()].'%');
                });
            }

            return $query_conversations;
        }, 20, 3);

        \Eventy::addAction('thread.attachments_list_append', function($thread) {
            if (count($thread->attachments) < 2) {
                return;
            }
            ?>
                <li>
                    <a href="<?php echo route('extendedattachments.download_thread_attachments', ['thread_id' => $thread->id]) ?>" class="break-words" target="_blank"><?php echo __('Download all') ?></a>
                </li>
            <?php
        }, 20, 1);

        // Remoe old archives.
        \Eventy::addFilter('schedule', function($schedule) {
            $schedule->command('freescout:extendedattachments-cleanup')->cron('* * * * *');

            return $schedule;
        });

        \Eventy::addAction('thread.attachment_append', function($attachment) {
            ?>
                &nbsp;<a href="<?php echo route('extendedattachments.ajax_html', ['action' => 'delete_attachment', 'attachment_id' => $attachment->id]) ?>" data-trigger="modal" data-modal-title="<?php echo __("Delete Attachment") ?>" data-modal-no-footer="true" data-modal-on-show="eaInitDeleteModal"><i class="glyphicon glyphicon-trash small"></i></a>
            <?php
        }, 20);
        
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view')) {
                $reminder_phrases = base64_decode(config('extendedattachments.reminder_phrases')) ?? '';
                $reminder_phrases = preg_split("/\r\n|\n|\r/", $reminder_phrases);
                if (count($reminder_phrases)) {
                    foreach ($reminder_phrases as $i => $reminder_phrase) {
                        $reminder_phrases[$i] = str_replace('"', '\"', $reminder_phrase);
                        $reminder_phrases[$i] = strip_tags($reminder_phrases[$i]);
                    }
                    echo 'eaReminderInit(["'.implode('","', $reminder_phrases).'"], "'.__("You mentioned :phrase in your message but no attachment is present.").'", "'.__("Send Anyway").'");';
                }
                echo 'avInit({download: "'.__('Download').'", unsupported: "'.__("Couldn't preview file").'"});';
            }
        });

        // Section parameters.
        \Eventy::addFilter('settings.alter_section_params', function($params, $section) {
           
            if ($section != 'general') {
                return $params;
            }

            $params['settings']['extendedattachments.reminder_phrases'] = [
                'env' => 'EXTENDEDATTACHMENTS_REMINDER_PHRASES',
                'env_encode' => true,
            ];

            return $params;
        }, 20, 2);

        \Eventy::addAction('settings.general.append', function($settings, $errors) {
            echo \View::make('extendedattachments::partials/settings', [
                'settings'     => $settings,
                'errors'       => $errors,
            ])->render();
        }, 20, 2);

        // Section settings.
        \Eventy::addFilter('settings.alter_section_settings', function($settings, $section) {
           
            if ($section != 'general') {
                return $settings;
            }
           
            $settings['extendedattachments.reminder_phrases'] = base64_decode(config('extendedattachments.reminder_phrases'));

            return $settings;
        }, 20, 2);
    }

    public static function searchFilterName()
    {
        return strtolower('Attachment Name');
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
            __DIR__.'/../Config/config.php' => config_path('extendedattachments.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'extendedattachments'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/extendedattachments');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/extendedattachments';
        }, \Config::get('view.paths')), [$sourcePath]), 'extendedattachments');
    }

    public function registerCommands()
    {
        $this->commands([
            \Modules\ExtendedAttachments\Console\Cleanup::class
        ]);
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
