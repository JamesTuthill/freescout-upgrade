<?php

namespace Modules\Twitter\Providers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

require_once __DIR__.'/../vendor/autoload.php';

class TwitterServiceProvider extends ServiceProvider
{
    const DRIVER = 'twitter';

    // Communication channel.
    // The value must be between 1 and 255. Official modules use channel numbers below 100.
    // Non-official - above 100. To avoid conflicts with other modules contact FreeScout Team
    // to get your Channel number: https://freescout.net/contact-us/
    const CHANNEL = 12;
    
    const CHANNEL_NAME = 'Twitter';

    const LOG_NAME = 'twitter_errors';
    const SALT = 'o3D23sdl8';

    public static $skip_messages = [
        '%%%_IMAGE_%%%',
        '%%%_VIDEO_%%%',
        '%%%_FILE_%%%',
        '%%%_AUDIO_%%%',
        '%%%_LOCATION_%%%',
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
        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('twitter::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35);

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['twitter'] = [
                'mailboxes.twitter.settings',
            ];
            return $menu;
        });

        \Eventy::addFilter('channel.name', function($name, $channel) {
            if ($name) {
                return $name;
            }
            if ($channel == self::CHANNEL) {
                return self::CHANNEL_NAME;
            } else {
                return $name;
            }
        }, 20, 2);

        \Eventy::addFilter('channels.list', function($channels) {
            $channels[self::CHANNEL] = self::CHANNEL_NAME;
            return $channels;
        });

        \Eventy::addAction('chat_conversation.send_reply', function($conversation, $replies, $customer) {
            if ($conversation->channel != self::CHANNEL) {
                return;
            }

            $channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
                \Twitter::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox);
                return;
            }
            $driver_class = self::getDriverClass();

            if (!($conversation->mailbox->meta[\Twitter::DRIVER]['enabled'] ?? false)) {
                return;
            }

            $botman = \Twitter::getBotman($conversation->mailbox, null, false);

            if (!$botman) {
                return;
            }

            // We send only the last reply.
            $replies = $replies->sortByDesc(function ($item, $key) {
                return $item->id;
            });
            $thread = $replies[0];

            // If thread is draft, it means it has been undone
            $thread = $thread->fresh();

            if ($thread->isDraft()) {
                return;
            }

            //$botman->typesAndWaits(2);

            $text = $thread->getBodyAsText();

            // Attachments.
            // Some drivers can not send message with attachment https://github.com/botman/driver-facebook/issues/65
            if ($thread->has_attachments) {
                foreach ($thread->attachments as $attachment) {

                    //$botman_attachment = null;

                    switch ($attachment->type) {
                        // case Attachment::TYPE_IMAGE:
                        //     $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Image($attachment->url(), [
                        //         'custom_payload' => true,
                        //     ]);
                        //     break;
                        // case Attachment::TYPE_VIDEO:
                        //     $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Video($attachment->url(), [
                        //         'custom_payload' => true,
                        //     ]);
                        //     break;
                        // case Attachment::TYPE_AUDIO:
                        //     $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Audio($attachment->url(), [
                        //         'custom_payload' => true,
                        //     ]);
                        //     break;
                        default:
                            $attachment_url = $attachment->url();
                            $text .= "\n\n[".\Helper::remoteFileName($attachment_url)."] \n".$attachment_url."";
                            // $botman_attachment = new \BotMan\BotMan\Messages\Attachments\File($attachment->url(), [
                            //     'custom_payload' => true,
                            // ]);
                            break;
                    }

                    // if ($botman_attachment) {
                    //     $message = \BotMan\BotMan\Messages\Outgoing\OutgoingMessage::create('')->withAttachment($botman_attachment);
                    //     $botman->say($message, $customer->channel_id, $driver_class);
                    // }
                }
            }

            $botman->say($text, $channel_id, $driver_class);

        }, 20, 3);
    }

    public static function getDriverClass()
    {
        return \BotMan\Drivers\Twitter\TwitterDriver::class;
    }

    public static function getBotman($mailbox, $request = null, $is_webhook = false)
    {
        $driver_config = $mailbox->meta[self::DRIVER] ?? [];

        if (empty($driver_config['consumer_key']) || empty($driver_config['consumer_secret'])
            || empty($driver_config['token']) || empty($driver_config['token_secret'])
        ) {
            \Twitter::log(self::CHANNEL_NAME.' is not configured for this mailbox.', $mailbox, $is_webhook);
            return false;
        }

        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Twitter\TwitterDriver::class);

        return \BotMan\BotMan\BotManFactory::create([
            self::DRIVER => $driver_config
        ], new \BotMan\BotMan\Cache\LaravelCache());
    }

    public static function processIncomingMessage($bot, $text, $mailbox, $files = [])
    {
        if (in_array($text, self::$skip_messages) && empty($files)) {
            return false;
        }

        $messenger_user = $bot->getUser();
        
        // Skip user agent message.
        if (!$messenger_user) {
            \Twitter::log('Empty user.', $mailbox, true);
            return;
        }

        // Get or creaate a customer.
        $channel_id = $messenger_user->getId();
        if (!$channel_id) {
            \Twitter::log('User has no ID: '.json_encode($messenger_user->getInfo()).'. Check App Logs', $mailbox, true);
            return;
        }
        $channel = \Twitter::CHANNEL;

        $customer_info = null;

        $customer = Customer::getCustomerByChannel($channel, $channel_id);

        // Try to find customer by username.
        if (!$customer) {
            $customer_info = $messenger_user->getInfo();

            if (!empty($customer_info['screen_name'])) {
                $customer = Customer::findCustomersBySocialProfile(
                    Customer::SOCIAL_TYPE_TWITTER,
                    $customer_info['screen_name'],
                    self::CHANNEL
                )->first();
                
                if ($customer) {
                    $customer->addChannel($channel, $channel_id);
                }
            }
        }

        if (!$customer) {
            if (!$customer_info) {
                $customer_info = $messenger_user->getInfo();
            }

            $customer_data = [
                // These two lines will add a record to customer_channel via observer.
                'channel' => $channel,
                'channel_id' => $channel_id,
                'first_name' => $messenger_user->getFirstName() ?: $customer_info['name'] ?: $channel_id,
                'last_name' => $messenger_user->getLastName(),
                // 'social_profiles' => Customer::formatSocialProfiles([[
                //     'type' => Customer::SOCIAL_TYPE_FACEBOOK,
                // ]])
            ];

            // Social networks.
            $email = $customer_info['email'] ?? $customer_info['mail'] ?? '';
            if ($email) {
                $customer = Customer::create($email, $customer_data);
            } else {
                $customer = Customer::createWithoutEmail($customer_data);
            }
            if (!$customer) {
                \Twitter::log('Could not create a customer.', $mailbox, true);
                return;
            }
        }

        // Set photo.
        if (!$customer->photo_url) {
            $photo_url = $customer_info['profile_image_url'] ?? '';
            if ($photo_url) {
                if ($customer->setPhotoFromRemoteFile($photo_url)) {
                    $customer->save();
                }
            }
        }

        if (!empty($customer_info['screen_name'])) {
            $socials = array_merge($customer->getSocialProfiles(), [[
                'type' => Customer::SOCIAL_TYPE_TWITTER,
                'value' => 'https://twitter.com/' . $customer_info['screen_name'],
            ]]);
            $customer->setSocialProfiles($socials);
        }
        $customer->save();
        //$bot->reply('Customer ID:'.$customer->id);

        // Get last customer conversation or create a new one.
        $conversation = Conversation::where('mailbox_id', $mailbox->id)
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->orderBy('created_at', 'desc')
            ->first();

        $attachments = [];

        if (count($files)) {
            foreach ($files as $file) {
                $file_url = $file->getUrl();
                if (!$file_url) {
                    continue;
                }
                $attachments[] = [
                    'file_name' => \Helper::remoteFileName($file_url),
                    'file_url' => $file_url,
                ];
            }
        }

        $text = nl2br($text);

        if ($conversation) {
            // Create thread in existing conversation.
            Thread::createExtended([
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ],
                $conversation,
                $customer
            );
        } else {
            // Create conversation.
            Conversation::create([
                    'type' => Conversation::TYPE_CHAT,
                    'subject' => Conversation::subjectFromText($text),
                    'mailbox_id' => $mailbox->id,
                    'source_type' => Conversation::SOURCE_TYPE_WEB,
                    'channel' => $channel,
                ], [[
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ]],
                $customer
            );
        }
    }

    public static function getMailboxSecret($id)
    {
        return crc32(config('app.key').$id.'salt'.self::SALT);
    }

    public static function getMailboxVerifyToken($id)
    {
        return crc32(config('app.key').$id.'verify'.self::SALT).'';
    }

    public static function log($text, $mailbox = null, $is_webhook = true)
    {
        $mailbox_name = '';
        if ($mailbox) {
            $mailbox_name = $mailbox->name ?? '';
        }
        \Helper::log(\Twitter::LOG_NAME, '['.self::CHANNEL_NAME.($is_webhook ? ' Webhook' : '').'] '.($mailbox_name ? '('.$mailbox_name.') ' : '').$text);
    }

    public static function getMailboxOauth($mailbox)
    {
        $driver_config = $mailbox->meta[self::DRIVER] ?? [];

        $connection = new \Abraham\TwitterOAuth\TwitterOAuth(
            $driver_config['consumer_key'],
            $driver_config['consumer_secret'],
            $driver_config['token'],
            $driver_config['token_secret']
        );

        return $connection;
    }

    public static function setWebhook($token, $mailbox_id, $remove = false)
    {
        $url = route('twitter.webhook', ['mailbox_id' => $mailbox_id, 'mailbox_secret' => \Twitter::getMailboxSecret($mailbox_id)]);

        $mailbox = Mailbox::find($mailbox_id);

        $connection = \Twitter::getMailboxOauth($mailbox);

        /*
         * During the beta, access to the API is provided up to 50 account subscriptions per webhook
         * and up to one webhook per Twitter application.
         *
         * So we check if we already have a webhook defined and delete it.
         */

        // https://developer.twitter.com/en/docs/twitter-api/premium/account-activity-api/guides/getting-started-with-webhooks
        $webhooks = $connection->get('account_activity/all/prod/webhooks');

        if (isset($webhooks->errors)) {
            return [
                'result' => false,
                'msg' => json_encode($webhooks->errors),
            ];
        }

        if (count($webhooks) > 0) {
            $connection->delete('account_activity/all/prod/webhooks/'.$webhooks[0]->id);
        }

        if (!$remove) {

            $webhook = $connection->post('account_activity/all/prod/webhooks', [
                'url' => $url
            ]);

            if (isset($webhook->errors)) {
                return [
                    'result' => false,
                    'msg' => json_encode($webhook->errors),
                ];
            } else {
                // Subscribe to the webhook
                $subscribe_result = $connection->post('account_activity/all/prod/subscriptions', []);

                if (isset($subscribe_result->errors)) {
                    return [
                        'result' => false,
                        'msg' => json_encode($subscribe_result->errors),
                    ];
                }
            }
        }

        return [
            'result' => true,
            'msg' => '',
        ];
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
            __DIR__.'/../Config/config.php' => config_path('twitter.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'twitter'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/twitter');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/twitter';
        }, \Config::get('view.paths')), [$sourcePath]), 'twitter');
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
