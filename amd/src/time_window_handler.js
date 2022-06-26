import $ from "jquery";
import Ajax from "core/ajax";

export const init = (userId) => {
  $(".stop-btn").on("click", function () {
    let courseID = $(this).attr("id");

    toggle_time_window(courseID, userId, 0);
  });

  $(".start-btn").on("click", function () {
    let courseID = $(this).attr("id");

    toggle_time_window(courseID, userId, 1);
  });

  let toggle_time_window = (course_id, userid, value) => {
    // ajax call
    let wsfunction = "local_participant_image_upload_active_window";
    let params = {
      courseid: course_id,
      changedby: userid,
      active: value,
    };
    let request = {
      methodname: wsfunction,
      args: params,
    };

    Ajax.call([request])[0]
      .done(function (value) {
        window.console.log(value);
        window.location.href = $(location).attr("href");
      })
      .fail(Notification.exception);
  };
};
