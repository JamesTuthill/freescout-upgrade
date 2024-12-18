/**
 * Module's JavaScript.
 */

var WB_FILTERS_SEPARATOR = '|';
var WB_REFRESH_INTERVAL = 60; // seconds

function wbInit(text_delete)
{
	$(document).ready(function() {

		$('#wb-wallboards-list').change(function(e) {
			window.location.href = $(this).val();
		});

		wbApplyListeners();
		wbShowParams();

		// On chaning params
		$('.wb-param li a').click(function(e) {
			var li = $(this).parent();

			var param = li.parents('.wb-param').attr('data-param');
			if (param != 'filters') {
				li.parent().children('li').removeClass('active');
				li.addClass('active');
			}

			wbShow();

			e.preventDefault();
		});

		$('#wb-delete-wallboard').click(function(e) {
			showModalConfirm('<span class="text-danger"><i class="glyphicon glyphicon-exclamation-sign"></i> '+text_delete+'</span>', 'wb-confirm-delete-wallboard', {
					on_show: function(modal) {
						modal.children().find('.wb-confirm-delete-wallboard:first').click(function(e) {
							var button = $(this);
					    	button.attr('data-loading-text', Lang.get("messages.delete")+'…');
					    	button.button('loading');
					    
							fsAjax({
									action: 'delete_wallboard',
									wallboard_id: getGlobalAttr('wallboard_id')
								}, 
								laroute.route('wallboards.ajax'), 
								function(response) {
									button.button('reset');
									if (isAjaxSuccess(response)) {
										window.location.href = '';
										modal.modal('hide');
									} else {
										showAjaxError(response);
									}
								}, true
							);
						});
					}
			}, Lang.get("messages.delete"));
			e.preventDefault();
		});

		$('#wb-btn-refresh').click(function(e) {
			wbShow();
			e.preventDefault();
		});

		$('.wb-filter-date').flatpickr();

		$('#wb-toolbar .wb-filter-date').change(function(e) {
			$('#wb-date-period').val('custom');
			wbShow();
		});

		$('#wb-date-period').change(function(e) {
			var option = $(this).children('option:selected');
			var date_from = option.attr('data-date-from');
			var date_to = option.attr('data-date-to');

			if (option.val() == 'all') {
				$('.wb-filter-date').attr('disabled', 'disabled').attr('data-force-disable', '1');
				wbShow();
			} else {
				if (option.val() != 'custom') {
					$('#wb-toolbar .wb-filter-date[name="from"]').val(date_from);
					if (date_to) {
						$('#wb-toolbar .wb-filter-date[name="to"]').val(date_to);
					}
					wbShow();
				}

				$('.wb-filter-date').removeAttr('disabled').removeAttr('data-force-disable');
			}
		});

		// Automatically refresh wallboard every N sec
		setInterval(function() {
			wbShow(true);
		}, WB_REFRESH_INTERVAL*1000);

		$('#wb-wallboard')[0].addEventListener('sortupdate', function(e) {
		    // $(e.detail.item) contains the current dragged element.
		    var widget_ids = [];

		    $(e.target).children('.wb-widget').each(function(index, el){
			    widget_ids.push($(this).attr('data-widget-id'));
			});
			fsAjax({
					action: 'move_widget',
					wallboard_id: getGlobalAttr('wallboard_id'),
					widgets: widget_ids
				}, 
				laroute.route('wallboards.ajax'), 
				function(response) {
					showAjaxResult(response);
				}, true
			);
		});

		/*$('#wb-copy-link').click(function(e) {
			var url = window.location.origin+window.location.pathname+'?';
			var params = knGetParams();
			var kn = [];
			for (var param_name in params) {
				var value = params[param_name];
				if (param_name == 'filters') {
					for (var filter_name in value) {
						var filter_values = value[filter_name].join(KN_FILTERS_SEPARATOR);
						if (!filter_values.length) {
							continue;
						}
						kn.push('kn%5B'+param_name+'%5D%5B'+filter_name+'%5D='+encodeURIComponent(filter_values));
					}
				} else {
					kn.push('kn%5B'+param_name+'%5D='+encodeURIComponent(value));
				}
			}
			url += kn.join('&');
			copyToClipboard(url);
			e.preventDefault();
		});

		$('#wb-reset-filter').click(function(e) {
			window.location.href = $(this).attr('href');
			e.preventDefault();
		});*/
	});
}

function wbApplyListeners()
{
	$('.wb-more').off('click').click(function(e) {
		knLoadRows($(this), false);
	});

	initModals('div');

	$('.wb-widget-footer a').off('click').click(function(e) {
		e.stopPropagation();
	});

	// Sorting widgets
	sortable('#wb-wallboard', {
	    handle: '.wb-widget-header',
	    //connectWith: 'wb-sortable',
	    items: '.wb-widget',
	    forcePlaceholderSize: true 
	});
}

function wbGetParams()
{
	var params = {};

	/*$('.wb-param').each(function(i, el) {
		var j_el = $(el);
		var param = j_el.attr('data-param');
		if (param == 'filters') {

		} else {
			params[param] = j_el.children().find('li.active:first').attr('data-param-value');
		}
	});*/

	params.wallboard_id = getGlobalAttr('wallboard_id');

	params.filters = {};
	/*$('.wb-param[data-param="filters"] li').each(function(i, el) {
		var li = $(el);
		var param_name = li.attr('data-param-name');
		var param_value = li.attr('data-param-value');
		params.filters[param_name] = [];
		if (param_value) {
			params.filters[param_name] = param_value.split(WB_FILTERS_SEPARATOR);
		}

		if (params.filters[param_name].length == 1
			&& typeof(params.filters[param_name][0]) != "undefined"
			&& params.filters[param_name][0] == ''
		) {
			params.filters[param_name] = [];
		}
	});*/

	// Date filter
	params.filters.date = {};
	params.filters.date.period = $('#wb-date-period').val();
	params.filters.date.from = $('#wb-toolbar .wb-filter-date[name="from"]').val();
	params.filters.date.to = $('#wb-toolbar .wb-filter-date[name="to"]').val();

	return params;
}

function wbShowParams()
{
	$('.wb-param-counter').each(function(i, el) {
		var j_el = $(el);
		var count = j_el.parents('.wb-param:first').children('ul:first').children('li.active').length;
		if (!count) {
			j_el.text('');
		} else {
			j_el.text(' ('+count+')');
		}
	});
}

function wbShow(no_toolbar_disable)
{
	var btn_refresh = $('#wb-btn-refresh i:first');
	if (btn_refresh.hasClass('glyphicon-spin')) {
		return;
	}
	btn_refresh.addClass('glyphicon-spin');

	if (typeof(no_toolbar_disable) == "undefined") {
		toolbar = $('#wb-toolbar :input[data-force-disable!="1"]');
	} else {
		toolbar = $('#does_not_exist');
	}

	toolbar.attr('disabled', 'disabled');
	fsAjax({
			action: 'show',
			wb: wbGetParams()
		}, 
		laroute.route('wallboards.ajax'), 
		function(response) {
			
			if (isAjaxSuccess(response) && response.html) {
				$('#wb-wallboard').html(response.html);
				wbApplyListeners();
				initModals();
				initTooltips();
			} else {
				showAjaxResult(response);
			}

			loaderHide();
			btn_refresh.removeClass('glyphicon-spin');
			toolbar.removeAttr('disabled');
		}, false, function(response) {
			showAjaxResult(response);
			loaderHide();
			btn_refresh.removeClass('glyphicon-spin');
			toolbar.removeAttr('disabled');
		}
	);
}

// Create/update wallboard
function wbInitWallboardModal()
{
	$(document).ready(function(){

		$('.wb-wallboard-name:visible:first').focus();

		$('.wb-wallboard-form:visible:first').on('submit', function(e) {
			
			var wallboard_id = $(this).attr('data-wallboard-id');

			var data = $(this).serialize();

			if (wallboard_id) {
				data += '&wallboard_id='+wallboard_id;
				data += '&action=update_wallboard';
			} else {
				data += '&action=new_wallboard';
			}
			
			var button = $(this).children().find('button:first');
	    	button.button('loading');

			fsAjax(data, 
				laroute.route('wallboards.ajax'), 
				function(response) {
					if (isAjaxSuccess(response)) {
						if (wallboard_id) {
							// Update
							window.location.href = '';
						} else {
							// Create
							window.location.href = response.wallboard_url;
						}
					} else {
						showAjaxError(response);
						button.button('reset');
					}
					loaderHide();
				}
			);

			e.preventDefault();

			return false;
		});
	});
}

// Create/update widget
function wbInitWidgetModal()
{
	$(document).ready(function(){

		// Readonly mode
		if (!$('.wb-widget-form:visible:first .wb-widget-submit:first').length) {
			$('.wb-widget-form:visible:first :input').attr('disabled', 'disabled');
		}

		$('.wb-widget-form-title:visible:first').focus();

		$('.wb-widget-form:visible:first').on('submit', function(e) {

			var widget_id = $(this).attr('data-widget-id');

			var data = $(this).serialize();

			if (widget_id) {
				//data += '&widget_id='+widget_id;
				data += '&action=update_widget';
			} else {
				data += '&action=new_widget';
			}
			var button = $(this).children().find('.wb-widget-submit:first');
	    	button.button('loading');

			fsAjax(data, 
				laroute.route('wallboards.ajax'), 
				function(response) {
					if (isAjaxSuccess(response)) {
						if (response.msg_success) {        
							showFloatingAlert("success", response.msg_success);
						}
						wbShow();
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

		$('#wb-delete-widget').click(function(e) {
			var button = $(this);
			showModalConfirm(wb_text_delete_card, 'wb-confirm-delete-widget', {
				on_show: function(modal) {
					modal.children().find('.wb-confirm-delete-widget:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						var widget_id = button.parents('.wb-widget-form:first').attr('data-widget-id');
						fsAjax(
							{
								action: 'delete_widget',
								wallboard_id: getGlobalAttr('wallboard_id'),
								widget_id: widget_id
							},
							laroute.route('wallboards.ajax'),
							function(response) {
								if (isAjaxSuccess(response)) {
									if (response.msg_success) {
										showFloatingAlert("success", response.msg_success);
									}
									$('.modal:visible').modal('hide');
									wbShow();
								} else {
									showAjaxError(response);
									button.button('reset');
								}
							}, true
						);
					});
				}
			}, Lang.get("messages.delete"));

			e.preventDefault();
		});

		$('.wb-filter-mailbox:visible:first').change(function(e) {
			var mailbox_id = $(this).val();

			if (mailbox_id) {
				$('.modal-body .wb-mailbox-filter').hide();
				$('.modal-body .wb-mailbox-filter[data-mailbox-id="'+mailbox_id+'"]').show();
			} else {
				$('.modal-body .wb-mailbox-filter').show();
			}
			$('.modal-body .wb-mailbox-filter:hidden :input').val('');
		}).change();

		$('.wb-type-date').flatpickr();

		/*$('.modal button.close').click(function(e) {
			wbShow();
		});*/

		initTooltips();
	});
}

function wbInitFilterModal()
{
	$(document).ready(function(){

		$('.wb-type-date:visible').flatpickr({allowInput: true});

		$('.wb-filter-form:visible:first').on('submit', function(e) {
			
			var fields = $(this).serializeArray();
			
			var filter = $(this).children(':input[name="filter"]').val();
			var selected = [];
			var cf_data = {};
			for (var i in fields) {
				// if (fields[i].name == 'filter') {
				// 	filter = fields[i].value;
				// }
				if (filter == 'custom_field') {
					matches = fields[i].name.match(/selected\[op\]\[(\d+)\]/);
					if (matches && typeof(matches[1]) != "undefined" && matches[1]) {
						if (typeof(cf_data[matches[1]]) == "undefined") {
							cf_data[matches[1]] = {};
						}
						cf_data[matches[1]]['op'] = fields[i].value;
					}

					matches = fields[i].name.match(/selected\[value\]\[(\d+)\]/);
					if (matches && typeof(matches[1]) != "undefined" && matches[1]) {
						if (typeof(cf_data[matches[1]]) == "undefined") {
							cf_data[matches[1]] = {};
						}
						cf_data[matches[1]]['value'] = fields[i].value;
					}
				} else {
					if (fields[i].name == 'selected[]') {
						selected.push(fields[i].value);
					}
				}
			}

			if (filter == 'custom_field') {
				for (var cf_id in cf_data) {
					if (cf_data[cf_id].value != '') {
						selected.push(value = JSON.stringify({
							id: cf_id,
							value: cf_data[cf_id].value,
							op: cf_data[cf_id].op
						}));
					}
				}
			}

			// Set filters.
			var li = $('.wb-param[data-param="filters"] li[data-param-name="'+filter+'"]');

			li.attr('data-param-value', selected.join(KN_FILTERS_SEPARATOR));
			var a = li.children('a:first');

			if (selected.length) {
				li.addClass('active');
				a.children('.wb-filter-counter:first').text('('+selected.length+')');
			} else {
				li.removeClass('active');
				a.children('.wb-filter-counter:first').text('');
			}
			// Update modal link
			var href = a.attr('href');
			href = href.replace(/&selected.*/, '');
			for (var i in selected) {
				href += '&selected%5B'+i+'%5D='+encodeURIComponent(selected[i]);
			}
			a.attr('href', href);

			$('.wb-reset-filter').removeClass('hidden');

			wbShow();
			wbShowParams();
			$('.modal:visible:first').modal('hide');
			
			e.preventDefault();

			return false;
		});
	});
}