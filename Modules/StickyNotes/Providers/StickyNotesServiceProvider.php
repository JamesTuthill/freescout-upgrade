<?php

namespace Modules\StickyNotes\Providers;

use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class StickyNotesServiceProvider extends ServiceProvider
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
        /*\Eventy::addAction('thread.menu', function($thread) {
            if ($thread->type != Thread::TYPE_NOTE) {
                return;
            }
            ?>
            <li>
                <a href="<?php echo route('test', ['thread_id' => $thread->id]) ?>"><?php echo __("Pin") ?></a>
            </li>
            <?php
        });*/

        \Eventy::addFilter('middleware.web.custom_handle.response', function($response, $request) {

            if ($request->route()->getName() != 'conversations.view' || !$request->isMethod('GET')) {
                return $response;
            }

            $conversation_id = $request->route('id');

            if (empty($request->sn_action) || empty($request->sn_thread_id) || empty($conversation_id)) {
                return $response;
            }

            $thread = Thread::find($request->sn_thread_id);

            if ($thread && $thread->conversation_id == $conversation_id) {

                if ($request->sn_action == 'pin') {
                    // Pin.
                    $thread->setMeta('sn.pinned', now()->getTimestamp());
                    $thread->save();
                } else {
                    // Unpin.
                    if (!empty($thread->meta['sn.pinned'])) {
                        $metas = $thread->meta;
                        unset($metas['sn.pinned']);
                        $thread->meta = $metas;
                        $thread->save();
                    }
                }
            }

            // Reload the page.
            $url_data = $request->all();

            unset($url_data['sn_action']);
            unset($url_data['sn_thread_id']);

            $url_data['id'] = $conversation_id;

            return redirect()->route('conversations.view', $url_data);
        }, 20, 2);

        \Eventy::addAction('thread.info.prepend', function($thread) {
            if ($thread->type != Thread::TYPE_NOTE) {
                return;
            }

            $url_data = request()->all();

            $url_data['sn_thread_id'] = $thread->id;

            if (!empty($thread->meta['sn.pinned'])) {
                $url_data['sn_action'] = 'unpin';
                ?>
                <a href="?<?php echo http_build_query($url_data) ?>" class="thread-info-icon link-active" data-toggle="tooltip" title="<?php echo __('Unpin') ?>"><i class="glyphicon glyphicon-pushpin"></i></a> 
                <?php
            } else {
                $url_data['sn_action'] = 'pin';
                ?>
                <a href="?<?php echo http_build_query($url_data) ?>" class="thread-info-icon" data-toggle="tooltip" title="<?php echo __('Pin to Top') ?>"><i class="glyphicon glyphicon-pushpin"></i></a> 
                <?php
            }
        });

        \Eventy::addFilter('conversation.view.threads', function($threads) {
            return $threads->sortByDesc(function ($thread, $key) {
                if ($thread->isNote() && !empty($thread['meta']['sn.pinned'])) {
                    return $thread->created_at->getTimestamp() + (int)$thread['meta']['sn.pinned'];
                } else {
                    return $thread->created_at->getTimestamp();
                }
            });
        });
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
            __DIR__.'/../Config/config.php' => config_path('stickynotes.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'stickynotes'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/stickynotes');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/stickynotes';
        }, \Config::get('view.paths')), [$sourcePath]), 'stickynotes');
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
