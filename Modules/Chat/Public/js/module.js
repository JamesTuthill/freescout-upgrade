/**
 * Module's JavaScript.
 */

function chatInitSettings()
{
	$(document).ready(function(){

		$('#chat-show-preview').click(function(e) {
			//$('body:first').append($('#chat-widget-code').val());
            FreeScoutW = JSON.parse($('#chat-widget-settings').val());
            var a = document.createElement('script');
            var m = document.getElementsByTagName('script')[0];
            a.async = 1;
            a.id = "freescout-w";
            a.src = $('#chat-widget-url').val();
            m.parentNode.insertBefore(a, m);

			e.preventDefault();

			$(this).fadeOut();
		});

		$('#chat-widget-form input:visible,#chat-widget-form select:visible').on('change keyup', function(e) {
			$('#chat-widget-code-wrapper').addClass('hidden');
			$('#chat-widget-save-wrapper').removeClass('hidden');
		});

		$(".chat-colorpicker").colorpicker({
            customClass: 'colorpicker-2x',
            sliders: {
                saturation: {
                    maxLeft: 200,
                    maxTop: 200
                },
                hue: {
                    maxTop: 200
                },
                alpha: {
                    maxTop: 200
                }
            }
        }).on('changeColor.colorpicker', function(event) {
            $('#chat-widget-code-wrapper').addClass('hidden');
			$('#chat-widget-save-wrapper').removeClass('hidden');
			 return true;
        }).trigger("change");
	});
}