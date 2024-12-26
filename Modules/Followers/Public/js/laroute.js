(function () {
    var module_routes = [
    {
        "uri": "conversation\/followers\/ajax",
        "name": "conversations.followers.ajax"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();