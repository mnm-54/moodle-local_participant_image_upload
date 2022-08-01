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
 * lib file for getting image file
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serve the files.
 *
 * @param stdClass $course the course object.
 * @param stdClass $cm the course module object.
 * @param context $context the context.
 * @param string $filearea the name of the file area.
 * @param array $args extra arguments (itemid, path, filename).
 * @param bool $forcedownload whether or not force download.
 * @param array $options additional options affecting the file serving.
 * @return bool false if the file not found, just send the file otherwise and do not return anything.
 */
function local_participant_image_upload_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    global $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if ($filearea != 'student_photo') {
        return false;
    }

    $itemid = (int)array_shift($args);

    $fs = get_file_storage();

    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $file = $fs->get_file($context->id, 'local_participant_image_upload', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}


function local_participant_image_upload_get_image_url($studentid)
{
    $context = context_system::instance();

    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'local_participant_image_upload', 'student_photo')) {

        foreach ($files as $file) {
            if ($studentid == $file->get_itemid() && $file->get_filename() != '.') {
                // Build the File URL. Long process! But extremely accurate.
                $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
                // Display the image
                $download_url = $fileurl->get_port() ? $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path() . ':' . $fileurl->get_port() : $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path();
                return $download_url;
            }
        }
    }
    return false;
}

/**
 * check student attandance for the day
 */
function check_student_attandance($cid, $sid, $time)
{
    global $DB;
    $done = $DB->count_records("block_face_recog_attendance", array('student_id' => $sid, 'course_id' => $cid, 'time' => $time));
    if ($done) {
        return "<td style='color:green;'>Present</td>";
    } else {
        return "<td style='color:red;'>Absent</td>";
    }
}

function student_attandancelist($courseid, $from, $to, $sort) {
    global $DB;

    $sql = "SELECT DISTINCT session_id 
    FROM {block_face_recog_attendance}
    WHERE ({block_face_recog_attendance}.time > " . $from . " AND {block_face_recog_attendance}.time < " . $to .")";
    $sessionlist1 = $DB->get_records_sql($sql);

    $sql = "SELECT session_id 
    FROM {local_piu_window} 
    WHERE {local_piu_window}.session_id > " . $from . " AND {local_piu_window}.session_id < " . $to . "
        AND {local_piu_window}.session_id NOT IN (SELECT session_id FROM mdl_block_face_recog_attendance)";
    $sessionlist2 = $DB->get_records_sql($sql);

    $distintsessions = array();
    foreach($sessionlist1 as $session) {
        array_push($distintsessions, $session->session_id);
    }
    foreach($sessionlist2 as $session) {
        array_push($distintsessions, $session->session_id);
    }

    $string = implode(", ", $distintsessions);
 
    $sql = "SELECT {user}.id, {user}.username, {local_piu_window}.session_id, {local_piu_window}.session_name, {course}.id course_id, {block_face_recog_attendance}.time, {user}.firstname, {user}.lastname, {user}.email
        FROM {role_assignments}
        JOIN {user} on {role_assignments}.userid = {user}.id
        JOIN {role} on {role_assignments}.roleid = {role}.id
        JOIN {context} on {role_assignments}.contextid = {context}.id
        JOIN {course} on {context}.instanceid = {course}.id
        LEFT JOIN {local_piu_window} on {course}.id = {local_piu_window}.course_id
        LEFT JOIN {block_face_recog_attendance} on {course}.id = {block_face_recog_attendance}.course_id AND {user}.id = {block_face_recog_attendance}.student_id AND {local_piu_window}.session_id = {block_face_recog_attendance}.session_id
        WHERE {role}.shortname = 'student' AND {course}.id=2 AND {local_piu_window}.session_id in 
        (" . $string . ") 
        GROUP BY {user}.id, {local_piu_window}.session_id
        ORDER BY {local_piu_window}.session_id " . $sort;

    $studentdata = $DB->get_recordset_sql($sql);
    return $studentdata;
}

function student_attendance_update($courseid, $studentid, $sessionid) {
    global $DB;

    $record = $DB->get_record('block_face_recog_attendance', array(
                    'course_id' => $courseid,
                    'student_id' => $studentid,
                    'session_id' => $sessionid
                ));
    if(empty($record)) {
        $record = new stdClass();
        $record->student_id = $studentid;
        $record->course_id = $courseid;
        $record->session_id = $sessionid;
        $record->time = time();
        
        $DB->insert_record('block_face_recog_attendance', $record);
    } else {
        $record->time = time();
        
        $DB->update_record('block_face_recog_attendance', $record);
    }
}

function is_manager() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]); 
}

function is_coursecreator() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]); 
}

function is_teacher() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]); 
}

function get_enrolled_courselist_as_teacher($userid) {
    global $DB;
    $sql = "SELECT lpw.id, c.fullname 'fullname', c.id, lpw.session_id, lpw.active active
                FROM {role_assignments} r
                JOIN {user} u on r.userid = u.id
                JOIN {role} rn on r.roleid = rn.id
                JOIN {context} ctx on r.contextid = ctx.id
                JOIN {course} c on ctx.instanceid = c.id
                LEFT JOIN {local_piu_window} lpw on c.id = lpw.course_id  and lpw.active=1
                WHERE rn.shortname = 'editingteacher' and u.id=" . $userid;
    $courselist = $DB->get_records_sql($sql);
    return $courselist;
}

function insert_attendance($courseid, $session_id)
{
    global $DB;
    $sql = "SELECT u.id student_id,c.id course_id
        FROM {role_assignments} r
        JOIN {user} u on r.userid = u.id
        JOIN {role} rn on r.roleid = rn.id
        JOIN {context} ctx on r.contextid = ctx.id
        JOIN {course} c on ctx.instanceid = c.id
        WHERE rn.shortname = 'student'
        AND c.id=" . $courseid;

    $studentdata = $DB->get_records_sql($sql);

    foreach ($studentdata as $student) {
        $student->session_id = $session_id;
        $student->time = 0;
    }

    $DB->insert_records('block_face_recog_attendance', $studentdata);
}

/**
 * Create a new active session or stops a active session.
 */
function toggle_window($courseid, $changedby, $sessionid, $active) {
    global $DB;
    if ($active) {
        $record = new stdClass();
        $record->course_id = $courseid;
        $record->active = $active;
        $record->session_id = time();
        $record->session_name = get_session_name($courseid);
        $record->changedby = $changedby;

        $DB->insert_record('local_piu_window', $record);

        return $record->session_id;
    } else {
        $record = $DB->get_record('local_piu_window', array('course_id' => $courseid, 'session_id' => $sessionid));

        $record->active = $active;
        $record->changedby = $changedby;

        $DB->update_record('local_piu_window', $record);
    }
}

/**
 * Prepares and returns a session name for a course according to the convention.
 * 
 * Session name: C{courseid}-y/m/d-{nth_session_of_today} (eg. C100-2022/08/01-01, C100-2022/08/01-02)
 */
function get_session_name($courseid) {
    global $DB;
    // Get the total number of sessions of the specific course for today.

    // Setting default timezone.
    date_default_timezone_set('Asia/kolkata');
    $t1 = mktime(0, 0, 0);
    $t2 = mktime(23, 59, 59);

    $sql = "SELECT * FROM {local_piu_window} 
            WHERE {local_piu_window}.session_id > $t1 AND {local_piu_window}.session_id < $t2";

    $records = $DB->get_records_sql($sql);
    $count = count($records) + 1;
    
    // Prepare session name.
    $session_name = "C" . $courseid . "-" . date('Y/m/d', strtotime('now')) . "-" . $count;
    return $session_name;
}
