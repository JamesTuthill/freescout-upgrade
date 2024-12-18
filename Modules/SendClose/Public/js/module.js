/**
 * Module's JavaScript.
 */

function scInit()
{
	$(document).ready(function(){
		$('.sc-reply-submit:first').click(function(e) {
			$('.note-statusbar:visible:first select[name="status"]:first').val(3);
			$(".btn-reply-submit:first").click();
		});
	});
}
