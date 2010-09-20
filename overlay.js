var $overlay_wrapper;
var $overlay_panel;

function show_overlay(str) {
    if ( !$overlay_wrapper ) append_overlay(str);
    $overlay_wrapper.fadeIn(700);
}

function hide_overlay() {
    $overlay_wrapper.fadeOut(500);
}

function append_overlay(str) {
    $overlay_wrapper = $('<div id="overlay"></div>').appendTo( $('BODY') );
    $overlay_panel = $('<div id="overlay-panel"></div>').appendTo( $overlay_wrapper );

    $overlay_panel.html( str );

    attach_overlay_events();
}


function attach_overlay_events() {
    $('A.hide-overlay', $overlay_wrapper).click( function(ev) {
        ev.preventDefault();
        hide_overlay();
    });
}

$(function() {
    $('A.show-overlay').click( function(ev) {
        ev.preventDefault();
        show_overlay();
    });
});