/**
 * Module's JavaScript.
 */

function snoozeInitModal(jmodal)
{
	$(document).ready(function(){

		// Dates
		var datepicker = $('.snooze-datetime:visible').flatpickr({
			enableTime: true,
			minDate: "today",
			static: true,
			onChange: function(selectedDates, dateStr, instance) {
		        $('.snooze-btn:visible:first').removeAttr('disabled');
		    }
		});

		$('.snooze-datetime-trigger:visible').click(function(e) {
			datepicker.toggle();
		});

		$('.snooze-btn:visible:first').click(function(e) {
			var snooze_date = $('.snooze-datetime:visible').val();
			if (!snooze_date) {
				return;
			}
			var button = $(this);
			button.button('loading');
			fsAjax({
					'action': 'snooze',
					'conversation_id': getGlobalAttr('conversation_id'),
					'snooze_date': snooze_date
				}, 
				laroute.route('snooze.ajax'),
				function(response) {
					if (isAjaxSuccess(response)) {
						if (convGetStatus() != FS_STATUS_CLOSED) {
							$('.conv-status:first a[data-status="'+FS_STATUS_CLOSED+'"]').click();
						} else {
							reloadPage();
						}
					} else {
						showAjaxError(response);
						button.button('reset');
					}
					loaderHide();
				}, true
			);

			e.preventDefault();
			//jmodal.modal('hide');
		});

		$('.snooze-cancel:visible:first').click(function(e) {
			jmodal.modal('hide');
		});

		$('.snooze-apply:visible:first').click(function(e) {
			var button = $(e.target);
			if (button.hasClass('glyphicon')) {
				button = button.parent();
			}
			button.button('loading');

			fsAjax({
					'action': 'calc',
					'number': $('.snooze-number:visible:first').val(),
					'period': $('.snooze-period:visible:first').val()
				}, 
				laroute.route('snooze.ajax'),
				function(response) {
					button.button('reset');
					if (isAjaxSuccess(response)) {
						$('.snooze-datetime:visible').val(response.datetime);
						$('.snooze-btn:visible:first').removeAttr('disabled');
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

function snoozeSnooze(thread_id)
{
	var button = $('#thread-'+thread_id+' .snooze-send-now:first').button('loading');

	fsAjax({
			'action': 'send_now',
			'thread_id': thread_id
		}, 
		laroute.route('snooze.ajax'), 
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

function snoozeUnsnooze()
{
	var button = $('.snooze-unsnooze:first').button('loading');

	//$('.conv-status:first a[data-status="1"]').click();

	fsAjax({
			'action': 'unsnooze',
			'conversation_id': getGlobalAttr('conversation_id')
		}, 
		laroute.route('snooze.ajax'), 
		function(response) {
			if (isAjaxSuccess(response)) {
				window.location.href = '';
			} else {
				button.button('reset');
				showAjaxError(response);
			}
			loaderHide();
		}
	);

	return false;
}

function snoozeInit()
{
	$(document).ready(function(){
		$('a.snooze-unsnooze').click(function(e) {
			snoozeUnsnooze();
			e.preventDefault();
		});
	});
}
