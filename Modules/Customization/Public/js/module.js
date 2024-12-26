/**
 * Module's JavaScript.
 */

function initCustomization()
{
	$(document).ready(function(){
		$('.cust-img-remove').click(function(e) {
			var wrapper = $(this).parents('.cust-img-wrapper:first');

			wrapper.children('.cust-img-remove-input:first').val(1);
			wrapper.children().find('.cust-img-custom').hide();
			wrapper.children().find('.cust-img-default').removeClass('hidden');

			$(this).remove();

			e.preventDefault();
		});

		$('#cust-header-input').keyup(function(e) {
			$('#cust-header-color').val('#'+$(this).val());
			custSetHeaderColor($(this).val());
		});

		$('#cust-header-color').change(function(e) {
			var color = $(this).val().replace('#', '');
			$('#cust-header-input').val(color);
			custSetHeaderColor(color);
		});

		$('#cust-header-reset').click(function(e) {
			custHeaderReset($(this).attr('data-default-color'));
			e.preventDefault();
		});

		summernoteInit('#customization_footer', {
			insertVar: false,
			disableDragAndDrop: true
			/*callbacks: {
				onInit: function() {
					$(selector).parent().children().find('.note-statusbar').remove();
				}
			}*/
		});
	});
}

function custSetHeaderColor(color)
{
	$('.navbar-static-top:first').css({'background-color': '#'+color});
	$('.navbar-default .navbar-nav > .active > a:first').css({'background-color': '#'+color});
}


function custHeaderReset(color)
{
	$('#cust-header-color').val('#'+color);
	$('#cust-header-input').val(color);
	custSetHeaderColor(color);
}