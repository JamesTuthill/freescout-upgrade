/**
 * Module's JavaScript.
 */

var av_i18n;
var av_scroll;
var av_att;

var av_viewable_extensions = ['txt', 'diff', 'patch', 'json'];
var av_viewable_mime_types = ['text/plain', 'text/x-diff', 'application/json'];

function eaInitDeleteModal()
{
	$(document).ready(function(){

		$('.ea-confirm-delete:visible:first').click(function(e) {
			
			var attachment_id = $(this).attr('data-attachment-id');

			var button = $(this);
	    	button.button('loading');

			fsAjax({
					action: 'delete_attachment',
					attachment_id: attachment_id
				}, 
				laroute.route('extendedattachments.ajax'), 
				function(response) {
					if (isAjaxSuccess(response)) {
						$('.thread-attachments li[data-attachment-id="'+attachment_id+'"').hide();
						$('.attachments-list li[data-attachment-id="'+attachment_id+'"').hide();
						button.parents('.modal:first').modal('hide');
					} else {
						showAjaxError(response);
					}
					button.button('reset');
				}, true
			);

			return false;
		});

	});
}

function avInit(i18n)
{
	av_i18n = i18n;

	$(document).ready(function(){
		$('.attachment-link').click(function(e) {
			var att = $(this);
			avOpenInViewer($(this));
			e.preventDefault();
		});
	});
}

function avOpenInViewer(att)
{
	if (!att.length) {
		return;
	}
	av_att = att;

	var att_url = att.attr('href');
	var att_name = att.text();

	var container = $('#av-container');
	if (!container.length) {
		var html = '<div id="av-bg" class="av-close-trigger av-element">';
		html += '</div>';

		html += '<div id="av-container" class="av-element av-close-trigger">';

		html += '<div id="av-header">';
		html += '<span id="av-name"></span>';
		html += '<a href="" class="btn btn-primary" id="av-download" download>'+av_i18n.download+' &nbsp;<i class="glyphicon glyphicon-download-alt small"></i></a>';
		html += '<a href="#" id="av-close" class="pull-right av-close-trigger">✕</a>';
		html += '</div>';

		html += '<div id="av-content">';
		html += '</div>';

		html += '<div id="av-prev" class="av-nav">';
		html += '<i class="glyphicon glyphicon-menu-left"></i>';
		html += '</div>';

		html += '<div id="av-next" class="av-nav">';
		html += '<i class="glyphicon glyphicon-menu-right"></i>';
		html += '</div>';

		html += '</div>';
		$('body:first').prepend(html);

		$('.av-close-trigger').click(function(e) {
			avCloseViewer();
		});
		$('#av-header').click(function(e) {
			e.stopPropagation();
		});
		$('#av-next').click(function(e) {
			if (!$(this).hasClass('av-inactive')) {
				var next_li = av_att.parent().next();
				if (next_li.attr('data-attachment-id')) {
					avOpenInViewer(next_li.children('.attachment-link:first'));
				}
			}
			e.stopPropagation();
		});
		$('#av-prev').click(function(e) {
			if (!$(this).hasClass('av-inactive')) {
				var prev_li = av_att.parent().prev();
				if (prev_li.attr('data-attachment-id')) {
					avOpenInViewer(prev_li.children('.attachment-link:first'));
				}
			}
			e.stopPropagation();
		});
	} else {
		$('.av-element').show();
	}

	$('#av-name').text(att_name);
	$('#av-download').attr('href', att_url);

	// Show content.
	var mime_type = att.parent().attr('data-mime');
	var mime_category = mime_type.split('/')[0];
	var ext = att_url.replace(/.*\.([^\.]+)\?.*/, '$1').toLowerCase();

	var content = '<span class="av-content-text">'+av_i18n.unsupported+'<br/><a href="'+att_url+'" download class="av-content-download"><i class="glyphicon glyphicon-download-alt"></i></a></span>';

	if (mime_type == 'application/pdf') {
		content = '<iframe src="'+att_url+'" frameborder="0" id="av-iframe"></iframe>';
	} else if (mime_type == 'message/rfc822' || att_name.toUpperCase() == 'RFC822' || ext == 'eml') {
		// EML
		content = '<iframe sandbox="allow-popups allow-downloads" src="'+laroute.route('extendedattachments.eml_viewer', {attachment_url: encodeURIComponent(att_url), mailbox_id: getGlobalAttr('mailbox_id'), t: Date.now()})+'" frameborder="0" id="av-iframe" style="background-color: white;"></iframe>';
	} else if (av_viewable_extensions.indexOf(ext) != -1 && av_viewable_mime_types.indexOf(mime_type) != -1) {
		// Text files
		content = '<iframe src="'+att_url+'" frameborder="0" id="av-iframe" class="av-iframe-white"></iframe>';
	} else if (mime_category == 'audio') {
		content = '<audio controls src="'+att_url+'" class="av-content-item">'+av_i18n.unsupported+'</audio>';
	} else if (mime_category == 'video') {
		content = '<video controls class="av-content-item"><source src="'+att_url+'" type="video/mp4">'+av_i18n.unsupported+'</video>';
	} else if (mime_category == 'image') {
		content = '<img src="'+att_url+'" class="av-content-item" />';
	}
	$('#av-content').html(content);

	av_scroll = $(window).scrollTop();
	$('html:first').addClass('av-no-scroll');

	// Show/hide prev/next.
	if (!att.parent().next().attr('data-attachment-id')) {
		$('#av-next').addClass('av-inactive');
	} else {
		$('#av-next').removeClass('av-inactive');
	}
	if (!att.parent().prev().attr('data-attachment-id')) {
		$('#av-prev').addClass('av-inactive');
	} else {
		$('#av-prev').removeClass('av-inactive');
	}
}

function avCloseViewer()
{
	$('.av-element').hide();
	$('#av-content').html('');
	$('html:first').removeClass('av-no-scroll');
	$("html").scrollTop(av_scroll);
	setTimeout(function() {
		$("html").scrollTop(av_scroll);
	}, 10);
	setTimeout(function() {
		$("html").scrollTop(av_scroll);
	}, 100);
}

function eaReminderInit(phrases, dialog_text, dialog_submit_text)
{
	// Works for emails and notes
	fsAddFilter('conversation.can_submit', function(value, params) {
		if (!value) {
			return value;
		}

		// Send Anyway
		if ($('body:first').attr('data-ea-send-anyway') == '1') {
			return true;
		}

		// Check attachments
		if ($('.attachments-upload .attachment-loaded').length) {
			return value;
		}

		// Check phrases in the body
		var body = stripTags(getReplyBody());
		var phrase = '';

		for (var i in phrases) {
			if (body.indexOf(phrases[i]) != -1) {
				phrase = phrases[i]
				break;
			}
		}

		if (!phrase) {
			return value;
		}

		dialog_text = dialog_text.replace(':phrase', '<strong>"'+phrase+'"</strong>');

		showModalConfirm(dialog_text, 'ea-send-anyway', {
			on_show: function(modal) {
				modal.children().find('.ea-send-anyway:first').click(function(e) {
					$('body:first').attr('data-ea-send-anyway', '1');
					$(".btn-reply-submit:visible:first").click();
					modal.modal('hide');
				});
			}
		}, dialog_submit_text);
		
		return false;
	}, 100);
}