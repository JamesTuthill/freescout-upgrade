<div class="conv-top-block clearfix">
    <div id="cl-conv-checklist">
        @include('checklists::partials/items')
    </div>
    <button type="button" class="btn btn-sm btn-link padding-0" id="cl-add-toggler">{{ __('Add Task') }}…</button>
    <form id="cl-add-form">
        <div class="input-group input-sized-lg hidden" id="cl-add-group">
            <input type="text" class="form-control" id="cl-add-text" value="" placeholder="{{ __('Task description') }}…" required="required">
            <span class="input-group-btn">
                <button id="cl-add-trigger" class="btn btn-default" type="submit" data-loading-text="Add…">{{ __('Add') }}</button>
            </span>
        </div>
    </form>
    @if (count($linked_conversations))
        <div class="alert alert-info alert-narrow margin-top-10 margin-bottom-5">
            {{ __('This conversation is linked to the following conversation(s):') }}
            @foreach ($linked_conversations as $i => $linked_conversation)
                <a href="{{ $linked_conversation->url() }}" target="_blank">#{{ $linked_conversation->number }}</a>@if (!$loop->last), @endif
            @endforeach
        </div>
    @endif
</div>
