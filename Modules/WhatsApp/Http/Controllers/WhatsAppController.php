<?php

namespace Modules\WhatsApp\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WhatsAppController extends Controller
{
    public function webhooks(Request $request, $mailbox_id, $mailbox_secret, $system = \WhatsApp::SYSTEM_CHATAPI)
    {
        if (class_exists('Debugbar')) {
            \Debugbar::disable();
        }

        $mailbox = Mailbox::find($mailbox_id);

        if (!$mailbox || \WhatsApp::getMailboxSecret($mailbox_id) != $request->mailbox_secret
        ) {
            \WhatsApp::log('Incorrect webhook URL: '.url()->current(), $mailbox ?? null, true, $system);
            abort(404);
        }

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        if (empty($settings['enabled'])) {
            \WhatsApp::log('Webhook triggered but WhatsApp integration is not enabled.', $mailbox ?? null, true, $system);
            abort(404);
        }

        if (\WhatsApp::getSystem($settings) != $system) {
            \WhatsApp::log('Webhook triggered by '.\WhatsApp::getSystemName($system).', but currently '.\WhatsApp::getSystemName(\WhatsApp::getSystem($settings)).' is enabled.', $mailbox ?? null, true, $system);
            abort(404);
        }

        $data = $request->all();

        // 1msg.io.
        if ($system == \WhatsApp::SYSTEM_CHATAPI) {

            if (empty($data['messages'])) {
                // Do not log: {"ack":[{"id":"gBGHVRmZUZdzLwIJJ3C5ey3WeP8i","chatId":"5519995197732@c.us","status":"read"}],"channelId":457431}
                if (empty($data['ack'])) {
                    \WhatsApp::log('Webhook triggered but invalid data received: '.json_encode($data), $mailbox, true, $system);
                }
                abort(404);
            }

            //\Log::error('Webhook data: '.json_encode($data));

            foreach ($data['messages'] as $message) {
                $sender_phone = $message['author'] ?? '';
                $sender_phone = preg_replace("/@.*/", '', $sender_phone);
                $sender_name = $message['senderName'] ?? '';

                $from_customer = true;
                $customer_phone = $message['chatId'] ?? '';
                $customer_phone = preg_replace("/@.*/", '', $customer_phone);
                $user = null;

                if (!empty($message['fromMe'])) {
                    // Message from user.
                    $from_customer = false;

                    if (!$customer_phone) {
                        \WhatsApp::log('Could not import your own message sent directly from WhatsApp on mobile phone as a response to customer. Customer phone number which should be passed to Webhook in chatId parameter is empty. Webhook data: '.json_encode($data), $mailbox, true, $system);
                        return;
                    }

                    // Try to find user by phone number.
                    $user = User::where('phone', $sender_phone)->first();
                    if (!$user) {
                        //\WhatsApp::log('Could not import your own message sent directly from WhatsApp on mobile phone as a response to customer. Could not find the user with the following phone number: '.$sender_phone.'. Make sure to set this phone number to one of the users in FreeScout. Webhook data: '.json_encode($data), $mailbox, true, $system);
                        \WhatsApp::log('Could not import your own message sent as a response to a customer. Could not find the user with the following phone number: '.$sender_phone.'. Make sure to set this phone number to one of the users in FreeScout. Webhook data: '.json_encode($data), $mailbox, true, $system);
                        return;
                    }
                }

                $attachments = [];
                $body = '';
                $message_type = $message['type'] ?? '';

                switch ($message_type) {
                    case \WhatsApp::API_MESSAGE_TYPE_IMAGE:  
                    case \WhatsApp::API_MESSAGE_TYPE_VIDEO:  
                        if (empty($message['caption'])) {
                            if ($message_type == \WhatsApp::API_MESSAGE_TYPE_IMAGE) {
                                $body = __('Image');
                            } else {
                                $body = __('Video');
                            }
                        }

                        $attachments[] = [
                            'file_name' => \Helper::remoteFileName($message['body']),
                            'file_url' => $message['body']
                        ];
                        break;

                    case \WhatsApp::API_MESSAGE_TYPE_LOCATION:
                        $body = __('Location').': '.$message['body'];
                        break;

                    case \WhatsApp::API_MESSAGE_TYPE_DOCUMENT:
                        $body = __('Document');
                        $attachments[] = [
                            'file_name' => $message['caption'] ?? \Helper::remoteFileName($message['body']),
                            'file_url' => $message['body']
                        ];
                        $message['caption'] = '';
                        break;

                    case \WhatsApp::API_MESSAGE_TYPE_AUDIO:
                    case \WhatsApp::API_MESSAGE_TYPE_VOICE:
                        $body = __('Audio');
                        $attachments[] = [
                            'file_name' => \Helper::remoteFileName($message['body']),
                            'file_url' => $message['body']
                        ];
                        break;

                    case \WhatsApp::API_MESSAGE_TYPE_CONTACT:
                        $body = __('Contact')."\n\n".$message['body'];
                        break;

                    default:
                    //case \WhatsApp::API_MESSAGE_TYPE_TEXT:
                    //case \WhatsApp::API_MESSAGE_TYPE_INTERACTIVE:
                    //case \WhatsApp::API_MESSAGE_TYPE_STICKER:
                        if (!empty($message['body']) && !is_array($message['body'])) {
                            $body = $message['body'] ?? '';
                        }
                }

                $body = htmlspecialchars($body);

                $body = nl2br($body);

                if (!empty($message['caption'])) {
                    $body = $body."<br><br>".$message['caption'];
                }

                // 1msg.io sends only quotedMsgId, but we keep this just in case.
                if (!empty($message['quotedMsgBody'])) {
                    $body = '<blockquote>'.$message['quotedMsgBody'].'</blockquote><br>'.$body;
                }

                // There is no such parameter in 1msg.io, but we keep it just in case.
                if (!empty($message['file']) && empty($attachments)) {
                    $attachments[] = [
                        'file_name' => \Helper::remoteFileName($message['file']),
                        'file_url' => $message['file']
                    ];
                }

                if ($from_customer) {
                    \WhatsApp::processIncomingMessage($sender_phone, $sender_name, $body, $mailbox, $attachments);
                } else {
                    // From user.
                    \WhatsApp::processOwnMessage($user, $customer_phone, $body, $mailbox, $attachments, $message['chatName'] ?? $customer_phone);
                }
            }

            // $botman->hears('(.*)', function ($bot, $text) use ($mailbox) {
            //     \Log::error('text: '.$text);
            //     \WhatsApp::processIncomingMessage($bot, $text, $mailbox);
            // });

            // $botman->receivesFiles(function($bot, $files) use ($mailbox) {

            //     \WhatsApp::processIncomingMessage($bot, __('File(s)'), $mailbox, $files);

            //     // foreach ($files as $file) {

            //     //     $url = $file->getUrl(); // The direct url
            //     //     $payload = $file->getPayload(); // The original payload
            //     // }
            // });

            // $botman->receivesImages(function($bot, $images) use ($mailbox) {
            //     \WhatsApp::processIncomingMessage($bot, __('Image(s)'), $mailbox, $images);

            //     // foreach ($images as $image) {

            //     //     $url = $image->getUrl(); // The direct url
            //     //     $title = $image->getTitle(); // The title, if available
            //     //     $payload = $image->getPayload(); // The original payload
            //     // }
            // });

            // $botman->receivesVideos(function($bot, $videos) use ($mailbox) {
            //     \WhatsApp::processIncomingMessage($bot, __('Video(s)'), $mailbox, $videos);
            //     // foreach ($videos as $video) {

            //     //     $url = $video->getUrl(); // The direct url
            //     //     $payload = $video->getPayload(); // The original payload
            //     // }
            // });

            // $botman->receivesAudio(function($bot, $audios) use ($mailbox) {
            //     \WhatsApp::processIncomingMessage($bot, __('Audio'), $mailbox, $audios);
            //     // foreach ($audios as $audio) {

            //     //     $url = $audio->getUrl(); // The direct url
            //     //     $payload = $audio->getPayload(); // The original payload
            //     // }
            // });

            // $botman->receivesLocation(function($bot, $location) use ($mailbox) {
            //     \WhatsApp::processIncomingMessage($bot, __('Location: '.$location->getLatitude().','.$location->getLongitude()), $mailbox);
            //     // $lat = $location->getLatitude();
            //     // $lng = $location->getLongitude();
            // });

            //$botman->listen();
        }

        // Twilio.
        if ($system == \WhatsApp::SYSTEM_TWILIO) {
            //\WhatsApp::log('Data: '.json_encode($data), $mailbox, true, $system);
            // \WhatsApp::log('settings: '.json_encode($settings), $mailbox, true, $system);

            // Check SID.
            if (($settings['twilio_sid'] ?? '') != $request->input('AccountSid')) {
                \WhatsApp::log('Incorrect Account SID received in webhook: '.$request->input('AccountSid'), $mailbox, true, $system);
                abort(404);
            }
            $customer_phone = str_replace('whatsapp:+', '', $data['From'] ?? '');
            $customer_name = $data['ProfileName'] ?? '';
            // Attachment may be sent without description.
            $body = $data['Body'] ?? '';

            if (!empty($data['Latitude']) && !empty($data['Longitude'])) {
                $body = $data['Latitude'].','.$data['Longitude'];
            }

            if (!$body) {
                $body = '&nbsp;';
            }

            // Build files array.
            $files = [];
            $files_count = (int)$request->input('NumMedia');

            for ($i = 0; $i < $files_count; $i++) {
                if (!empty($data["MediaUrl".$i])) {
                    
                    $file_url = $data["MediaUrl".$i] ?? '';

                    // https://github.com/freescout-helpdesk/freescout/issues/3906
                    // To avoid https://www.twilio.com/docs/api/errors/20003
                    if (strstr($file_url, '/Accounts/')) {
                        // Get media files with HTTP Basic Authentication enabled in Twilio
                        // https://www.twilio.com/docs/sms/tutorials/how-to-receive-and-download-images-incoming-mms/php-laravel
                        if (!empty($settings['twilio_sid']) && !empty($settings['twilio_token'])) {
                            $file_url = str_replace('https://', 'https://'.$settings['twilio_sid'].':'.$settings['twilio_token'].'@', $file_url);
                        }
                    }
                    $file_data = [
                        'file_name' => \Helper::remoteFileName($file_url),
                        'file_url'  => $file_url,
                        'mime_type' => $data["MediaContentType".$i] ?? $data["MediaContentType"] ?? '',
                    ];

                    // MediaUrl returns a real URL in a "Location:" header.
                    // Try to get mime type by following redirects.
                    $last_file_url = $file_url;
                    $last_content_type = '';
                    for ($i = 0; $i < 10; $i++) { 
                        $headers = get_headers($last_file_url);
                        $has_redirect = false;
                        foreach ($headers as $header) {
                            if (preg_match("#^location:(.*)#i", $header, $m) && !empty($m[1])) {
                                $last_file_url = trim($m[1]);
                                $has_redirect = true;
                            }
                            if (preg_match("#^content-type:(.*)#i", $header, $m) && !empty($m[1])) {
                                $last_content_type = trim($m[1]);
                            }
                        }
                        if (!$has_redirect) {
                            break;
                        }
                    }
                    if ($last_content_type) {
                        if (preg_match("#/(.*)#", $last_content_type, $m) && !empty($m[1])) {
                            $file_data['mime_type'] = $last_content_type;
                            if (!strstr($file_data['file_name'], '.')) {
                                $file_data['file_name'] = $file_data['file_name'].'.'.$m[1];
                            }
                        }
                    }
                    if ($last_file_url) {
                        $file_data['file_url'] = $last_file_url;
                    }

                    $files[] = $file_data;
                }
            }

            //\WhatsApp::log('data: '.json_encode($data).'; files: '.json_encode($files), $mailbox, true, $system);

            \WhatsApp::processIncomingMessage($customer_phone, $customer_name, $body, $mailbox, $files);
        }
    }

    /**
     * Settings.
     */
    public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        return view('whatsapp::settings', [
            'mailbox'   => $mailbox,
            'settings'   => $settings,
        ]);
    }

    /**
     * Settings save.
     */
    public function settingsSave(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        $settings = $request->settings;
        $settings_prev = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        $webhooks_enabled = (int)($mailbox->meta[\WhatsApp::DRIVER]['enabled'] ?? 0);

        $settings['enabled'] = (int)($settings['enabled'] ?? 0);

        // Try to add a webhook.
        if (\WhatsApp::getSystem($settings) == \WhatsApp::SYSTEM_CHATAPI 
            && (!$webhooks_enabled || \WhatsApp::getSystem($settings_prev) != \WhatsApp::SYSTEM_CHATAPI)
            && (int)$settings['enabled']
        ) {
            $result = \WhatsApp::setWebhook($settings, $mailbox->id);

            if (!$result['result']) {
                $settings['enabled'] = false;
                \Session::flash('flash_error', '('.\WhatsApp::SYSTEM_CHATAPI_NAME.') '.__('Error occurred setting up a Whatsapp webhook').': '.$result['msg']);
            }
        }
        // Remove webhook.
        if ((\WhatsApp::getSystem($settings) == \WhatsApp::SYSTEM_CHATAPI 
                || \WhatsApp::getSystem($settings_prev) == \WhatsApp::SYSTEM_CHATAPI
            )
            && $webhooks_enabled
            && !(int)$settings['enabled']
        ) {
            \Log::error('remove hook');
            $result = \WhatsApp::setWebhook($settings, $mailbox->id, true);

            if (!$result['result']) {
                \Session::flash('flash_error', '('.\WhatsApp::SYSTEM_CHATAPI_NAME.') '.__('Error occurred removing a Whatsapp webhook').': '.$result['msg']);
            }
        }

        $mailbox->setMetaParam(\WhatsApp::DRIVER, $settings);
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('mailboxes.whatsapp.settings', ['mailbox_id' => $mailbox_id]);
    }
}
