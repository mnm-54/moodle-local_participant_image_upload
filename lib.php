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

    if ($context->contextlevel != CONTEXT_COURSE) {
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


function get_image_url($courseid, $studentid)
{
    $context = context_course::instance($courseid);

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
function student_attandancelist($courseid, $month, $day, $year)
{
    global $DB;
    $today = mktime(0, 0, 0, $month, $day, $year);
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

    return $studentdata;
}
