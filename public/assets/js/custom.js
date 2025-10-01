function getCharacterCount(inputElement) {
    var max = inputElement.data("max");
    var characterCount = 0;
    if (inputElement instanceof $ && inputElement.length > 0) {
        characterCount = inputElement.val().length;
    } else {
        characterCount = 0;
    }
    var text = characterCount + "/" + max + " characters";
    inputElement.next().html(text);
}
function empty(value) {
    if (value == "" || value == null || value == undefined) {
        return true;
    }
    else {
        return false;
    }
}
$(document).ready(function () {
    // check counts
    $('.check_count').on('input', function () {
        getCharacterCount($(this));
    });
});