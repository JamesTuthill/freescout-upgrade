@foreach ($items as $item)
    <div class="cl-item @if ($item->isCompleted()) cl-item-completed @endif" data-cl-item-id="{{ $item->id }}">
        <div class="checkbox margin-top-0">
            <input type="checkbox" @if ($item->isCompleted()) checked="checked" @endif>
            <span class="cl-item-wrapper"><span class="cl-item-text">{{ __($item->text) }}</span>@if ($item->isLinked()) <small>(<a href="{{ $item->conversationUrl() }}" target="_blank">#{{ $item->linked_conversation_number }}</a>)</small>@endif</span>
            <span class="cl-item-editor">
                <form class="form-horizontal">
                    <div class="input-group" id="cl-edit-group">
                        <input type="text" class="form-control cl-edit-text" value="{{ __($item->text) }}" required="required">
                        <span class="input-group-btn">
                            <button class="btn btn-default cl-edit-save" type="submit"><i class="glyphicon glyphicon-ok"></i></button>
                            <button type="button" class="btn btn-default cl-edit-cancel">Cancel</button>
                        </span>
                    </div>
                </form>
            </span>
            <span class="cl-item-actions" data-toggle="dropdown"><i class="glyphicon glyphicon-option-vertical"></i></span>
            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                @if (!$item->isLinked())
                    <li role="presentation"><a href="{{ route('checklists.ajax_html', ['action' => 'link_conversation', 'item_id' => $item->id]) }}" role="menuitem" data-trigger="modal" data-modal-no-footer="true" data-modal-title="{{ __('Link To Conversation') }}" data-modal-size="lg" data-modal-on-show="clInitLinkConvModal">{{ __('Link To Conversation') }}</a></li>
                @else
                    <li role="presentation"><a href="" role="menuitem" class="cl-conv-unlink" data-loading-text="{{ __('Unlink From Conversation') }}…">{{ __('Unlink From Conversation') }}</a></li>
                @endif
                <li role="presentation"><a href="" role="menuitem" class="cl-conv-delete" data-loading-text="{{ __('Delete') }}…">{{ __('Delete') }}</a></li>
            </ul>
        </div>
    </div>
@endforeach