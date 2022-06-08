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

use core\check\check;

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$PAGE->set_url(new moodle_url('/local/participant_image_upload/attendancelist.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_manage', 'local_participant_image_upload'));

if (!is_siteadmin()) {
    redirect($CFG->wwwroot, 'Dont have proper permission to view the page', null, \core\output\notification::NOTIFY_ERROR);
}

$courseid = optional_param('cid', 0, PARAM_INT);
if ($courseid == 0) {
    redirect($CFG->wwwroot, 'No course selected', null, \core\output\notification::NOTIFY_WARNING);
}

global $DB;
$today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
$sql = "SELECT u.id id, (u.username) 'student', fra.time time
        FROM {role_assignments} r
        JOIN {user} u on r.userid = u.id
        JOIN {role} rn on r.roleid = rn.id
        JOIN {context} ctx on r.contextid = ctx.id
        JOIN {course} c on ctx.instanceid = c.id
        left join moodlebackup.mdl_block_face_recog_attendance fra on r.userid =fra.student_id and c.id= fra.course_id and fra.time=" . $today . "
        WHERE rn.shortname = 'student'
        AND c.id=" . $courseid . " order by u.id";

$studentdata = $DB->get_records_sql($sql);

$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');

$templatecontext = (object)[
    'course_name' => $coursename->fullname,
    'courseid' => $courseid,
    'studentlist' => array_values($studentdata),
    'date' => date("Y/m/d")
];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_participant_image_upload/attendancelist', $templatecontext);

echo $OUTPUT->footer();
