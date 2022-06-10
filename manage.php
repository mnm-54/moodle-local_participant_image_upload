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

if (!is_siteadmin()) {
    redirect($CFG->wwwroot, 'Dont have proper permission to view the page', null, \core\output\notification::NOTIFY_ERROR);
}

$courseid = optional_param('cid', 0, PARAM_INT);
if ($courseid == 0) {
    redirect($CFG->wwwroot, 'No course selected', null, \core\output\notification::NOTIFY_WARNING);
}

global $DB;
$sql = "SELECT u.id id, (u.username) 'student'
        FROM {role_assignments} r
        JOIN {user} u on r.userid = u.id
        JOIN {role} rn on r.roleid = rn.id
        JOIN {context} ctx on r.contextid = ctx.id
        JOIN {course} c on ctx.instanceid = c.id
        WHERE rn.shortname = 'student'
        AND c.id=" . $courseid;

$studentdata = $DB->get_records_sql($sql);

$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');

$templatecontext = (object)[
    'course_name' => $coursename->fullname,
    'courseid' => $courseid,
    'studentlist' => array_values($studentdata),
    'redirecturl' => new moodle_url('/local/participant_image_upload/upload_image.php')
];

$redirecturl = $CFG->wwwroot . '/local/participant_image_upload/upload_image.php';

echo $OUTPUT->header();
echo "<h1>$coursename->fullname</h1><hr />";
echo "<button onclick=\"location.href = '" . $CFG->wwwroot . "/local/participant_image_upload/attendancelist.php?cid=" . $courseid . "';\">Check attendance list</button>";
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
        <th>Student name</th>
        <th>Preview</th>
        <th>Upload image</th>
    </tr>
    </thead><tbody>';

foreach ($studentdata as $student) {
    $btnurl = ($redirecturl . "?cid=" . $courseid . "&id=" . $student->id);
    echo "
    <tr>
        <td>" . $student->student . "</td>
        <td>" . get_image_url($courseid, $student->id) . "</td>
        <td>
        <button
            type='button'
            class='btn btn-warning'
            onclick=" . "location.href='" . $btnurl . "'>" .
        "upload
        </button>
        </td>
    </tr>";
}

echo '</tbody></table>';

echo $OUTPUT->footer();
