(function () {
    var module_routes = [
    {
        "uri": "conversation\/send-later\/ajax",
        "name": "sendlater.ajax"
    },
    {
        "uri": "conversation\/send-later\/ajax-html\/{conversation_id}\/{action}",
        "name": "sendlater.ajax_html"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();