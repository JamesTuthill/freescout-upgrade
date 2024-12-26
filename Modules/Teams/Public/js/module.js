/**
 * Module's JavaScript.
 */
function teamUpdateInit()
{
	$(document).ready(function(){

	    $("#team-delete").click(function(e){
	    	var confirm_html = $('#team_delete_modal').html();

			showModalDialog(confirm_html, {
				width_auto: false,
				on_show: function(modal) {
					modal.children().find('.input-delete-user:first').on('keyup keypress', function(e) {
						if ($(this).val() == 'DELETE') {
							modal.children().find('.button-delete-team:first').removeAttr('disabled');
						} else {
							modal.children().find('.button-delete-team:first').attr('disabled', 'disabled');
						}
					});

					modal.children().find('.button-delete-team:first').click(function(e) {

						var data = $('.team-assign-form:visible:first').serialize();
						if (data) {
							data += '&';
						}
						data += 'action=delete_team';
						data += '&team_id='+getGlobalAttr('team_id');

						modal.modal('hide');

						fsAjax(
							data,
							laroute.route('teams.ajax'),
							function(response) {
								if (isAjaxSuccess(response)) {
									window.location.href = laroute.route('teams.teams');
									return;
								} else {
									showAjaxError(response);
								}
								loaderHide();
							}
						);
						e.preventDefault();
					});
				}
			});
		});
	});
}
