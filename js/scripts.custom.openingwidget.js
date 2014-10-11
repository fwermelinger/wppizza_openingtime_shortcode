var now = moment().clone();

jQuery(document).ready(function ($) {
    /******************************
    *	[custom script to display time until orders open]
    ******************************/
    var timesArray = initTimesArray();
    var specialDays = initSpecialDaysArray();
    if (typeof timesArray === 'undefined') {
        return;
    }

    var insertPlaces = $('.pizza_opentimes_widget');
    if (insertPlaces.length > 0) {
        var nextOpen = getNextOpeningTime(now, timesArray, specialDays);
        var nextClose = getNextClosingTime(now, timesArray, specialDays);

        var content = '';
        var open = now > nextOpen && now < nextClose;
        
        //check for closetimes
        if (open) {                    
            var closedTimes = getTodaysBreak(now.weekday());
            if (closedTimes != null && closedTimes.start < now && closedTimes.end > now) {
                open = false;
                nextOpen = closedTimes.end;
            }
        }
        if (open) {
            content = $(otc_translations['titleopen']);
        }
        else {
            var datestring = nextOpen.format("dddd, h:mm a");
            if (nextOpen.weekday() == now.weekday()){
                datestring = otc_translations['translation_today'] + nextOpen.format("h:mm A");
            }
            content = $(otc_translations['titleclosed'].replace('%datestring%', datestring));            
        }

        insertPlaces.html(content);
    }
});

function getTodaysBreak(weekday) {
    for (var b =0; b< closedstandard.length; b++){
        if (closedstandard[b].day == weekday) {
            return {
                start: parseTime(weekday, closedstandard[b].close_start),
                end: parseTime(weekday, closedstandard[b].close_end)
                };
        }
    }
    return null;
}

function getNextTimeObject(now, timesArray, specialDays) {
    for (var i = 0; i < timesArray.length; i++) {
        var daysettings = timesArray[i];
        daysettings = getNormalOrSpecialday(daysettings, specialDays); //Specialday settings override the current days openingtimes
        if (daysettings.openingTimes.open.format('HHmm') != daysettings.openingTimes.close.format('HHmm')) {               
            if (daysettings.openingTimes.open > now || daysettings.openingTimes.close > now) {                
                return daysettings;
            }
        }
    }
    
    //if everything fails prevent from crashing
    return timesArray[0];
}

function getNextOpeningTime(now, timesArray, specialDays) {
    var timeobj = getNextTimeObject(now, timesArray, specialDays);
    return timeobj.openingTimes.open;
}

function getNextClosingTime(now, timesArray, specialDays) {
    var timeobj = getNextTimeObject(now, timesArray, specialDays);
    return timeobj.openingTimes.close;
}

function getNormalOrSpecialday(normalday, specialDaysArray) {
    if (specialDaysArray === null) {
        return normalday;
    }
    for(var i=0; i< specialDaysArray.length; i++) {
        if (normalday.openingTimes.open.format('YYYYMMDD') == specialDaysArray[i].openingTimes.open.format('YYYYMMDD')) {
            return specialDaysArray[i];        
        }
    }
    return normalday;    
}

function initTimesArray() {
    if (typeof openingstandard !== 'undefined'){
        openingstandard = openingstandard;
        var timesArray = [];
        //add first week
        for (var i=0; i < openingstandard.length ; i++){
            timesArray.push(newTimesArrayEntry(parseTime(i, openingstandard[i].open), parseTime(i, openingstandard[i].close)));            
        }
        
        //add another week
        for (var i=0; i < openingstandard.length ; i++){
            timesArray.push(newTimesArrayEntry(parseTime(i+7, openingstandard[i].open), parseTime(i+7, openingstandard[i].close)));            
        }
        return timesArray;
    }
    return null;
}

function initSpecialDaysArray() {    
    if (typeof openingSpecial !== 'undefined'){
        var timesArray = openingSpecial;
        var specialDaysArray = [];
        for (var i = 0; i < timesArray.date.length; i++) {            
            var opentime = new moment(timesArray.date[i] + ' ' + timesArray.open[i]);
            var closetime = new moment(timesArray.date[i] + ' ' + timesArray.close[i]);
            specialDaysArray.push(newTimesArrayEntry(opentime, closetime));
        }
        return specialDaysArray;
    }
    return null;
}

function parseTime(weekday, time) {
    return now.clone()
    .day(weekday)
    .hour(time.substr(0, 2))
    .minute(time.substr(3, 2))
    .second(0);
}

function newTimesArrayEntry(opentime, closetime) {
    return { 'openingTimes': {
        open: opentime,
        close: closetime
        }
    };
}