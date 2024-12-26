<div class="row-container">
	<form class="form-horizontal cl-link-conv-form" method="POST" action="" data-item-id="{{ $item_id }}">

		<div class="form-group">
	        <label class="col-sm-2 control-label">{{ __('Conversation') }}</label>

	        <div class="col-sm-10">
	            <div class="input-group">
	                <span class="input-group-addon">#</span>
	                <input type="number" class="form-control" name="conversation_number" value="" id="cl-link-conv-number" placeholder="{{ __('Conversation Number') }}" required="required">
	            </div>
	        </div>
	    </div>

		<div class="form-group margin-top margin-bottom-10">
	        <div class="col-sm-10 col-sm-offset-2">
	            <button class="btn btn-primary cl-link-conv-submit" data-loading-text="{{ __('Link') }}…">{{ __('Link') }}</button>
	            <button class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
	        </div>
	    </div>
	</form>

	<hr/>
	<iframe src="{{ route('conversations.search', ['f' => ['mailbox' => $mailbox_id, 'custom' => \Checklists::SEARCH_CUSTOM]]) }}&amp;x_embed=1" frameborder="0" class="modal-iframe"></iframe>
</div>