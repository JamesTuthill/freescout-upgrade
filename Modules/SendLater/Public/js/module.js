/**
 * Module's JavaScript.
 */

function slInitScheduleModal(jmodal)
{
	$(document).ready(function(){

		$('.sl-schedule-datetime:visible').val($('input[name="sl_schedule_date"]:first').val());

		// Dates
		var datepicker = $('.sl-schedule-datetime:visible').flatpickr({
			enableTime: true,
			minDate: "today",
			static: true,
			onChange: function(selectedDates, dateStr, instance) {
		        $('.sl-schedule-btn:visible:first').removeAttr('disabled');
		    }
		});

		$('.sl-datetime-trigger:visible').click(function(e) {
			datepicker.toggle();
		});

		// Schedule
		$('.sl-schedule-btn:visible:first').click(function(e) {
			var schedule_date = $('.sl-schedule-datetime:visible').val();
			if (schedule_date) {
				$('input[name="sl_schedule_date"]:first').val(schedule_date+':00');
			}

			//e.preventDefault();
			$('.btn-reply-submit:visible:first').click();
			jmodal.modal('hide');
		});

		$('.sl-schedule-cancel:visible:first').click(function(e) {
			$('input[name="sl_schedule_date"]:first').val('');
			jmodal.modal('hide');
		});

		$('.sl-schedule-apply:visible:first').click(function(e) {
			var button = $(e.target);
			if (button.hasClass('glyphicon')) {
				button = button.parent();
			}
			button.button('loading');

			fsAjax({
					'action': 'calc',
					'number': $('.sl-schedule-number:visible:first').val(),
					'period': $('.sl-schedule-period:visible:first').val()
				}, 
				laroute.route('sendlater.ajax'),
				function(response) {
					button.button('reset');
					if (isAjaxSuccess(response)) {
						$('.sl-schedule-datetime:visible').val(response.datetime);
						$('.sl-schedule-btn:visible:first').removeAttr('disabled');
						// Causes JS error
						//datepicker.setDate(response.datetime);
					} else {
						showAjaxError(response);
					}
					loaderHide();
				}
			);
		});
	});
}

function slSendNow(thread_id)
{
	var button = $('#thread-'+thread_id+' .sl-send-now:first').button('loading');

	fsAjax({
			'action': 'send_now',
			'thread_id': thread_id
		}, 
		laroute.route('sendlater.ajax'), 
		function(response) {
			if (isAjaxSuccess(response)) {
				window.location.href = '';
			} else {
				button.button('reset');
				showAjaxError(response);
			}
			loaderHide();
		},
		false,
		function(response) {
			showFloatingAlert('error', Lang.get("messages.ajax_error"));
			ajaxFinish();
			button.button('reset');
		}
	);

	return false;
}

function slCancelSending(thread_id)
{
	var button = $('#thread-'+thread_id+' .sl-cancel-sending:first').button('loading');

	fsAjax({
			'action': 'cancel',
			'thread_id': thread_id
		}, 
		laroute.route('sendlater.ajax'), 
		function(response) {
			if (isAjaxSuccess(response)) {
				window.location.href = '';
			} else {
				button.button('reset');
				showAjaxError(response);
			}
			loaderHide();
		},
		false,
		function(response) {
			showFloatingAlert('error', Lang.get("messages.ajax_error"));
			ajaxFinish();
			button.button('reset');
		},
	);

	return false;
}

function slInitNewConv()
{
	$(document).ready(function(){
		slSetModalUrl();
	});
}

function slSetModalUrl()
{
	fs_reply_changed = true;
	saveDraft(false, true);

	if (getGlobalAttr('conversation_id')) {
		if (!$('#sl-modal-trigger').attr('href')) {
			
			var href = laroute.route('sendlater.ajax_html', {
				'action': 'schedule', 'conversation_id': getGlobalAttr('conversation_id')
			});
			$('#sl-modal-trigger').attr('href', href);
		}
	} else {
		setTimeout(slSetModalUrl, 300);
	}
}

function slInitSendButtons()
{
	$(document).ready(function(){
		$('.sl-send-now').click(function(e) {
			slSendNow($(this).parents('.thread:first').attr('data-thread_id'));
			e.preventDefault();
		});
		$('.sl-cancel-sending').click(function(e) {
			slCancelSending($(this).parents('.thread:first').attr('data-thread_id'));
			e.preventDefault();
		});
	});
}