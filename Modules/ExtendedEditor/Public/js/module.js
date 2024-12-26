/**
 * Module's JavaScript.
 */

fsAddFilter('conversation.editor_toolbar', function(value) {

    if (typeof(value[0][1]) != 'undefined') {
        value[0][1].splice(6, 0, 'style');
        value[0][1].splice(8, 0, 'table');
        value[0][1].splice(4, 0, 'color');
    }

    return value;
});