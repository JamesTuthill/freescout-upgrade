<?php

namespace Modules\Twitter\Http\Controllers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TwitterController extends Controller
{
    public function webhooks(Request $request, $mailbox_id, $mailbox_secret)
    {
        if (class_exists('Debugbar')) {
            \Debugbar::disable();
        }

        $mailbox = Mailbox::find($mailbox_id);

        if (!$mailbox || \Twitter::getMailboxSecret($mailbox_id) != $request->mailbox_secret
        ) {
            \Twitter::log('Incorrect webhook URL: '.url()->current(), $mailbox ?? null, true);
            abort(404);
        }

        if (empty($request->crc_token) && !($mailbox->meta[\Twitter::DRIVER]['enabled'] ?? false)) {
            \Twitter::log('Webhook executed but Twitter is not enabled for this mailbox', true);
            abort(404);
        }

        $botman = \Twitter::getBotman($mailbox, $request, true);

        if (!$botman) {
            abort(404);
        }

        $botman->hears('(.*)', function ($bot) use ($mailbox) {
            // Process included images.
            // https://www.phpclasses.org/blog/package/7700/post/9-Get-Twitter-Direct-Message-Images-in-PHP-with-the-OAuth-API.html
            $data = $bot->getMessage()->getPayload();

            // Text is obtained from the payload to prevent removal of newlines
            // in the message text
            $text = $data['message_create']['message_data']['text'];

            // Only images can be attached.
            $attachment_short_url = $data['message_create']['message_data']['attachment']['media']['url'] ?? '';
            $attachment_full_url = $data['message_create']['message_data']['attachment']['media']['media_url'] ?? '';

            if ($attachment_short_url && $attachment_full_url) {
                // Try to download as attachment.
                $connection = \Twitter::getMailboxOauth($mailbox);

                // https://developer.twitter.com/en/docs/twitter-api/premium/account-activity-api/guides/getting-started-with-webhooks
                $attachment_data = $connection->oAuthRequest($attachment_full_url, 'GET', []);

                if ($attachment_data) {
                    $attachment = Attachment::create(
                        \Helper::remoteFileName($attachment_full_url),
                        \Helper::binaryDataMimeType($attachment_data),
                        null,
                        $attachment_data,
                        null,
                        false,
                        null,
                        null
                    );

                    if ($attachment) {
                        $img_html = '<img src="'.$attachment->url().'" />';

                        $text = str_replace($attachment_short_url, $img_html, $text);
                    }
                }
            }

            \Twitter::processIncomingMessage($bot, $text, $mailbox);
        });

        $botman->listen();
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

        $settings = $mailbox->meta[\Twitter::DRIVER] ?? [];

        return view('twitter::settings', [
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

        $webhooks_enabled = (int)($mailbox->meta[\Twitter::DRIVER]['enabled'] ?? 0);

        $settings['enabled'] = (int)($settings['enabled'] ?? 0);

        // Try to add a webhook.
        if (!$webhooks_enabled && (int)$settings['enabled']) {
            $result = \Twitter::setWebhook($settings['token'] ?? '', $mailbox->id);

            if (!$result['result']) {
                $settings['enabled'] = false;
                \Session::flash('flash_error', __('Error occurred setting up a Twitter webhook').': '.$result['msg']);
            }
        }
        // Remove webhook.
        if ($webhooks_enabled && !(int)$settings['enabled']) {
            $result = \Twitter::setWebhook($settings['token'] ?? '', $mailbox->id, true);

            if (!$result['result']) {
                $settings['enabled'] = true;
                \Session::flash('flash_error', __('Error occurred removing a Twitter webhook').': '.$result['msg']);
            }
        }

        $mailbox->setMetaParam(\Twitter::DRIVER, $settings);
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('mailboxes.twitter.settings', ['mailbox_id' => $mailbox_id]);
    }
}
