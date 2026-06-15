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
// check link (includes URLs with query params e.g. ?fbid=123&set=a.456)
function checkLink(value) {
    if (!value || typeof value !== 'string') {
        return false;
    }
    var trimmed = value.trim();
    if (!trimmed || trimmed.length > 2048) {
        return false;
    }
    // Post copy often contains spaces/newlines; only single-line URL-like input counts as a link.
    if (/\s/.test(trimmed)) {
        return false;
    }
    if (/^https?:\/\//i.test(trimmed)) {
        try {
            new URL(trimmed);
            return true;
        } catch (e) {
            return false;
        }
    }
    return /^([\w-]+\.)+[a-z]{2,}(\/\S*)?$/i.test(trimmed);
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

// Custom Tooltip Function - Initialize tooltips globally
function initTooltips() {
    $('.has-tooltip').each(function() {
        var $element = $(this);
        var tooltipText = $element.data('tooltip');
        
        // Create tooltip element if not exists and has tooltip data
        if (tooltipText && $element.find('.custom-tooltip').length === 0) {
            $element.append('<span class="custom-tooltip">' + tooltipText + '</span>');
        }
    });
}

$(document).ready(function () {
    // check counts
    $('.check_count').on('input', function () {
        getCharacterCount($(this));
    });

    // Initialize tooltips globally
    initTooltips();
});