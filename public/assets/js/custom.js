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

// Resolve YYYY-MM-DD for "today" in an IANA timezone (falls back to browser local date).
function getTodayInTimezone(timezone) {
    if (!timezone) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    try {
        return new Intl.DateTimeFormat('en-CA', {
            timeZone: timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).format(new Date());
    } catch (e) {
        return getTodayInTimezone(null);
    }
}

// Convert a wall-clock date/time in an IANA timezone to UTC milliseconds.
function zonedDateTimeToUtcMs(dateValue, timeValue, timezone) {
    const [year, month, day] = String(dateValue).split('-').map(Number);
    const [hour, minute] = String(timeValue).split(':').map(Number);

    if (!year || !month || !day || Number.isNaN(hour) || Number.isNaN(minute)) {
        return NaN;
    }

    let utcMs = Date.UTC(year, month - 1, day, hour, minute, 0);
    const formatter = new Intl.DateTimeFormat('en-US', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });

    function partsFor(ms) {
        const map = {};
        formatter.formatToParts(new Date(ms)).forEach(function(part) {
            if (part.type !== 'literal') {
                map[part.type] = part.value;
            }
        });
        return map;
    }

    for (let attempt = 0; attempt < 5; attempt++) {
        const parts = partsFor(utcMs);
        const gotYear = parseInt(parts.year, 10);
        const gotMonth = parseInt(parts.month, 10);
        const gotDay = parseInt(parts.day, 10);
        const gotHour = parseInt(parts.hour, 10);
        const gotMinute = parseInt(parts.minute, 10);

        if (gotYear === year && gotMonth === month && gotDay === day && gotHour === hour && gotMinute === minute) {
            return utcMs;
        }

        const targetMs = Date.UTC(year, month - 1, day, hour, minute, 0);
        const currentMs = Date.UTC(gotYear, gotMonth - 1, gotDay, gotHour, gotMinute, 0);
        utcMs += (targetMs - currentMs);
    }

    return utcMs;
}

// check for past date/time (uses window.userTimezone when set)
function checkPastDateTime(dateValue, timeValue, timezone) {
    const tz = timezone || (typeof window.userTimezone !== 'undefined' ? window.userTimezone : null);
    let inputMs;

    if (tz) {
        inputMs = zonedDateTimeToUtcMs(dateValue, timeValue, tz);
    } else {
        const combinedDateTimeString = `${dateValue} ${timeValue}:00`;
        const inputDate = new Date(combinedDateTimeString);
        inputDate.setSeconds(0, 0);
        inputMs = inputDate.getTime();
    }

    if (Number.isNaN(inputMs)) {
        toastr.error("Please enter a valid schedule date and time.");
        return true;
    }

    const nowMs = Date.now();
    const nowMinuteMs = nowMs - (nowMs % 60000);
    const isPast = inputMs < nowMinuteMs;

    if (isPast) {
        toastr.error("The selected date and time is in the past. Please select a future date/time!");
        return true;
    }

    return false;
}

function initScheduleDateInputs(userTodayDate, timezone) {
    const today = userTodayDate || getTodayInTimezone(timezone || window.userTimezone);
    if (!today) {
        return;
    }

    $('#schedule_date, #createPostScheduleDate, #queueNewPostScheduleDate').each(function() {
        const $input = $(this);
        $input.attr('min', today);
        if (!$input.val()) {
            $input.val(today);
        }
    });

    $('#edit_post_publish_date').each(function() {
        $(this).attr('min', today);
    });
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