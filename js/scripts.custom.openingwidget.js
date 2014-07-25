var now = moment().clone();

jQuery(document).ready(function ($) {
    /******************************
    *	[custom script to display countdown until orders open]
    ******************************/

    if (typeof timesArray === 'undefined') {
        return;
    }

    var insertPlaces = $('.pizza_opentimes_widget');
    if (insertPlaces.length > 0) {
        var nextOpen = getNextOpeningTime(now);
        var nextClose = getNextClosingTime(now);

        var openOrClosed = '';
        var open = now > nextOpen && now < nextClose;
        if (open) {
            openOrClosed = $('<span class="wppizza-otc-open">Open</span>');
        }
        else {
            openOrClosed = $('<span class="wppizza-otc-closed">Closed</span>');
        }
        var widgetContent = $('<div class="hidden-xs hidden-sm"></div>')
        widgetContent.append($('<h4>We are ' + openOrClosed[0].outerHTML + ' right now</h4>'));        

        if (!open) {
            var timespan = moment.duration(nextOpen - now);
            widgetContent.append($("<p>and will open again in " + getTimeSpanText(timespan)));
        }
        else {
            widgetContent.append($("<p><a href='/restaurants/'>Browse our Restaurants</a></p>"));
        }

        insertPlaces.html(widgetContent);
    }
});

function getTimeSpanText(timespan) {
    var text = (timespan.days() > 0 ? timespan.days() + " day" : "") +
        (timespan.days() > 1 ? "s " : " ") +
        (timespan.hours() > 0 ? timespan.hours() + " hour" : "") +
        (timespan.hours() > 1 ? "s " : "") +
        " " + timespan.minutes() + " minutes</p>";
    return text;
}

function getNextTimeObject(now) {
    for (var i = 0; i < timesArray.length; i++) {
        if (timesArray[i].openingTimes.open > now || timesArray[i].openingTimes.close > now)
            return timesArray[i];
    }
    //if we are at the end of the week, return the first one
    //this shouldn't happen anymore though because we have more than 1 week in the list now
    return timesArray[0];
}

function getNextOpeningTime(now) {
    var timeobj = getNextTimeObject(now);
    return timeobj.openingTimes.open;
}

function getNextClosingTime(now) {
    var timeobj = getNextTimeObject(now);
    return timeobj.openingTimes.close;
}


