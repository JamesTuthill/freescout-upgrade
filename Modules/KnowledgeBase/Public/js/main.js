$(document).ready(function(){
	$('#kb-customer-logout-link').click(function(e) {
		$('#customer-logout-form').submit();
		e.preventDefault();
	});
	$('#kb-logout-link').click(function(e) {
		$('#logout-form').submit();
		e.preventDefault();
	});
	$('#kb-category-nav-toggle').click(function(e) {
		$('#kb-category-nav-mobile').toggleClass('hidden-xs')
        $('#kb-mobie-nav-overlay').toggleClass('hidden-xs')
		e.preventDefault();
    });
    $('#kb-category-nav-close').click(function (e) {
        $('#kb-category-nav-mobile').addClass('hidden-xs')
        $('#kb-mobie-nav-overlay').addClass('hidden-xs')
        e.preventDefault();
    });
    $('#kb-mobie-nav-overlay').click(function (e) {
        $('#kb-category-nav-mobile').addClass('hidden-xs')
        $('#kb-mobie-nav-overlay').addClass('hidden-xs')
        e.preventDefault();
    });
});
