<?php

namespace Modules\Inbox\Providers;

use App\Conversation;
use App\ConversationFolder;
use App\Folder;
use App\Mailbox;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class InboxServiceProvider extends ServiceProvider
{
    const FOLDER_TYPE_INBOX = 150;

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
        \Eventy::addFilter('folder.type_icon', function($icon, $folder) {
             if ($folder->type == self::FOLDER_TYPE_INBOX) {
                 return 'cloud';
             }

             return $icon;
         }, 20, 2);

        \Eventy::addFilter('folder.type_name', function($name, $folder) {
             if ($folder->type == self::FOLDER_TYPE_INBOX) {
                 return __('All');
             }

            return $name;
        }, 20, 2);

        \Eventy::addFilter('folder.conversations_query', function($query_conversations, $folder, $user_id) {

            if ($folder->type == self::FOLDER_TYPE_INBOX) {
                $query = Conversation::where('mailbox_id', $folder->mailbox_id)
                    ->where('state', Conversation::STATE_PUBLISHED);

                // Respect app.show_only_assigned_conversations parameter.
                // Show only assigned to the current user conversations.
                if (!\Helper::isConsole()
                    && $user_id
                    && $auth_user = auth()->user()
                ) {
                    if ($auth_user->id == $user_id && $auth_user->canSeeOnlyAssignedConversations()) {
                        $assignee_condition_applied = \Eventy::filter('folder.only_assigned_condition', false, $query, $user_id);
                        if (!$query) {
                            $query->where('user_id', '=', $user_id);
                            $assignee_condition_applied = true;
                        }
                    }
                }

                return $query;
            }

            return $query_conversations;
        }, 20, 3);

        \Eventy::addFilter('conversations.ajax_pagination_folder', function($folder, $request, $response, $user) {

            if ($request->folder_id == (-1)*self::FOLDER_TYPE_INBOX) {
                $folder = new Folder();
                $folder->type = self::FOLDER_TYPE_INBOX;
                // Set any available mailbox.
                $folder->mailbox_id = $request->mailbox_id;
            }

            return $folder;
        }, 20, 4);

        \Eventy::addFilter('folder.conversations_order_by', function($order_by, $folder_type) {

            if ($folder_type == self::FOLDER_TYPE_INBOX) {
                $order_by[] = ['last_reply_at' => 'desc'];
            }

            return $order_by;
        }, 20, 2);

        \Eventy::addFilter('mailbox.folders', function($folders, $mailbox) {
            if (count($folders) && $mailbox->id > 0) {
                $folder = new Folder();
                $folder->id = (-1)*self::FOLDER_TYPE_INBOX;
                $folder->type = self::FOLDER_TYPE_INBOX;
                // Set any available mailbox.
                $folder->mailbox_id = $mailbox->id;

                $folders->push($folder);
            }

            return $folders;
        }, 10, 2);

        \Eventy::addFilter('conversations_table.column_title_date', function($title, $folder) {
            if ($folder->type == self::FOLDER_TYPE_INBOX) {
                return __('Last Updated');
            }

            return $title;
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
            __DIR__.'/../Config/config.php' => config_path('inbox.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'inbox'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/inbox');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/inbox';
        }, \Config::get('view.paths')), [$sourcePath]), 'inbox');
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
