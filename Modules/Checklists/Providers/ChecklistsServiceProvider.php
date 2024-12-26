<?php

namespace Modules\Checklists\Providers;

use App\Conversation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('CL_MODULE', 'checklists');

class ChecklistsServiceProvider extends ServiceProvider
{
    // Custom flag for search.
    const SEARCH_CUSTOM = 'cl';

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
            $styles[] = \Module::getPublicPath(CL_MODULE).'/css/module.css';
            return $styles;
        });
        
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(CL_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(CL_MODULE).'/js/module.js';

            return $javascripts;
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view')) {
                echo 'clInit();';
            }
            if (\Route::is('conversations.search')) {
                echo 'clInitPickConv();';
            }
        });

        // Show block in conversation
        \Eventy::addAction('conversation.after_subject_block', function($conversation, $mailbox) {
            $items = \ChecklistItem::where('conversation_id', $conversation->id)
                ->orderBy('id')
                ->get();

            $linked_conversation_ids = [];

            $linked_conversation_ids = \ChecklistItem::where('linked_conversation_id', $conversation->id)
                ->pluck('conversation_id');

            $linked_conversations = [];
            if (count($linked_conversation_ids)) {
                $linked_conversations = Conversation::whereIn('id', $linked_conversation_ids)->get();
            }

            echo \View::make('checklists::partials/conv_view', [
                'items' => $items,
                'linked_conversations' => $linked_conversations,
            ])->render();
        }, 40, 2);

        // Pick conversation.
        \Eventy::addAction('conversations_table.preview_prepend', function($conversation, $params) {
            $request = request();
            if (!empty($request->f) && !empty($request->f['custom']) && $request->f['custom'] == self::SEARCH_CUSTOM) {
            ?><button type="button" class="btn btn-default btn-xs kn-btn-pick" data-conv-number="<?php echo htmlspecialchars($conversation->number) ?>"><?php echo __('Pick Conversation').' #'. $conversation->number ?></button> <?php
            }
        }, 20, 2);

        // Conversation status changed.
        \Eventy::addAction('conversation.status_changed', function($conversation, $user, $changed_on_reply) {
            if ($conversation->status == Conversation::STATUS_CLOSED) {
                $items = \ChecklistItem::where('linked_conversation_id', $conversation->id)->get();
                foreach ($items as $item) {
                    $item->status = \ChecklistItem::STATUS_COMPLETED;
                    $item->save();
                }
            }
        }, 20, 3);

        \Eventy::addFilter('workflows.actions_config', function($actions, $mailbox_id = null) {

            $actions['dummy']['items']['add_checklist'] = [
                'title' => __('Add Checklist'),
                //'operators' => $operators,
                'values_custom' => true
            ];
            return $actions;
        }, 20, 2);

        \Eventy::addAction('workflows.values_custom', function($type, $value, $mode, $and_i, $row_i, $data) {
            if ($type != 'add_checklist') {
                return;
            }
            ?>
                <textarea class="form-control" cols="50" rows="3" wrap="off" name="<?php echo $mode ?>[<?php echo $and_i ?>][<?php echo $row_i ?>][value]" placeholder="<?php echo __('Enter tasks (one per line)') ?>" /><?php echo htmlspecialchars($value); ?></textarea>
            <?php
            
        }, 20, 6);

        \Eventy::addFilter('workflow.perform_action', function($performed, $type, $operator, $value, $conversation, $workflow) {
            if ($type == 'add_checklist' && \Module::isActive('checklists')) {
                $items = preg_split('/\r\n|\r|\n/', $value);
                foreach ($items as $value) {
                    $value = trim($value);
                    if ($value) {
                        \ChecklistItem::create($conversation->id, ['text' => $value]);
                    }
                }
                return true;
            }

            return $performed;
        }, 20, 6);
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
            __DIR__.'/../Config/config.php' => config_path('checklists.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'checklists'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/checklists');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/checklists';
        }, \Config::get('view.paths')), [$sourcePath]), 'checklists');
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
