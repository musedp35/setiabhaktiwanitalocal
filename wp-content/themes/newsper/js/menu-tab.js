jQuery(document).ready(function() {
    // Select the <span> element you want to change
    var oldElement = document.querySelector('.offcanvas-header .btn_close');
    var newElement = document.createElement('a');

    for (var i = 0, attrs = oldElement.attributes; i < attrs.length; i++) {
        newElement.setAttribute(attrs[i].name, attrs[i].value);
    }
    newElement.setAttribute('href', '#'); 
    newElement.innerHTML = oldElement.innerHTML;
    oldElement.parentNode.replaceChild(newElement, oldElement);

    var capture = jQuery(".modal.fade.bs_model")
    .attr( "tabindex", "0" )
    .focus()
    .keydown(
        function handleKeydown( event ) {

        if ( event.key.toLowerCase() !== "tab" ) {
            return;
        }
        var tabbable = jQuery()
        .add( capture.find( "button, input, select, textarea" ) )
        .add( capture.find( "[href]" ) )
        .add( capture.find( "[tabindex]:not([tabindex='-1'])" ) )
        ;
        var target = jQuery( event.target );
        if ( event.shiftKey ) {
            if ( target.is( capture ) || target.is( tabbable.first() ) ) {
            event.preventDefault();
            tabbable.last().focus();
            }
        } else {
            if ( target.is( tabbable.last() ) ) {
            event.preventDefault();
            tabbable.first().focus();
            }
        }
        }
    );
});