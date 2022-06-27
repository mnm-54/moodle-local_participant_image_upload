<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * List of student with attendance
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$PAGE->set_url(new moodle_url('/local/participant_image_upload/attendancelist.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_manage', 'local_participant_image_upload'));

if (!is_siteadmin()) {
    redirect($CFG->wwwroot, 'Dont have proper permission to view the page', null, \core\output\notification::NOTIFY_ERROR);
}

$courseid = optional_param('cid', 0, PARAM_INT);
$from_month = optional_param('fm', date('m'), PARAM_RAW);
$from_day = optional_param('fd', date('d'), PARAM_RAW);
$from_year = optional_param('fy', date('y'), PARAM_RAW);

$to_month = optional_param('tm', date('m'), PARAM_RAW);
$to_day = optional_param('td', date('d'), PARAM_RAW);
$to_year = optional_param('ty', date('y'), PARAM_RAW);

if ($courseid == 0) {
    redirect($CFG->wwwroot, 'No course selected', null, \core\output\notification::NOTIFY_WARNING);
}

global $DB, $PAGE;

$studentdata = student_attandancelist($courseid, $from_month, $from_day, $from_year, $to_month, $to_day, $to_year);

// echo "<pre>";
// var_dump($studentdata);
// die;

$students = [];
foreach($studentdata as $student) {
    $student->timedate = date('m-d-Y H:i:s', $student->time);
}
$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');

$templatecontext = (object)[
    'course_name' => $coursename->fullname,
    'courseid' => $courseid,
    'studentlist' => array_values($studentdata),
    'date' => date("Y/m/d")
];


echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_participant_image_upload/attendancelist', $templatecontext);
$PAGE->requires->js_call_amd('local_participant_image_upload/date_handler', 'init', array(
    $from_month, $from_day, $from_year, $to_month, $to_day, $to_year,
    $CFG->wwwroot . "/local/participant_image_upload/attendancelist.php" . "?cid=" . $courseid
));

echo $OUTPUT->download_dataformat_selector(
    get_string('export', 'local_participant_image_upload'), 
    'download.php', 
    'dataformat', 
    array(
        'cid' => $courseid, 
        'fm' => $from_month, 
        'fd' => $from_day, 
        'fy' => $from_year, 
        'tm' => $to_month, 
        'td' => $to_day, 
        'ty' => $to_year
    )
);

echo $OUTPUT->footer();
