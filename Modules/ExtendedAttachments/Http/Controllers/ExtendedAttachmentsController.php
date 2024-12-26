<?php

namespace Modules\ExtendedAttachments\Http\Controllers;

use App\Thread;
use App\Attachment;
use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ExtendedAttachmentsController extends Controller
{
    public function downloadThreadAttachments($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);

        if (!$thread->has_attachments) {
            abort(404);
        }

        $archive_path = 'extendedattachments'.DIRECTORY_SEPARATOR.'attachments_'.$thread_id.'.zip';

        $storage = \Helper::getPrivateStorage();

        // Check if archive already exists.
        if (!$storage->exists($archive_path)) {
            // Copy attachments to the temporary folder.
            $storage->makeDirectory('extendedattachments');
            $storage->makeDirectory('extendedattachments'.DIRECTORY_SEPARATOR.$thread_id);
            foreach ($thread->attachments as $attachment) {

                $attachment_path = 'extendedattachments'.DIRECTORY_SEPARATOR.$thread_id.DIRECTORY_SEPARATOR.$attachment->file_name;
                if ($storage->exists($attachment_path)) {
                    $i = 2;
                    do {
                        $attachment_path = 'extendedattachments'.DIRECTORY_SEPARATOR.$thread_id.DIRECTORY_SEPARATOR.$i.'_'.$attachment->file_name;
                        $i++;
                    } while ($storage->exists($attachment_path));
                }
                $storage->copy($attachment->getStorageFilePath(), $attachment_path);
            }

            \Helper::createZipArchive(storage_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'extendedattachments'.DIRECTORY_SEPARATOR.$thread_id.DIRECTORY_SEPARATOR.'*', 'attachments_'.$thread_id.'.zip', '', $archive_path);

            $storage->deleteDirectory('extendedattachments'.DIRECTORY_SEPARATOR.$thread_id);
        }
        
        // Send archive.
        return $storage->download($archive_path);
    }

    /**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Delete board.
            case 'delete_attachment':
                $attachment = Attachment::find($request->attachment_id);
                if (!$attachment) {
                    $response['msg'] = __('Attachment not found');
                }

                if (!$response['msg'] && $attachment->thread_id 
                    && $attachment->thread && $attachment->thread->conversation
                    && $attachment->thread->conversation->mailbox
                ) {
                    if (!$attachment->thread->conversation->mailbox->userHasAccess($user)) {
                        $response['msg'] = __('Not enough permissions');
                    }
                }

                if (!$response['msg']) {                    
                    Attachment::deleteAttachments([$attachment]);
                }

                $response['status'] = 'success';
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occurred';
        }

        return \Response::json($response);
    }

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            
            case 'delete_attachment':
                $attachment = Attachment::find($request->attachment_id);
                return view('extendedattachments::ajax_html/delete_attachment', [
                    'attachment' => $attachment,
                ]);
                break;
        }

        abort(404);
    }


    public function emlViewer(Request $request)
    {
        $attachment_url = $request->attachment_url;

        if (!$attachment_url) {
            $this->emlViewerShowError();
        }

        parse_str(parse_url($attachment_url, PHP_URL_QUERY), $params);

        $attachment = Attachment::find($params['id'] ?? '');
        if (!$attachment) {
            $this->emlViewerShowError();
        }

        if ($attachment->getToken() != ($params['token'] ?? '')) {
            $this->emlViewerShowError();
        }

        $mailbox = Mailbox::find($request->mailbox_id ?? '');
        if (!$mailbox) {
            $this->emlViewerShowError();
        }

        if ($attachment->mime_type != 'message/rfc822' 
            && strtoupper($attachment->file_name) != 'RFC822' 
            && pathinfo(strtolower($attachment->file_name), PATHINFO_EXTENSION) != 'eml'
        ) {
            $this->emlViewerShowError();
        }

        // Parse EML file.
        $email = $attachment->getFileContents();

        try {
            $message = \MailHelper::parseEml($email, $mailbox);
        } catch (\Exception $e) {
            \Helper::logException($e, '[Extended Attachments] ');
            $this->emlViewerShowError();
        }

        $attachments = $message->getAttachments();
        $attachments_to_show = [];
        $body = $message->getHTMLBody();
        if (!$body) {
            $body = htmlspecialchars($message->getTextBody());
        } else {
            $body = \Helper::stripDangerousTags($body);
        }
        // Replace CIDs
        if (count($attachments)) {
            foreach ($attachments as $attachment) {
                if ($attachment->id && $attachment->content && strstr($body, 'cid:'.$attachment->id)) {
                    $body = str_replace('cid:'.$attachment->id, 'data:'.$attachment->content_type.';base64,'.base64_encode($attachment->content), $body);
                } else {
                    $attachments_to_show[] = $attachment;
                }
            }
        }

        // Download attachment contained in the EML file.
        if (!empty($request->attachment_id)) {
            foreach ($attachments_to_show as $attachment) {
                if ($attachment->id == $request->attachment_id) {
                    $response = response($attachment->content)
                       ->header('Content-Type' , $attachment->content_type);
                       
                    //if (!$view_attachment) {
                    $response->header('Content-Disposition', 'attachment; filename="'.$attachment->filename.'"');
                    //}
                    return $response;
                }
            }
            $this->emlViewerShowError();
        }

        // Show EML.

        $from = $message->getFrom()[0] ?? null;
        if ($from) {
            $from = $from->toArray();
            echo '<strong>'.__('From').':</strong> '.htmlspecialchars($from['personal'] ?? $from['full'] ?? '').' &lt;'.htmlspecialchars($from['mail'] ?? '').'&gt;<br/>';
        }

        $to_list = $message->getTo() ?? null;
        if ($to_list) {
            $to_array = [];
            $to_list = $to_list->get();
            foreach ($to_list as $to) {
                $to_array[] = htmlspecialchars($to->toArray()['mail'] ?? '');
            }
            
            echo '<strong>'.__('To').':</strong> '.(implode(', ', $to_array)).'<br/>';
        }

        $cc_list = $message->getCc() ?? null;
        if ($cc_list) {
            $cc_array = [];
            $cc_list = $cc_list->get();
            foreach ($cc_list as $cc) {
                $cc_array[] = htmlspecialchars($cc->toArray()['mail'] ?? '');
            }
            
            echo '<strong>'.__('Cc').':</strong> '.(implode(', ', $cc_array)).'<br/>';
        }

        $bcc_list = $message->getBcc() ?? null;
        if ($bcc_list) {
            $bcc_array = [];
            $bcc_list = $bcc_list->get();
            foreach ($bcc_list as $bcc) {
                $bcc_array[] = htmlspecialchars($bcc->toArray()['mail'] ?? '');
            }
            
            echo '<strong>'.__('Bcc').':</strong> '.(implode(', ', $bcc_array)).'<br/>';
        }

        echo '<strong>'.__('Subject').':</strong> '.$message->getSubject().'<br/><br/>';

        if (count($attachments_to_show)) {
            echo '<strong>'.__('Attachments').':</strong> ';
            foreach ($attachments_to_show as $i => $attachment) {
                if ($i != 0) {
                    echo ', ';
                }
                echo '<a href="'.route('extendedattachments.eml_viewer', ['attachment_url'=>$attachment_url, 'mailbox_id'=>$request->mailbox_id, 'attachment_id'=>$attachment->id]).'" target="_blank">'.htmlspecialchars($attachment->getName()).'</a>';
            }
            echo '<br/><br/>';
        }


        echo $body;
        exit();
    }

    public function emlViewerShowError()
    {
        echo __("<center>Couldn't preview file</center>");
        exit();
    }
}
