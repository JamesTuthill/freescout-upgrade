<?php

namespace Modules\EmbedImages\Listeners;

use App\Attachment;
use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use Swift_Message;
use Swift_EmbeddedFile;

class SwiftEmbedImages implements Swift_Events_SendListener
{
    /**
     * @var  Swift_Message
     */
    private $message;

    /**
     * @param  array  $config
     */
    // public function __construct($config)
    // {
    //     $this->config = $config;
    // }

    /**
     * @param  Swift_Events_SendEvent  $evt
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        $this->message = $evt->getMessage();

        $this->attachImages();
    }

    /**
     * @param  Swift_Events_SendEvent  $evt
     * @return bool
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        return true;
    }

    /**
     *
     */
    private function attachImages()
    {
        // Get body
        $body = $this->message->getBody();

        $app_host = parse_url(config('app.url'), PHP_URL_HOST);
        
        $body = preg_replace_callback(
            '/(<img\b[^>]*src=\")([^>"]+)(\"[^>]*>)/i',
            function ($m) use ($app_host) {
                
                $src = $m[2];

                if (preg_match("#^data:image/([a-zA-Z]+);base64,(.*)#", $src, $data_m)) {
                    // src="data:image/png;base64,iVBORw0KGgoAAAANSU.
                    if (!empty($data_m[1]) && !empty($data_m[2])) {
                        $image_extension = $data_m[1];
                        $image_data = base64_decode(html_entity_decode($data_m[2]));

                        if ($image_extension && $image_data) {
                            $cid = $this->message->embed(
                                new Swift_EmbeddedFile(
                                    $image_data,
                                    \Str::random(10).'.'.$image_extension,
                                    'image/'.$image_extension
                                )
                            );
                            if ($cid) {
                                $src = $cid;
                            }
                        }
                    }
                } else {
                    $url_parts = parse_url(html_entity_decode($m[2]));

                    if (empty($url_parts['host']) || empty($url_parts['path'])) {
                        return $m[1].$m[2].$m[3];
                    }

                    if ($url_parts['host'] == $app_host) {

                        if (strstr($url_parts['path'], '/'.Attachment::DIRECTORY.'/') && !empty($url_parts['query'])) {
                        
                            parse_str($url_parts['query'], $query_params);
                            
                            if (!empty($query_params['id']) && !empty($query_params['token'])) {
                                $attachment = Attachment::find($query_params['id']);
                                if ($attachment) {
                                    $attachment_contents = $attachment->getFileContents();
                                    if ($attachment_contents && preg_match("#^image/#", $attachment->mime_type)) {
                                        //$src = 'data:'.$attachment->mime_type.';base64,'.base64_encode($attachment_contents);
                                        $cid = $this->message->embed(
                                            new Swift_EmbeddedFile(
                                                $attachment_contents,
                                                $attachment->file_name,
                                                $attachment->mime_type
                                            )
                                        );
                                        if ($cid) {
                                            $src = $cid;
                                        }
                                    }
                                }
                            }
                        } elseif (strstr($url_parts['path'], '/uploads/')) {
                            // Images from /uploads/ folder.
                            $file_name = basename($url_parts['path']);

                            if (html_entity_decode($src) == \Helper::uploadedFileUrl($file_name)) {
                                $file_path = 'uploads/'.$file_name;
                                
                                $storage = \Helper::getPublicStorage();
                                $mime_type = $storage->mimeType($file_path);
                                $image_content = $storage->get($file_path);

                                if ($image_content && preg_match("#^image/#", $mime_type)) {
                                    $cid = $this->message->embed(
                                        new Swift_EmbeddedFile(
                                            $image_content,
                                            $file_name,
                                            $mime_type
                                        )
                                    );
                                    if ($cid) {
                                        $src = $cid;
                                    }
                                }
                            }
                        }
                    }
                }

                return $m[1].$src.$m[3];
            },
            $body
        );

        // Replace body
        $this->message->setBody($body);
    }
}
