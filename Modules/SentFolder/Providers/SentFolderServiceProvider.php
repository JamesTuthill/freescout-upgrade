<?php

namespace Modules\SentFolder\Providers;

use App\Conversation;
use App\ConversationFolder;
use App\Folder;
use App\Mailbox;
use App\User;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class SentFolderServiceProvider extends ServiceProvider
{
    const FOLDER_TYPE_SENT = 140;

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
             if ($folder->type == self::FOLDER_TYPE_SENT) {
                 return 'send';
             }

             return $icon;
         }, 20, 2);

        \Eventy::addFilter('folder.type_name', function($name, $folder) {
            if ($folder->type == self::FOLDER_TYPE_SENT) {
                return __('Sent');
            }

            return $name;
        }, 20, 2);

        \Eventy::addFilter('folder.conversations_query', function($query_conversations, $folder, $user_id) {

            if ($folder->type == self::FOLDER_TYPE_SENT) {
                return Conversation::select(['conversations.*', \DB::raw('max(threads.created_at) as last_user_reply_at')])
                    ->where('mailbox_id', $folder->mailbox_id)
                    ->join('threads', function ($join) {
                        $join->on('conversations.id', '=', 'threads.conversation_id');
                        $join->where('threads.type', Thread::TYPE_MESSAGE);
                        $join->where('threads.created_by_user_id', auth()->user()->id);
                    })
                    ->where('conversations.state', Conversation::STATE_PUBLISHED)
                    ->groupBy('conversations.id');
            }

            return $query_conversations;
        }, 20, 3);

        \Eventy::addFilter('conversations.ajax_pagination_folder', function($folder, $request, $response, $user) {

            if ($request->folder_id == (-1)*self::FOLDER_TYPE_SENT) {
                $folder = new Folder();
                $folder->type = self::FOLDER_TYPE_SENT;
                // Set any available mailbox.
                $folder->mailbox_id = $request->mailbox_id;
            }

            return $folder;
        }, 20, 4);

        \Eventy::addFilter('folder.conversations_order_by', function($order_by, $folder_type) {

            if ($folder_type == self::FOLDER_TYPE_SENT) {
                $order_by[] = ['last_user_reply_at' => 'desc'];
            }

            return $order_by;
        }, 20, 2);

        \Eventy::addFilter('mailbox.folders', function($folders, $mailbox) {
            if (count($folders) && $mailbox->id > 0) {
                $folder = new Folder();
                $folder->id = (-1)*self::FOLDER_TYPE_SENT;
                $folder->type = self::FOLDER_TYPE_SENT;
                // Set any available mailbox.
                $folder->mailbox_id = $mailbox->id;

                $folders->push($folder);
            }

            return $folders;
        }, 7, 2);

        \Eventy::addFilter('conversations_table.column_title_date', function($title, $folder) {
            if ($folder->type == self::FOLDER_TYPE_SENT) {
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
            __DIR__.'/../Config/config.php' => config_path('sentfolder.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'sentfolder'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/sentfolder');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/sentfolder';
        }, \Config::get('view.paths')), [$sourcePath]), 'sentfolder');
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
