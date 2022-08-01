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
 * manage page of the plugin
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$PAGE->set_url(new moodle_url('/local/participant_image_upload/manage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_manage', 'local_participant_image_upload'));

require_login();

if (!is_siteadmin() && !is_manager() && !is_coursecreator() && !is_teacher()) {
    redirect($CFG->wwwroot, get_string('no_permission', 'local_participant_image_upload'), null, \core\output\notification::NOTIFY_ERROR);
}

$courseid = optional_param('cid', 0, PARAM_INT);
if ($courseid == 0) {
    redirect($CFG->wwwroot, 'No course selected', null, \core\output\notification::NOTIFY_WARNING);
}

global $DB;
$sql = "SELECT u.id id, (u.username) 'student', u.firstname , u.lastname, u.email
        FROM {role_assignments} r
        JOIN {user} u on r.userid = u.id
        JOIN {role} rn on r.roleid = rn.id
        JOIN {context} ctx on r.contextid = ctx.id
        JOIN {course} c on ctx.instanceid = c.id
        WHERE rn.shortname = 'student'
        AND c.id=" . $courseid;

$studentdata = $DB->get_records_sql($sql);


// Check if there is any active session and the student is present or not.
foreach($studentdata as $student) {
    $activesession = $DB->get_record('local_piu_window', array('course_id' => $courseid, 'active' => 1));
    if($activesession) {
        $student->session = true;
        $student->session_id = $activesession->session_id;
        $record = $DB->get_record('block_face_recog_attendance', array('student_id' => $student->id, 'session_id' => $activesession->session_id));
        if($record) {
            $student->present = true;
        } else {
            $student->present = false;
        }
    } else {
        $student->session = false;
    }
}

$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');


echo $OUTPUT->header();

foreach ($studentdata as $student) {
    $student->image_url = local_participant_image_upload_get_image_url($student->id);
}

$sessions = $DB->get_records('local_piu_window', array('course_id' => $courseid), 'session_id DESC');

$templatecontext = (object)[
    'course_name' => $coursename->fullname,
    'courseid' => $courseid,
    'courselist_url' => new moodle_url("/local/participant_image_upload/courselist.php?cid=" . $courseid),
    'attandancelist_url' => new moodle_url("/local/participant_image_upload/attendancelist.php?cid=" . $courseid),
    'studentlist' => array_values($studentdata),
    'redirecturl' => new moodle_url('/local/participant_image_upload/upload_image.php'),
    'actionurl' => $CFG->wwwroot . '/local/participant_image_upload/submitattendance.php',
    'load_data_url' => $CFG->wwwroot . '/local/participant_image_upload/load_data.php',
    'sessions' => array_values($sessions),
    'number_of_students' => count($studentdata),
];

$PAGE->requires->js_call_amd('local_participant_image_upload/dropdown_handler', 'init', array(
    $CFG->wwwroot . "/local/participant_image_upload/submitattendance.php" . "?cid=" . $courseid
));

echo $OUTPUT->render_from_template('local_participant_image_upload/studentlist', $templatecontext);

echo $OUTPUT->footer();
