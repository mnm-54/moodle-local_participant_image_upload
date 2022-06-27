<?php

require_once(__DIR__ . '/../../config.php');

require_once(__DIR__ . '/lib.php');



$PAGE->set_url(new moodle_url('/local/participant_image_upload/test.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_courselist', 'local_participant_image_upload'));

$course_id = optional_param("cid", 0, PARAM_INT);
$session_id = optional_param("session", 0, PARAM_INT);
$active = optional_param("active", 0, PARAM_INT);

if($active) {

    toggle_window($course_id, $USER->id, $session_id, 1);

    redirect(new moodle_url('/local/participant_image_upload/courselist.php'), get_string('start_text', 'local_participant_image_upload'));
} else {
    toggle_window($course_id, $USER->id, $session_id, 0);
    redirect(new moodle_url('/local/participant_image_upload/courselist.php'), get_string('stop_text', 'local_participant_image_upload'));
}