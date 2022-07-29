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
 * Download attendance report.
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

require_login();
if (!is_siteadmin() && !is_manager() && !is_coursecreator()  && !is_teacher()) {
    redirect($CFG->wwwroot, get_string('no_permission', 'local_participant_image_upload'), null, \core\output\notification::NOTIFY_ERROR);
}

$courseid = optional_param('cid', 0, PARAM_INT);
$from = optional_param('from', mktime(-5,1,0), PARAM_RAW);  // Get the starting of date (12:01 AM)
$to = optional_param('to', mktime(18,59,59), PARAM_RAW);  // Get the end of date (11:59 PM)
$sort = optional_param('sort', 'ASC', PARAM_RAW);

$dataformat = optional_param('dataformat', '', PARAM_ALPHA);

if ($courseid == 0) {
    redirect($CFG->wwwroot, get_string('no_course_selected', 'local_participant_image'), null, \core\output\notification::NOTIFY_WARNING);
}

$studentdata = student_attandancelist($courseid, $from, $to, $sort);

// foreach($studentdata as $student) {
//     if($student->time) {
//         // New Timezone Object
//         $timezone = new DateTimeZone('Asia/Kolkata');

//         // Converting timestamp to date time format.
//         $date =  new DateTime('@'.$student->time, $timezone);   
//         $date->setTimezone($timezone);
//         $student->timedate = $date->format('m-d-Y H:i:s');
//     } else {
//         $student->timedate = "N/A";
//     }

//     if ($student->time) {
//         $student->time = 'present';
//     } else {
//         $student->time = 'absent';
//     }
//     //$student->timedate = date('m-d-Y H:i:s', $student->time);
// }

$students = [];

foreach ($studentdata as $key => $result) {
    $temp = [];
    $temp['student_id'] = $result->id;
    $temp['student'] = $result->username;
    $temp['firstname'] =$result->firstname;
    $temp['lastname'] =$result->lastname;
    $temp['email'] =$result->email;
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
    if ($temp['time']) {
        $temp['time'] = 'Present';
    } else {
        $temp['time'] = 'Absent';
    }
    array_push($students, $temp);

}

$columns = array(
    'student_id' => 'Student ID',
    'student' => 'Student Name',
    'firstname' => 'Firstname',
    'lastname' => 'Lastname',
    'email' => 'Email',
    'session_id' => 'Session ID',
    'session_name' => 'Session Name',
    'course_id' => 'Course ID',
    'time' => 'Attendance',
    'timedate' => 'Time',
);

//$filename = 'student_attendance_' . $month . '-' . $day . '-' . $year;
$filename = 'student_attendance_' . $courseid;

\core\dataformat::download_data($filename, $dataformat, $columns, $students, function ($record) {
    // Process the data in some way.
    // You can add and remove columns as needed
    // as long as the resulting data matches the $column metadata.
    return $record;
});
