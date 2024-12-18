/**
 * Module's JavaScript.
 */

function clInit()
{
	$(document).ready(function(){
		clApplyListeners();
		
		$('#cl-add-toggler').click(function(e) {
			$('#cl-add-group').removeClass('hidden');
			$(this).hide();
			$('#cl-add-text').focus();
		});

		$('#cl-add-form').submit(function(e) {
			var button = $('#cl-add-trigger');
			var input = $('#cl-add-text');
			button.button('loading');
			fsAjax(
				{
					action: 'add',
					conversation_id: getGlobalAttr('conversation_id'),
					text: input.val()
				}, 
				laroute.route('checklists.ajax'), 
				function(response) {
					clRefreshItems(response.html);
					$('#cl-add-toggler').show();
					$('#cl-add-group').addClass('hidden');
					button.button('reset');
					input.val('');
				}, true
			);
			return false;
		});
	});
}

function clApplyListeners()
{
	$('.cl-item input[type="checkbox"]').change(function() {
		var checkbox = $(this);
		var item_container = $(this).parents('.cl-item:first');
		//checkbox.attr('disabled', 'disabled');
		fsAjax(
			{
				action: 'change_status',
				item_id: item_container.attr('data-cl-item-id'),
				completed: (this.checked ? 1 : 0)
			}, 
			laroute.route('checklists.ajax'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					//checkbox.removeAttr('disabled');
			        if (checkbox.is(':checked')) {
			        	item_container.addClass('cl-item-completed');
			        } else {
			        	item_container.removeClass('cl-item-completed');
			        }
			    } else {
					showAjaxError(response);
				}
			},
			true,
			function() {
				checkbox.attr('checked', !checkbox.is(':checked'));
				//checkbox.removeAttr('disabled');
				showFloatingAlert('error', Lang.get("messages.ajax_error"));
				ajaxFinish();
			},
		);
    });

	$('.cl-conv-unlink').click(function(e) {
		var button = $(this);
		
		button.button('loading');
		fsAjax(
			{
				action: 'unlink_conversation',
				item_id: button.parents('.cl-item:first').attr('data-cl-item-id')
			}, 
			laroute.route('checklists.ajax'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					clRefreshItems(response.html);
			    } else {
					showAjaxError(response);
				}
				button.button('reset');
			}, true
		);

		return false;
	});

	$('.cl-conv-delete').click(function(e) {
		var button = $(this);
		
		button.button('loading');
		fsAjax(
			{
				action: 'delete',
				item_id: button.parents('.cl-item:first').attr('data-cl-item-id')
			}, 
			laroute.route('checklists.ajax'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					clRefreshItems(response.html);
			    } else {
					showAjaxError(response);
				}
				button.button('reset');
			}, true
		);

		return false;
	});

	$('.cl-item-text').click(function(e) {
		$(this).parents('.cl-item:first').addClass('cl-item-editing');
	});

	$('.cl-edit-cancel').click(function(e) {
		$(this).parents('.cl-item:first').removeClass('cl-item-editing');
	});

	$('.cl-edit-save').click(function(e) {
		var button = $(this);
		
		button.attr('disabled', 'disabled');
		fsAjax(
			{
				action: 'save',
				text: button.parents('.cl-item:first').children().find('.cl-edit-text:first').val(),
				item_id: button.parents('.cl-item:first').attr('data-cl-item-id')
			}, 
			laroute.route('checklists.ajax'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					clRefreshItems(response.html);
			    } else {
					showAjaxError(response);
				}
				button.removeAttr('disabled');
			}, true
		);

		return false;
	});
}

function clInitLinkConvModal()
{
	$(document).ready(function(){

		$('#cl-link-conv-number:visible:first').focus();

		$('.cl-link-conv-form:visible:first').on('submit', function(e) {

			var item_id = $(this).attr('data-item-id');

			var data = $(this).serialize();

			data += '&action=link_conversation';
			data += '&item_id='+item_id;

			var button = $(this).children().find('.cl-link-conv-submit:first');
	    	button.button('loading');

			fsAjax(data, 
				laroute.route('checklists.ajax'), 
				function(response) {
					if (isAjaxSuccess(response)) {
						clRefreshItems(response.html);
						$('.modal:visible:first').modal('hide');
					} else {
						showAjaxError(response);
						button.button('reset');
					}
				}, true
			);

			e.preventDefault();

			return false;
		});

		window.addEventListener("message", function(event) {
			
			if (typeof(event.data) != "undefined") {
				var number = event.data.replace('kn.pick_conversation:', '');

				if (number) {
					$('#cl-link-conv-number').val(number);
					$('.modal:visible:first').animate({scrollTop: 0}, 600, 'swing');
				}
			}
		});
	});
}

function clInitPickConv()
{
	$(document).ready(function(){
		$('.kn-btn-pick').click(function(e) {
			clPickConv($(this).attr('data-conv-number'), $(this)[0]);
			e.preventDefault();
			e.stopPropagation();
		});
	});
}

function clPickConv(number, btn)
{
	if (typeof(window.parent) != "undefined") {
		// It should be the same message as in Kanban as sometimes Kanban click event works first
		window.parent.postMessage('kn.pick_conversation:'+number);
		$('.kn-btn-pick.btn-primary').removeClass('btn-primary').addClass('btn-default');
		$(btn).addClass('btn-primary');
	}
}

function clRefreshItems(html)
{
	$('#cl-conv-checklist').html(html);
	clApplyListeners();
	initModals();
	/*fsAjax(
		{
			action: 'items',
			conversation_id: getGlobalAttr('conversation_id')
		}, 
		laroute.route('checklists.ajax'), 
		function(response) {
			$('#cl-conv-checklist').html(response.html);
			clApplyListeners();
		}, true
	);*/
}