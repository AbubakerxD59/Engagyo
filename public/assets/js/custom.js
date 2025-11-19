// character count
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
// check empty
function empty(value) {
    if (value == "" || value == null || value == undefined) {
        return true;
    }
    else {
        return false;
    }
}
// check link
function checkLink(value) {
    const urlRegex = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/i;
    if (urlRegex.test(value)) {
        return true;
    }
    return false;
}

// check for past date/time
function checkPastDateTime(dateValue, timeValue) {
    const combinedDateTimeString = `${dateValue} ${timeValue}:00`;
    const inputDate = new Date(combinedDateTimeString);
    const now = new Date();
    inputDate.setSeconds(0, 0);
    const isPast = inputDate.getTime() < now.getTime();
    if (isPast) {
        toastr.error("The selected date and time is in the past. Please select a future date/time!");
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