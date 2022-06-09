import $ from "jquery";
export const init = (month, day, year, site_url) => {
  year = String(year).padStart(4, "20");
  month = String(month).padStart(2, "0");
  let today = String(day).padStart(2, "0");

  let datePattern = year + "-" + month + "-" + today;

  let datepicker = document.getElementById("attendance_date");
  datepicker.value = datePattern;

  $("#date_check").on("click", function () {
    if (datepicker.value) {
      let newDate = datepicker.value;
      newDate = newDate.split("-");
      year = newDate[0];
      month = newDate[1];
      today = newDate[2];

      window.location.replace(
        site_url + "&m=" + month + "&d=" + today + "&y=" + year
      );
    } else {
      window.location.replace(site_url);
    }
  });
};
