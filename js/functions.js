// Prevent submission of the form if it only contains whitespace
// (runs onclick of submit button, which applies to submission of the form)
function empty() {
    var x = document.getElementById( "url" );
    var y = x.value.replace( new RegExp( "\\s+" ), "" );
    if ( y == "" ) {
        x.focus();
        return false;
    }
}

// Show a clear button when the input isn't empty
// (runs oninput of input field, which applies any time its value changes
function clearButton() {
    var input = document.getElementById( "url" );
    var button = document.getElementById( "clear-button" );
    if ( input.value != "" ) {
        button.classList.add( "visible" );
    } else {
        button.classList.remove( "visible" );
    }
}

// If the clear button is clicked, clear the input, hide the button,
// and return focus to the input field
function clearInput() {
    var input = document.getElementById( "url" );
    var button = document.getElementById( "clear-button" );
    input.value = "";
    button.classList.remove( "visible" );
    input.focus();
}