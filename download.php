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

require_login();
if (!is_siteadmin() && !is_manager() && !is_coursecreator()) {
    redirect($CFG->wwwroot, get_string('no_permission', 'local_participant_image_upload'), null, \core\output\notification::NOTIFY_ERROR);
}

$courseid = optional_param('cid', 0, PARAM_INT);
$from_month = optional_param('fm', date('m'), PARAM_RAW);
$from_day = optional_param('fd', date('d'), PARAM_RAW);
$from_year = optional_param('fy', date('y'), PARAM_RAW);

$to_month = optional_param('tm', date('m'), PARAM_RAW);
$to_day = optional_param('td', date('d'), PARAM_RAW);
$to_year = optional_param('ty', date('y'), PARAM_RAW);

$dataformat = optional_param('dataformat', '', PARAM_ALPHA);

if ($courseid == 0) {
    redirect($CFG->wwwroot, 'No course selected', null, \core\output\notification::NOTIFY_WARNING);
}

$columns = array(
    'id' => 'Attendance ID',
    'uid' => 'Student ID',
    'student' => 'Student Name',
    'firstname' => 'Firstname',
    'lastname' => 'Lastname',
    'email' => 'Email',
    'session_id' => 'Session ID',
    'time' => 'Attendance',
    'session_name' => 'Session Name',
);

$studentdata = student_attandancelist($courseid, $from_month, $from_day, $from_year, $to_month, $to_day, $to_year);

foreach ($studentdata as $student) {
    if ($student->time) {
        $student->time = 'present';
    } else {
        $student->time = 'absent';
    }
}

//$filename = 'student_attendance_' . $month . '-' . $day . '-' . $year;
$filename = 'student_attendance_' . $courseid;

\core\dataformat::download_data($filename, $dataformat, $columns, $studentdata, function ($record) {
    // Process the data in some way.
    // You can add and remove columns as needed
    // as long as the resulting data matches the $column metadata.
    return $record;
});
