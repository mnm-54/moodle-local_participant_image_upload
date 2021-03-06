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

/**
 * $return student attendance list for the day
 */
function student_attandancelist($courseid, $from_month, $from_day, $from_year, $to_month, $to_day, $to_year)
{
    global $DB;
    $from = mktime(0, 0, 0, $from_month, $from_day, $from_year);
    $to = mktime(23, 59, 59,  $to_month, $to_day, $to_year);

    $sql = "SELECT fra.id as id, u.id AS uid, u.username AS student, u.firstname, u.lastname, u.email, fra.session_id, fra.time, lpi.session_name
            FROM {block_face_recog_attendance} fra 
            JOIN {user} u on fra.student_id = u.id  
            JOIN {local_piu_window} lpi on fra.session_id = lpi.session_id
            WHERE fra.session_id>" . $from . " and fra.session_id<" . $to . " and fra.course_id =" . $courseid . " order by lpi.session_id";

    $studentdata = $DB->get_records_sql($sql);
    return $studentdata;
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

    // die(var_dump($studentdata));

    $DB->insert_records('block_face_recog_attendance', $studentdata);
}

function toggle_window($courseid, $changedby, $sessionid, $active)
{
    global $DB;
    if ($active) {
        $record = new stdClass();
        $record->course_id = $courseid;
        $record->active = $active;
        $record->session_id = time();
        $record->session_name = "C-" . $courseid . "-" . rand(1, 100);
        $record->changedby = $changedby;

        // var_dump($record);

        $DB->insert_record('local_piu_window', $record);

        return $record->session_id;
    } else {
        $record = $DB->get_record('local_piu_window', array('course_id' => $courseid, 'session_id' => $sessionid));
        var_dump($record);

        $record->active = $active;
        $record->changedby = $changedby;

        var_dump($record);

        $DB->update_record('local_piu_window', $record);
    }
    // if ($DB->record_exists_select('local_piu_window', 'course_id = :id and active = :active', array('id' => $courseid, 'active' => 1))) {
    //     $record = $DB->get_record_select('local_piu_window', 'course_id = :id', array('id' => $courseid));
    //     $record->active = $active;
    //     $record->changedby = $changedby;

    //     $DB->update_record('local_piu_window', $record);
    // } else {
    //     $record = new stdClass();
    //     $record->course_id = $courseid;
    //     $record->active = $active;
    //     $record->session_id = time();
    //     $record->session_name = "C-" . $courseid . "-" . rand(1, 100);
    //     $record->changedby = $changedby;

    //     $DB->insert_record('local_piu_window', $record);
    // }
}
