/**
 * Module's JavaScript.
 */

function chInit()
{
	$(document).ready(function(){

		summernoteInit('#ch_homepage_html', {
			insertVar: false,
			disableDragAndDrop: false,
			height: 275,
			toolbar: [
			    ['style', ['style', 'bold', 'italic', 'underline', 'color', 'lists', 'paragraph', 'removeformat', 'link', 'table']],
			    ['insert', ['picture', 'video']],
			    ['view', ['codeview']]
			],
			buttons: {
				removeformat: EditorRemoveFormatButton,
				lists: EditorListsButton
			},
			callbacks: {
				onImageUpload: function(files) {
					if (!files) {
						return;
					}
					for (var i = 0; i < files.length; i++) {
						editorSendFile(files[i], false, false, '#ch_homepage_html');
					}
				}
			}
		});

		$('#ch-dashboard-path').on('keyup', function(event) {
			var val = $(this).val().replace(/[^a-zA-Z0-9]/, '');
			$(this).val(val);

			if (val) {
				$('#ch-options').removeClass('hidden');
			} else {
				$('#ch-options').addClass('hidden');
			}
		});

		$('#ch-login-path').on('keyup', function(event) {
			var val = $(this).val().replace(/[^a-zA-Z0-9]/, '');
			$(this).val(val);
		});

		$('#ch-type').on('change', function(event) {
			if ($(this).val() == 'page') {
				$('.ch-homepage-page').removeClass('hidden');
				$('.ch-homepage-redirect').addClass('hidden');
				$('#ch-homepage-redirect').val('');
			} else {
				$('.ch-homepage-redirect').removeClass('hidden');
				$('.ch-homepage-page').addClass('hidden');
			}
		});

	});
}
