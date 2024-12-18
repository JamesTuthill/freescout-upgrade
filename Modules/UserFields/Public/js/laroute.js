(function () {
    var module_routes = [
    {
        "uri": "users\/fields\/ajax-admin",
        "name": "userfields.ajax_admin"
    },
    {
        "uri": "users\/fields\/ajax-search",
        "name": "userfields.ajax_search"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();