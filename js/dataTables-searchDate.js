var minDate, maxDate;

// Custom filtering function which will search data in column four between two values
jQuery.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
  var min = minDate.val();
  var max = maxDate.val();
  // var date = new Date((new Date()).getFullYear() + " " + data[1]); // TODO: Massive hack!!! Will always assume current year. Will BREAK around December time.
  var date = new Date(data[1]);

  if (min) {
    // Make max inclusive
    min.setHours(0);
    min.setMinutes(0);
    min.setSeconds(0);
  }

  if (max) {
    // Make max inclusive
    max.setHours(23);
    max.setMinutes(59);
    max.setSeconds(59);
  }

  if (
    (min === null && max === null) ||
    (min === null && date <= max) ||
    (min <= date && max === null) ||
    (min <= date && date <= max)
  ) {
    return true;
  }

  return false;
});

jQuery(function ($) {
  // Create date inputs
  minDate = new DateTime($("#min"), {
    format: "MMMM Do YYYY",
  });
  maxDate = new DateTime($("#max"), {
    format: "MMMM Do YYYY",
  });

  // NOTE: Make sure to store the DataTable into a variable called `table`. Used later as `table.draw()`
  var table = $("table.dataTablePrintPDF").DataTable({
    responsive: true,
    stateSave: true,
    colReorder: true,
    dom: "Bfrtip",
    order: [[1, "desc"]],
    buttons: [{ extend: "print" }, { extend: "pdfHtml5" }],
  });

  // Refilter the table
  $("#min, #max").on("change", function () {
    table.draw();
  });
});
