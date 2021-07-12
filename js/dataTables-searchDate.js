var minDate, maxDate;

// Custom filtering function which will search data in column four between two values
jQuery.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
  var min = minDate.val();
  var max = maxDate.val();
  var date = new Date('2021 ' + data[1]); // TODO: Massive hack!!!

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
    dom: "Bfrtip",
    order: [[1, "desc"]],
    buttons: [{ extend: "print" }, { extend: "pdfHtml5" }],
  });

  // Refilter the table
  $("#min, #max").on("change", function () {
    table.draw();
  });
});
