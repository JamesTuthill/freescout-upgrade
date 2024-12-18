(function () {
    var module_routes = [
    {
        "uri": "teams\/ajax",
        "name": "teams.ajax"
    },
    {
        "uri": "teams",
        "name": "teams.teams"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();