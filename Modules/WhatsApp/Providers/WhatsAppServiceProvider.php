<?php

namespace Modules\WhatsApp\Providers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

//require_once __DIR__.'/../vendor/autoload.php';

class WhatsAppServiceProvider extends ServiceProvider
{
    const DRIVER = 'whatsapp';

    // Communication channel.
    // The value must be between 1 and 255. Official modules use channel numbers below 100.
    // Non-official - above 100. To avoid conflicts with other modules contact FreeScout Team
    // to get your Channel number: https://freescout.net/contact-us/
    const CHANNEL = 13;
    
    const CHANNEL_NAME = 'WhatsApp';

    const SYSTEM_CHATAPI = 1;
    const SYSTEM_TWILIO  = 2;

    const SYSTEM_CHATAPI_NAME  = '1msg.io';

    public static $system_names = [
        self::SYSTEM_CHATAPI => self::SYSTEM_CHATAPI_NAME,
        self::SYSTEM_TWILIO => 'Twilio',
    ];

    const LOG_NAME = 'whatsapp_errors';
    const SALT = 'dk23lsd8';

    const TWILIO_API_URL = 'https://api.twilio.com/2010-04-01/Accounts';

    public static $skip_messages = [
        // '%%%_IMAGE_%%%',
        // '%%%_VIDEO_%%%',
        // '%%%_FILE_%%%',
        // '%%%_AUDIO_%%%',
        // '%%%_LOCATION_%%%',
    ];

    const API_METHOD_SEND = 'sendMessage';
    const API_METHOD_SEND_FILE = 'sendFile';
    const API_METHOD_SET_WEBOOK = 'webhook';

    //const API_STATUS_SUCCESS = 'successful';

    // Audios are not supported.
    const API_MESSAGE_TYPE_TEXT = 'chat';
    const API_MESSAGE_TYPE_INTERACTIVE = 'interactive';
    const API_MESSAGE_TYPE_IMAGE = 'image';
    const API_MESSAGE_TYPE_VIDEO = 'video';
    const API_MESSAGE_TYPE_LOCATION = 'location';
    const API_MESSAGE_TYPE_DOCUMENT = 'document';
    const API_MESSAGE_TYPE_AUDIO = 'audio';
    const API_MESSAGE_TYPE_CONTACT = 'vcard';
    const API_MESSAGE_TYPE_STICKER = 'sticker';
    const API_MESSAGE_TYPE_VOICE = 'voice';

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
                echo \View::make('whatsapp::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35);

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['whatsapp'] = [
                'mailboxes.whatsapp.settings',
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
                \WhatsApp::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox);
                return;
            }

            // We send only the last reply.
            $replies = $replies->sortByDesc(function ($item, $key) {
                return $item->id;
            });
            $thread = $replies[0];

            // If thread is draft, it means it has been undone
            $thread = $thread->fresh();
            
            if ($thread->isDraft() || $thread->imported) {
                return;
            }
            
            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\WhatsApp::DRIVER] ?? [];

            if (self::getSystem($config) == self::SYSTEM_CHATAPI) {
                // Send attachments first.
                if ($thread->has_attachments) {
                    foreach ($thread->attachments as $attachment) {

                        // API does not accept files with GET parameters
                        //$attachment_data = 'data:'.$attachment->mime_type.';base64,'.base64_encode($attachment->getFileContents());

                        $params = [
                            'filename' => $attachment->file_name,
                            'body' => $attachment->url(),
                            //'body' => $attachment_data,
                            'phone' => $channel_id,
                        ];

                        $response = self::apiCall($config, self::API_METHOD_SEND_FILE, $params);
                        if (empty($response['sent'])) {
                            \WhatsApp::log('Error occurred sending file via WhatsApp to '.$channel_id./*'. Request: '.json_encode($params).*/': Response: '.json_encode($response).'; Request: '.json_encode($params), $conversation->mailbox, true, self::SYSTEM_CHATAPI);
                        }
                    }
                }

                $params = [
                    'body' => $text,
                    'phone' => $channel_id,
                ];

                $response = self::apiCall($config, self::API_METHOD_SEND, $params);
                if (empty($response['sent'])) {
                    \WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.json_encode($response), $conversation->mailbox, true);
                }
            } else {
                // Send attachments first.
                if ($thread->has_attachments) {
                    foreach ($thread->attachments as $attachment) {
                        try {
                            self::twilioSendMessage($conversation->mailbox, $channel_id, '', $attachment->url());
                        } catch (\Exception $e) {
                            \WhatsApp::log('Error occurred sending file via WhatsApp to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                        }
                    }
                }
                try {
                    self::twilioSendMessage($conversation->mailbox, $channel_id, $text);
                } catch (\Exception $e) {
                    \WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                }
            }

        }, 20, 3);
    }

    public static function processIncomingMessage($user_phone, $user_name, $text, $mailbox, $attachments = [])
    {
        if (in_array($text, self::$skip_messages) && empty($attachments)) {
            return false;
        }

        if (!$user_phone && !$user_name) {
            \WhatsApp::log('Empty user.', $mailbox, true);
            return;
        }

        if (!$user_name) {
            $user_name = $user_phone;
        }

        // Get or creaate a customer.
        $channel = \WhatsApp::CHANNEL;
        $channel_id = $user_phone;

        $customer = Customer::getCustomerByChannel($channel, $channel_id);

        // Try to find customer by phone number.
        if (!$customer) {
            // Get first customer by phone number.
            $customer = Customer::findByPhone($channel_id);
            // For now we are searching for a customer without a channel
            // and link the obtained customer to the channel.
            if ($customer) {
                $customer->addChannel($channel, $channel_id);
            }
        }
        
        if (!$customer) {
            $customer_data = [
                // These two lines will add a record to customer_channel via observer.
                'channel' => $channel,
                'channel_id' => $channel_id,
                'first_name' => $user_name,
                'last_name' => '',
                'phones' => Customer::formatPhones([$user_phone])
            ];

            $customer = Customer::createWithoutEmail($customer_data);

            if (!$customer) {
                \WhatsApp::log('Could not create a customer.', $mailbox, true);
                return;
            }
        }

        // Get last customer conversation or create a new one.
        $conversation = Conversation::where('mailbox_id', $mailbox->id)
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->orderBy('created_at', 'desc')
            ->first();

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

    // https://www.twilio.com/docs/whatsapp/api?code-sample=code-send-an-outbound-freeform-whatsapp-message&code-language=curl&code-sdk-version=json
    public static function twilioSendMessage($mailbox, $to, $body, $media_url = '')
    {
        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        if (empty($settings['twilio_sid']) 
            || empty($settings['twilio_token'])
            || empty($settings['twilio_phone_number'])
        ) {
            throw new \Exception('Can not send a message via Twilio as some parameters are missing. Make sure to enter Account SID, Auth Token and Twilio Phone Number', 1);
            return;
        }

        $api_url = self::TWILIO_API_URL.'/'.$settings['twilio_sid'].'/Messages.json';

        $request_body = [
            'From' => 'whatsapp:+'.self::twilioSanitizePhoneNumber($settings['twilio_phone_number']),
            'Body' => $body,
            'To'   => 'whatsapp:+'.self::twilioSanitizePhoneNumber($to),
        ];
        // https://www.twilio.com/docs/sms/send-messages#include-media-in-your-messages
        if (!empty($media_url)) {
            $request_body['MediaUrl'] = $media_url;
        }
   
        $ch = curl_init($api_url);

        curl_setopt($ch, CURLOPT_USERPWD, $settings['twilio_sid'] . ":" . $settings['twilio_token']);
        //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // if ($http_method == self::API_METHOD_POST 
        //     || $http_method == self::API_METHOD_PUT
        //     || $http_method == self::API_METHOD_PATCH
        // ) {
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request_body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \Helper::setCurlDefaultOptions($ch);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $json_response = curl_exec($ch);

        $response = json_decode($json_response, true);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch), 1);
        }

        curl_close($ch);

        if (!empty($response['error_code']) || !empty($response['code'])) {
            throw new \Exception('API error: '.json_encode($response), 1);
        }
        
        return $response;
    }

    public static function twilioSanitizePhoneNumber($phone_number)
    {
        return preg_replace("#[^0-9]#", '', $phone_number);
    }

    // For backward compatibility.
    public static function getSystem($settings)
    {
        $system = $settings['system'] ?? 0;

        if (!$system) {
            return self::SYSTEM_CHATAPI;
        } else {
            return $system;
        }
    }

    public static function getSystemName($system)
    {
        return self::$system_names[$system] ?? self::SYSTEM_CHATAPI_NAME;
    }

    public static function getWebhookUrl($mailbox_id, $system = self::SYSTEM_CHATAPI)
    {
        return route('whatsapp.webhook', [
            'mailbox_id' => $mailbox_id,
            'mailbox_secret' => \WhatsApp::getMailboxSecret($mailbox_id),
            'system' => $system
        ]);
    }

    public static function processOwnMessage($user, $customer_phone, $text, $mailbox, $attachments = [], $chat_name = '')
    {
        if (in_array($text, self::$skip_messages) && empty($attachments)) {
            return false;
        }

        if (!$user) {
            \WhatsApp::log('Empty user when processing own message.', $mailbox, true);
            return;
        }

        // Do not import messages sent from FreeScout as user messages.
        // if (starts_with($text, '💬 ')) {
        //     return;
        // }

        // Skip if the text is a URL of the attachment from a message sent by
        // a support agent before.
        if (preg_match("#^https?://#", $text)) {
            $url_parts = parse_url(html_entity_decode($text));

            parse_str($url_parts['query'] ?? '', $url_params);

            if (!empty($url_params['id']) && !empty($url_params['token'])) {
                $attachment = Attachment::find($url_params['id']);
                if ($attachment && $attachment->getToken() == $url_params['token']) {
                    return;
                }
            }
        }

        // Get or creaate a customer.
        $channel = \WhatsApp::CHANNEL;
        $channel_id = $customer_phone;
        $conversation = null;

        $customer = Customer::where('channel', $channel)
            ->where('channel_id', $channel_id)
            ->first();

        if ($customer) {
            // Get last customer conversation.
            $conversation = Conversation::where('mailbox_id', $mailbox->id)
                ->where('customer_id', $customer->id)
                ->where('channel', $channel)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {

            $customer_data = [
                'channel' => $channel,
                'channel_id' => $channel_id,
                'first_name' => $chat_name,
                'last_name' => '',
                'phones' => Customer::formatPhones([$customer_phone])
            ];

            $customer = Customer::createWithoutEmail($customer_data);

            if (!$customer) {
                \WhatsApp::log('Could not create a customer.', $mailbox, true);
                return;
            }
        }

        if ($conversation) {
            // Check if there is already such message from support agent in this conversation.
            $last_user_reply = Thread::where('conversation_id', $conversation->id)
                //->where('body', $text)
                ->where('type', Thread::TYPE_MESSAGE)
                ->orderBy('created_at', 'desc')
                ->first();

            $text_prepared = html_entity_decode($text);
            $text_prepared = \Helper::htmlToText($text_prepared, false);
            $text_prepared = str_replace('💬 ', '', $text_prepared);
            $text_prepared = preg_replace("#[\r\n\t ]#", '', $text_prepared);

            $last_user_reply_text = preg_replace("#[\r\n\t ]#", '', $last_user_reply->getBodyAsText());

            if ($last_user_reply && $last_user_reply_text == $text_prepared) {
                return;
            }

            // Create thread in existing conversation.
            Thread::createExtended([
                    'type' => Thread::TYPE_MESSAGE,
                    'created_by_user_id' => $user->id,
                    'body' => $text,
                    'attachments' => $attachments,
                    'imported' => true,
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
                    'type' => Thread::TYPE_MESSAGE,
                    'created_by_user_id' => $user->id,
                    'body' => $text,
                    'attachments' => $attachments,
                    'imported' => true,
                ]],
                $customer
            );
           //\WhatsApp::log('Could not find customer conversation when importing own message sent directly from WhatsApp on mobile phone as a response to customer. Customer phone number: '.$customer_phone, $mailbox, true);
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

    public static function log($text, $mailbox = null, $is_webhook = true, $system = self::SYSTEM_CHATAPI)
    {
        \Helper::log(\WhatsApp::LOG_NAME, '['.self::CHANNEL_NAME.($is_webhook ? ' Webhook' : '').' - '.self::getSystemName($system).'] '.($mailbox ? '('.$mailbox->name.') ' : '').$text);
    }

    public static function setWebhook($config, $mailbox_id, $remove = false)
    {
        $params = [
            'webhookUrl' => self::getWebhookUrl($mailbox_id, self::SYSTEM_CHATAPI)
        ];

        // Not implemented
        if ($remove) {
            $params['webhookUrl'] = 'https://webhook-disabled.doesnotexist';
        }

        $response = self::apiCall($config, self::API_METHOD_SET_WEBOOK, $params);

        $output = json_encode($response);

        if ($response && !empty($response['set'])) {
            return [
                'result' => true,
                'msg' => $output,
            ];
        } else {
            return [
                'result' => false,
                'msg' => $output,
            ];
        }
    }

    /**
     * https://my.1msg.io/documentation
     */
    public static function apiCall($config, $method, $params)
    {
        $channel_id = $config['instance'] ?? '';
        $token = $config['token'] ?? '';

        $url = 'https://api.1msg.io/'.$channel_id.'/'.$method.'?'.http_build_query(['token' => $token]);

        try {
            $response = (new \GuzzleHttp\Client())->request('POST', $url, [
                'json' => $params,
                'proxy' => config('app.proxy'),
            ]);
        } catch (\Exception $e) {
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        }

        // https://guzzle3.readthedocs.io/http-client/response.html
        if ($response->getStatusCode() == 200) {
            return \Helper::jsonToArray($response->getBody()->getContents());
        } else {
            return [
                'result' => false,
                'msg' => 'API error: '.$response->getStatusCode(),
            ];
        }
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
            __DIR__.'/../Config/config.php' => config_path('whatsapp.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'whatsapp'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/whatsapp');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/whatsapp';
        }, \Config::get('view.paths')), [$sourcePath]), 'whatsapp');
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
