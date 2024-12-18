(function () {
    var module_routes = [
    {
        "uri": "attachments\/ajax",
        "name": "extendedattachments.ajax"
    },
    {
        "uri": "attachments\/eml_viewer",
        "name": "extendedattachments.eml_viewer"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();