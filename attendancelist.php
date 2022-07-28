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

if (!is_siteadmin() && !is_manager() && !is_coursecreator()) {
    redirect($CFG->wwwroot, get_string('no_permission', 'local_participant_image_upload'), null, \core\output\notification::NOTIFY_ERROR);
}

// Setting default value.
date_default_timezone_set('Asia/kolkata');
$d1 = mktime(0, 0, 0);
$d2 = mktime(23, 59, 59);

$courseid = optional_param('cid', 0, PARAM_INT);
$from = optional_param('from', $d1, PARAM_RAW);  
$to = optional_param('to', $d2, PARAM_RAW);

$sort = optional_param('sort', 'ASC', PARAM_RAW);

if ($courseid == 0) {
    redirect($CFG->wwwroot, get_string('no_course_selected', 'local_participant_image'), null, \core\output\notification::NOTIFY_WARNING);
}

global $DB, $PAGE;
$studentdata = student_attandancelist($courseid, $from, $to, $sort);

$students = [];

foreach ($studentdata as $key => $result) {
    $temp = [];
    $temp['student'] = $result->username;
    $temp['firstname'] =$result->firstname;
    $temp['lastname'] =$result->lastname;
    $temp['email'] =$result->email;
    $temp['student_id'] = $result->id;
    $temp['session_id'] = $result->session_id;
    $temp['session_name'] = $result->session_name;
    $temp['course_id'] = $result->course_id;
    $temp['time'] = $result->time;
    
    if($temp['time']) {
        // New Timezone Object.
        $timezone = new DateTimeZone('Asia/Kolkata');

        // Converting timestamp to date time format.
        $date =  new DateTime('@'.$temp['time'], $timezone);   
        $date->setTimezone($timezone);
        $temp['timedate'] = $date->format('m-d-Y H:i:s');
    } else {
        $temp['timedate'] = "N/A";
    }
    array_push($students, $temp);

}

$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');

$templatecontext = (object)[
    'course_name' => $coursename->fullname,
    'courseid' => $courseid,
    'courselist_url' => new moodle_url("/local/participant_image_upload/courselist.php?cid=" . $courseid),
    'studentlist_url' => new moodle_url("/local/participant_image_upload/manage.php?cid=" . $courseid),
    'studentlist' => array_values($students),
    'date' => date("Y/m/d"),
    'flag' => strtolower($sort)
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_participant_image_upload/attendancelist', $templatecontext);

$PAGE->requires->js_call_amd('local_participant_image_upload/date_time_handler', 'init', array(
    $from, $to, $sort,
    $CFG->wwwroot . "/local/participant_image_upload/attendancelist.php" . "?cid=" . $courseid
));

echo $OUTPUT->download_dataformat_selector(
    get_string('export', 'local_participant_image_upload'), 
    'download.php', 
    'dataformat', 
    array(
        'cid' => $courseid, 
        'from' => $from,
        'to' => $to,
        'sort' => $sort,
    )
);

echo $OUTPUT->footer();
