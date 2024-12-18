<div class="conv-sidebar-block attachments-block" data-auth_user_name="{{ Auth::user()->getFullName() }}">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href=".collapse-attachments">{{ __("Attachments") }} 
                        <b class="caret"></b>
                    </a>
                </h4>
            </div>
            <div class="collapse-attachments panel-collapse collapse in">
                <div class="panel-body">
                    <div class="sidebar-block-header2"><strong>{{ __("Attachments") }}</strong> (<a data-toggle="collapse" href=".collapse-attachments">{{ __('close') }}</a>)</div>
                    <ul class="sidebar-block-list attachments-list">
                        @foreach ($attachments as $attachment)
                            <li data-attachment-id="{{ $attachment->id }}" data-mime="{{ $attachment->mime_type }}">
                                <a href="{{ $attachment->url() }}" class="attachment-link help-link" target="_blank"><i class="glyphicon glyphicon-paperclip"></i> {{ $attachment->file_name }} ({{ $attachment->getSizeName() }})</a>
                                @action('extended_attachments.sidebar_item_append', $attachment, $thread, $conversation, $mailbox)
                            </li>
                        @endforeach
						@action('extended_attachments.sidebar_list_append', $thread, $conversation, $mailbox)
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>