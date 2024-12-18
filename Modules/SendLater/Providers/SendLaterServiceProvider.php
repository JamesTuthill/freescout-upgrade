<?php

namespace Modules\SendLater\Providers;

use App\Conversation;
use App\User;
use App\Job;
use App\Thread;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('SL_MODULE', 'sendlater');

class SendLaterServiceProvider extends ServiceProvider
{
    const META_NAME = 'sl.schedule';

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
            $javascripts[] = \Module::getPublicPath(SL_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(SL_MODULE).'/js/module.js';

            return $javascripts;
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.create')) {
                echo 'slInitNewConv();';
            }
            if (\Route::is('conversations.view')) {
                echo 'slInitSendButtons();';
            }
        });

        \Eventy::addAction('conversation.append_send_dropdown', function($conversation, $mailbox, $new_converstion) {
            if (!$conversation->isChat()) {
                echo \View::make('sendlater::partials/send_dropdown_button', ['conversation_id' => $conversation->id])->render();
            }
        }, 100, 3);

        \Eventy::addAction('thread.before_save_from_request', function($thread, $request) {
            if (!empty($request->sl_schedule_date)) {
                $schedule_date = \Helper::sanitizeDatepickerDatetime($request->sl_schedule_date ?? '');
                // Convert user date to server date.
                try {                    
                    $user = auth()->user();

                    if ($user && $user->timezone) {                        

                        $date_user = Carbon::createFromFormat('Y-m-d H:i:00', $schedule_date, $user->timezone);
                        $schedule_date = $date_user->setTimezone(config('app.timezone'))->format('Y-m-d H:i:00');
                    }
                } catch (\Exception $e) {
                    \Helper::logException($e, 'Send Later Module');
                    return;
                }

                $thread->setMeta(\SendLater::META_NAME, $schedule_date);

                self::setConversationSchedule($thread->conversation, true);
            }
        }, 20, 2);

        \Eventy::addAction('thread.before_body', function($thread, $loop, $threads, $conversation, $mailbox) {
            if (empty($thread->meta[\SendLater::META_NAME])) {
                return;
            }

            ?>
                <div class="alert alert-info alert-narrow">
                    <i class="glyphicon glyphicon-time"></i> <strong class="small"><?php echo __('Sending scheduled on :date', ['date' =>  User::dateFormat($thread->meta[\SendLater::META_NAME])]) ?></strong>&nbsp;&nbsp; 
                    <?php if ($thread->created_by_user_id == auth()->user()->id): ?>
                        <span>
                            <a href="#" class="small sl-send-now" data-loading-text="<?php echo __('Send Now') ?>…"><?php echo __('Send Now') ?></a> | 
                            <a href="#" class="small sl-cancel-sending" data-loading-text="<?php echo __('Cancel') ?>…"><?php echo __('Cancel') ?></a>
                        </span>
                    <?php endif ?>
                </div>
            <?php
        }, 10, 5);

        \Eventy::addFilter('backgound_action.dispatch_delay', function($delay, $action, $params) {
            
            if (!in_array($action, ['conversation.created_by_user', 'conversation.user_replied'])) {
                return $delay;
            }

            if (!empty($params[1]) && !empty($params[1]->meta) && !empty($params[1]->meta[\SendLater::META_NAME])) {

                $thread = $params[1];

                // Calculate delay.
                $scheduled_date = null;

                // Convert string into Carbon
                try {
                    $scheduled_date = Carbon::parse($thread->meta[\SendLater::META_NAME]);
                } catch (\Exception $e) {
                    $thread->unsetMeta(\SendLater::META_NAME);
                    $thread->save();
                    self::setConversationSchedule($thread->conversation, false);
                    return $delay;
                }

                if (!$scheduled_date->greaterThan(now())) {
                    $thread->unsetMeta(\SendLater::META_NAME);
                    $thread->save();
                    self::setConversationSchedule($thread->conversation, false);
                    return $delay;
                }

                return abs(now()->diffInSeconds($scheduled_date));
            }

            return $delay;
        }, 20, 3);

        \Eventy::addFilter('conversation.send_reply_to_customer_delay', function($delay, $conversation, $threads) {
            
            $thread = $threads->first();

            if ($thread && !empty($thread->meta) && !empty($thread->meta[\SendLater::META_NAME])) {

                // Calculate delay.
                $scheduled_date = null;

                // Convert string into Carbon
                try {
                    $scheduled_date = Carbon::parse($thread->meta[\SendLater::META_NAME]);
                } catch (\Exception $e) {
                    // $thread->unsetMeta(\SendLater::META_NAME);
                    // $thread->save();
                    return $delay;
                }

                if (!$scheduled_date->greaterThan(now())) {
                    // $thread->unsetMeta(\SendLater::META_NAME);
                    // $thread->save();
                    return $delay;
                }

                return abs(now()->diffInSeconds($scheduled_date));
            }

            return $delay;
        }, 20, 3);

        \Eventy::addAction('email_notification.before_body', function($thread, $user, $conversation) {

            // Calculate delay.
            $scheduled_date = self::threadDateToCarbon($thread);

            if ($scheduled_date) {
                ?>
                    <div style="color: #31708f; background-color: #d9edf7; padding: 6px 15px; margin-bottom: 20px; border: 1px solid #bce8f1;">
                        <?php echo __('Sending scheduled on :date', ['date' =>  User::dateFormat($thread->meta[\SendLater::META_NAME], '', $user)]) ?>
                    </div>
                <?php
            }
        }, 20, 3);

        \Eventy::addAction('conversation.user_replied', function($conversation, $thread) {

            // Turn into drafts all previous pending replies.
            foreach ($conversation->threads as $prev_thread) {
                if ($prev_thread->id == $thread->id) {
                    continue;
                }
                if (empty($prev_thread->meta[\SendLater::META_NAME])) {
                    continue;
                }
                \SendLater::sendThreadNow($prev_thread);
            }

            if (!empty($thread->meta[\SendLater::META_NAME])) {
                $thread->unsetMeta(\SendLater::META_NAME);
                $thread->save();
                self::setConversationSchedule($conversation, false);
            }

        }, 20, 2);

        \Eventy::addAction('conversation.created_by_user', function($conversation, $thread) {

            if (!empty($thread->meta[\SendLater::META_NAME])) {
                $thread->unsetMeta(\SendLater::META_NAME);
                $thread->save();
                self::setConversationSchedule($conversation, false);
            }

        }, 20, 2);

        \Eventy::addAction('conversations_table.before_subject', function($conversation) {
            if ($conversation->scheduled) {
                echo '<i class="glyphicon glyphicon-time text-help"></i>';
            }
        }, 20, 2);

        // Datepicker has to be included in the page itself
        // as when loaded via ajax_html it causes CSP error.
        \Eventy::addAction('conversation.after_subject_block', function($conversation, $mailbox) {
            echo \View::make('partials/include_datepicker')->render();
        }, 20, 2);

        \Eventy::addAction('conversation.create_form.after_subject', function() {
            echo \View::make('partials/include_datepicker')->render();
        });
    }

    public static function setConversationSchedule($conversation, $is_schedule)
    {
        $conversation->scheduled = $is_schedule;
        $conversation->save();
    }

    public static function threadDateToCarbon($thread)
    {
        if ($thread && !empty($thread->meta) && !empty($thread->meta[\SendLater::META_NAME])) {

            // Calculate delay.
            $scheduled_date = null;

            // Convert string into Carbon
            try {
                $scheduled_date = Carbon::createFromFormat('Y-m-d H:i:00', $thread->meta[\SendLater::META_NAME]);
            } catch (\Exception $e) {
                return null;
            }

            if (!$scheduled_date->greaterThan(now())) {
                return null;
            }

            return $scheduled_date;
        }

        return null;
    }

    public static function sendThreadNow($thread)
    {
        $jobs = self::getJobs($thread->id);

        foreach ($jobs as $job) {
            $job->available_at = $job->created_at->timestamp;
            $job->save();
        }

        $thread->unsetMeta(\SendLater::META_NAME);
        $thread->save();

        self::setConversationSchedule($thread->conversation, false);

        return true;
    }

    public static function cancelThreadSending($thread)
    {
        $jobs = \SendLater::getJobs($thread->id);
        foreach ($jobs as $job) {
            $job->delete();
        }
        $thread->state = Thread::STATE_DRAFT;
        $thread->unsetMeta(\SendLater::META_NAME);
        $thread->save();

        self::setConversationSchedule($thread->conversation, false);

        if ($thread->conversation->threads_count == 1) {
            $thread->conversation->state = Conversation::STATE_DRAFT;
            $thread->conversation->save();
        }
    }

    public static function getJobs($thread_id)
    {
        $result = [];

        $jobs = Job::get();

        foreach ($jobs as $job) {
            try {
                $data = \Helper::jsonToArray($job->payload);
                if (!$data || empty($data['displayName'])) {
                    continue;
                }
                $command_data = unserialize($data['data']['command']);

                if ($data['displayName'] == 'App\Jobs\SendReplyToCustomer') {
                    if (empty($command_data->threads)) {
                        continue;
                    }
                    $thread = $command_data->threads->last();
                    if ($thread->id == $thread_id) {
                        $result[] = $job;
                    }
                }

                if ($data['displayName'] == 'App\Jobs\TriggerAction' 
                    && in_array($command_data->action, ['conversation.created_by_user', 'conversation.user_replied'])
                    && $command_data->params[1]
                ) {
                    $thread = $command_data->params[1];
                    if ($thread->id == $thread_id) {
                        $result[] = $job;
                    }
                }

            } catch (\Exception $e) {
                \Helper::logException($e, 'Send Later Module');
                continue;
            }
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
            __DIR__.'/../Config/config.php' => config_path('sendlater.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'sendlater'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/sendlater');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/sendlater';
        }, \Config::get('view.paths')), [$sourcePath]), 'sendlater');
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
