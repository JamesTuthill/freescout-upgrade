/**
 * Module's JavaScript.
 */

// Frontend
function initFollowers()
{
	$(document).ready(function() {
		followersListeners();

		fsAddAction('conversation.follow', function(params) {
			if (!$('.followers-block .followers-item-'+params.user_id).length) {
				followersAdd([{
					user_id: params.user_id,
					name: $('.followers-block:first').attr('data-auth_user_name')
				}]);
			} else {
				$('.followers-block .followers-item-'+params.user_id).addClass('followers-subscribed');
			}
		});

		fsAddAction('conversation.unfollow', function(params) {
			$('.followers-block .followers-item-'+params.user_id).removeClass('followers-subscribed');
		});
	});
}

function followersListeners()
{
	$('.followers-block .followers-item').off('click').on('click', function(e) {
		if (!$(this).hasClass('followers-self')) {
			var action = '';
			if ($(this).hasClass('followers-subscribed')) {
				action = 'unsubscribe';
			} else {
				action = 'subscribe';
			}
			var button = $(this);
			followersSubscribe(action, [button.attr('data-user_id')], button);
		}
		e.preventDefault();
	});
}

function followersSubscribe(action, user_ids, buttons, callback)
{
	fsAjax({
			action: action,
			conversation_id: getGlobalAttr('conversation_id'),
			user_ids: user_ids
		}, 
		laroute.route('conversations.followers.ajax'), 
		function(response) {
			if (isAjaxSuccess(response)) {
				if (action == 'subscribe') {
					buttons.addClass('followers-subscribed');
				} else {
					buttons.removeClass('followers-subscribed');
				}
			} else {
				showAjaxResult(response);
			}
			if (typeof(callback) != "undefined") {
				callback(response);
			}
		}, true
	);
}

function initFollowersAdd(jmodal)
{
	$(document).ready(function(){
		$('.followers-add-form').submit(function(e) {
			var button = $(this).children().find('.btn-primary:first');
			button.button('loading');

			var user_ids = [];
			var attr_selector = [];
			var followers = [];

            $.each($(this).children().find(".follower-add-user:checked"), function(){
                user_ids.push($(this).val());
                attr_selector.push('.followers-item-'+$(this).val());
                followers.push({
                	user_id: $(this).val(),
                	name: $(this).next().text()
                });
            });

			followersSubscribe('subscribe', 
				user_ids, 
				$(attr_selector.join(',')),
				function(response) {
					if (isAjaxSuccess(response)) {
						followersAdd(followers);
						jmodal.modal('hide');
					}
				}
			);

			e.preventDefault();
		});
	});
}

function followersAdd(followers)
{
	var first_nonfollower = $('.followers-item:not(.followers-subscribed):first');
	var container = null;
	if (!first_nonfollower.length) {
		container = $('.followers-list');
	}
	for (var i in followers) {
		var follower = followers[i];

		if ($('.followers-item-'+follower.user_id).length) {
			continue;
		}

		var html = '<li>';
		html += '<a href="#" data-user_id="'+follower.user_id+'" class="help-link followers-item followers-item-'+follower.user_id+' followers-subscribed">';
		html += '<i class="glyphicon glyphicon-bell"></i> '+follower.name;
		html += '</a>';
		html += '</li>';                                

		if (first_nonfollower.length) {
			$(html).insertBefore(first_nonfollower);
		 } else {
		 	container.append(html);
		 }
	}
	followersListeners();
}