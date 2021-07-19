var FF_AFTERNOON_TEA_DATE_FIELD_KEY = "afternoontea-date";
var FF_AFTERNOON_TEA_TIME_FIELD_KEY = "afternoontea-time";

var FF_AFTERNOON_TEA_SLOT_BOOKING_MAX = 2;

var SHOW_SLOTS_AVAILABLE_BELOW = 3;

jQuery(function ($) {
  var dateField = $("#field_" + FF_AFTERNOON_TEA_DATE_FIELD_KEY);
  var timeField = $("#field_" + FF_AFTERNOON_TEA_TIME_FIELD_KEY);

  timeField.hide();

  dateField.on("change", function (e) {
    timeField.hide();

    // ie: "02/07/2021"
    var date = convertUkDateToInternational(e.currentTarget.value);

    timeField.find("option").each(function (i, option) {
      if (option.value) {
        option.innerText =
          option.value +
          " (" +
          FF_AFTERNOON_TEA_SLOT_BOOKING_MAX +
          " remaining)";
        option.disabled = "";
      }
    });

    fetch("/wp-admin/admin-ajax.php?action=booking_ajax&date=" + date)
      .then(function (resp) {
        return resp.json();
      })
      .then(function (bookedDates) {
        if (date in bookedDates) {
          Object.keys(bookedDates[date].times).forEach(function (time) {
            var bookingsForTimeSlot = bookedDates[date].times[time];
            time = convert24To12(time);
            var option = timeField.find('option[value="' + time + '"]')[0];

            // Only show available bookings when lower than the constant SHOW_SLOTS_AVAILABLE_BELOW
            if (bookingsForTimeSlot <= SHOW_SLOTS_AVAILABLE_BELOW) {
              option.innerText =
                option.value + " (" + bookingsForTimeSlot + " remaining)";
            }

            if (bookingsForTimeSlot === 0) {
              option.disabled = "disabled";
            }
          });
        }

        timeField.show();
      })
      .catch(function () {
        // Failsafe
        timeField.show();
      });
  });
});

/** "02/07/2021" => "2021-07-02" */
function convertUkDateToInternational(date) {
  var day = date.split("/")[0];
  var month = date.split("/")[1];
  var year = date.split("/")[2];

  return year + "-" + month + "-" + day;
}

/** "14:30" => "2:30 PM" */
function convert24To12(time) {
  var hour = time.split(":")[0];
  var minute = time.split(":")[1];

  var newHour = hour;
  var amPm = "AM";

  if (hour > 12) newHour = hour - 12;
  if (hour > 11) amPm = "PM";

  return newHour + ":" + minute + " " + amPm;
}
