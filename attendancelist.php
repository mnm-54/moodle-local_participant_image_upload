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
$sql = "SELECT u.id id, (u.username) 'student'
        FROM {role_assignments} r
        JOIN {user} u on r.userid = u.id
        JOIN {role} rn on r.roleid = rn.id
        JOIN {context} ctx on r.contextid = ctx.id
        JOIN {course} c on ctx.instanceid = c.id
        left join moodlebackup.mdl_block_face_recog_attendance fra on r.userid =fra.student_id and c.id= fra.course_id and fra.time=" . $today . "
        WHERE rn.shortname = 'student'
        AND c.id=" . $courseid;

$studentdata = $DB->get_records_sql($sql);

$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');

echo $OUTPUT->header();
echo "<h1>$coursename->fullname</h1><hr />";
echo "Date: " . date("Y/m/d") . "<br>";
echo '
<style>
#student_image_listcss {
    font-family: Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }
  
  #student_image_listcss td, #student_image_listcss th {
    border: 1px solid #ddd;
    padding: 8px;
  }
  
  #student_image_listcss tr:nth-child(even){background-color: #f2f2f2;}
  
  #student_image_listcss tr:hover {background-color: #ddd;}
  
  #student_image_listcss th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #04AA6D;
    color: white;
  }
</style>
<table border="1" id="student_image_listcss">
    <thead>
    <tr>
        <th>Student ID</th>
        <th>Student Name</th>
        <th>Attandance</th>
    </tr>
    </thead><tbody>';

foreach ($studentdata as $student) {
    $attandance = check_student_attandance($courseid, $student->id, $today);
    echo "
    <tr>
        <td>" . $student->id . "</td>
        <td>" . $student->student . "</td>"
        . $attandance .
        "</tr>";
}

echo '</tbody></table>';

echo $OUTPUT->footer();
