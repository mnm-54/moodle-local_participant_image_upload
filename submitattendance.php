<?php

require_once(__DIR__ . '/../../config.php');

require_once(__DIR__ . '/lib.php');

$PAGE->set_url(new moodle_url('/local/participant_image_upload/submitattendance.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_courselist', 'local_participant_image_upload'));

$studentid = optional_param("id", 0, PARAM_INT);
$courseid = optional_param("cid", 0, PARAM_INT);
$sessionid = optional_param("session_id", 0, PARAM_INT);


if($studentid && $courseid && $sessionid) {

    // Check attendance at first.
    if(attendance_status($courseid, $studentid, $sessionid)) {
        redirect(new moodle_url('/local/participant_image_upload/manage.php?cid=' . $courseid), get_string('attendance_already_given', 'local_participant_image_upload'), null, \core\output\notification::NOTIFY_ERROR);
    } else {
        student_attendance_update($courseid, $studentid, $sessionid);
        redirect(new moodle_url('/local/participant_image_upload/manage.php?cid=' . $courseid), get_string('attendance_given', 'local_participant_image_upload'));
    }

} else {
    redirect(new moodle_url('/local/participant_image_upload/manage.php?cid=' . $courseid), get_string('attendance_error', 'local_participant_image_upload'), null, \core\output\notification::NOTIFY_ERROR);
}
