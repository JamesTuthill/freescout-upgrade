/**
 * Module's JavaScript.
 */

function ufInitUserFieldsAdmin()
{
	$(document).ready(function() {

		ufInitUserFieldsForm('.uf-user-field-form', 'user_field_update');

		// Delete
		$(".uf-user-field-delete").click(function(e){
			var button = $(this);

			showModalConfirm(Lang.get("messages.uf_confirm_delete_user_field"), 'uf-delete-ok', {
				on_show: function(modal) {
					var user_field_id = button.attr('data-user_field_id');
					modal.children().find('.uf-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'user_field_delete',
								user_field_id: user_field_id
							}, 
							laroute.route('userfields.ajax_admin'), 
							function(response) {
								showAjaxResult(response);
								button.button('reset');
								$('#uf-user-field-'+user_field_id).remove();
							}
						);
					});
				}
			}, Lang.get("messages.delete"));
			e.preventDefault();
		});

		if ($('#uf-user-fields-index').length) {
			sortable('#uf-user-fields-index', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			})[0].addEventListener('sortupdate', function(e) {
			    // ui.item contains the current dragged element.
			    var user_fields = [];
			    $('#uf-user-fields-index > .panel').each(function(idx, el){
				    user_fields.push($(this).attr('data-user-field-id'));
				});
				fsAjax({
						action: 'user_field_update_sort_order',
						user_fields: user_fields,
					}, 
					laroute.route('userfields.ajax_admin'), 
					function(response) {
						showAjaxResult(response);
					}
				);
			});
		}
	});
}

// Create user field
function ufInitNewUserField(jmodal)
{
	$(document).ready(function(){
		ufInitUserFieldsForm('.uf-new-user-field-form', 'user_field_create');
	});
}

// Backend form
function ufInitUserFieldsForm(selector, action)
{
	$(selector).on('submit', function(e) {
		var user_field_id = $(this).attr('data-user_field_id');
		var data = $(this).serialize();
		//data += '&mailbox_id='+getGlobalAttr('mailbox_id');
		data += '&action='+action;
		if (user_field_id) {
			data += '&user_field_id='+user_field_id;
		}
		
		var button = $(this).children().find('button:first');
    	button.button('loading');

		fsAjax(data, 
			laroute.route('userfields.ajax_admin'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					if (typeof(response.msg_success) != "undefined") {
						// Update
						button.button('reset');
						showFloatingAlert('success', response.msg_success);
						var html = ' '+htmlEscape(response.name)+' <small>(ID: '+user_field_id+')</small>';
						if (response.required) {
							html += ' <i class="required-asterisk"></i>';
						}
						$('#uf-user-field-'+user_field_id+' .panel-title a:first span:first').html(html);
					} else {
						// Create
						window.location.href = '';
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

	$(selector+' :input[name="type"]:first').on('change', function(e) {
		$(selector+' .cf-type :input').attr('disabled', 'disabled');
		$(selector+' .cf-type-'+$(this).val()+' :input').removeAttr('disabled');

		$(selector+' .cf-type').addClass('hidden');
		$(selector+' .cf-type-'+$(this).val()).removeClass('hidden');
	});

	ufApplyOptionsListeners(selector);
}

function ufApplyOptionsListeners(selector)
{
	$(selector+' .cf-option[data-inited!="1"] .cf-option-add').on('click', function(e) {
		var button = $(e.target);
		var container = button.parents('.cf-options:first');

		var new_option = container.children('.cf-option:first').clone();

		// Get max id
		var max_id = 1;
		container.children('.cf-option').find('input').each(function(index, el){
		    if ($(this).attr('data-option-id') > max_id) {
		    	max_id = $(this).attr('data-option-id')*1;
		    }
		});

		new_option.removeAttr('data-inited');
		new_option.children('input:first').attr('name', 'options['+(max_id+1)+']').attr('data-option-id', max_id+1).val('');
		new_option.appendTo(container);

		cfApplyOptionsListeners(selector);
	});

	$(selector+' .cf-option[data-inited!="1"] .cf-option-remove').on('click', function(e) {
		var button = $(e.target);
		var container = button.parents('.cf-options:first');
		if (container.children('.cf-option').length == 1) {
			return;
		}
		showModalConfirm(Lang.get("messages.uf_confirm_delete_option"), 'cf-option-remove-ok', {
			on_show: function(modal) {
				modal.children().find('.cf-option-remove-ok:first').click(function(e) {
					button.parents('.cf-option:first').addClass('cf-removed').children('input:first').attr('disabled', 'disabled');
					modal.modal('hide');
				});
			}
		}, Lang.get("messages.delete"));
	});

	$(selector+' .cf-option[data-inited!="1"] .cf-option-restore').on('click', function(e) {
		var button = $(e.target);
		button.parents('.cf-option:first').removeClass('cf-removed').children('input:first').removeAttr('disabled');
	});

	// To avoid double triggering
	$(selector+' .cf-option[data-inited!="1"]').attr('data-inited', '1');

	sortable(selector+' .cf-options', {
	    handle: '.cf-option-handle',
	    //forcePlaceholderSize: true 
	});
}

// Frontend
function ufInitUserFields()
{
	$(document).ready(function() {
		$('.uf-cf-type-date').flatpickr({allowInput: true});

		ufCfInitAutosuggest();
	});
}

function ufCfInitAutosuggest()
{
	$('.uf-cf-autosuggest:visible').each(function(index, el) {
		var input = $(this);
		if (input.data('select2')) {
			return;
		}
		
		var placeholder = '';
		if (!input.attr('required')) {
			placeholder = input.children('option:first').text();
		}
		var options = {
			ajax: {
				url: laroute.route('userfields.ajax_search'),
				dataType: 'json',
				delay: 250,
				cache: true,
				data: function (params) {
					return {
						q: params.term,
						user_field_id: input.attr('name'),
						page: params.page
					};
				}
			},
			allowClear: true,
			placeholder: placeholder,
			tags: true,
			minimumInputLength: 1,
			language: {
	            inputTooShort: function(args) {
	                return "";
	            }
	        }
		}
		input.select2(options);
	});
}

function ufInit(user_vars)
{
	fsAddFilter('editor.vars', function(vars) {
		vars.user = {...vars.user, ...user_vars};
		return vars;
	});
}