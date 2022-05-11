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
 * image upload functionalities
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/participant_image_upload/classes/form/upload_image_form.php');

$PAGE->set_url(new moodle_url('/local/participant_image_upload/upload_image.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_upload', 'local_participant_image_upload'));

require_login();

if (!is_siteadmin()) {
    redirect($CFG->wwwroot, 'Dont have proper permission to view the page', null, \core\output\notification::NOTIFY_ERROR);
}

//Instantiate imageupload_form 
$mform = new imageupload_form();
if ($data = $mform->get_data()) {
    // ... store or update $student
    file_save_draft_area_files(
        $data->student_photo,
        $data->context_id,
        'local_participant_image_upload',
        'student_photo',
        $data->id,
        array('subdirs' => 0, 'maxfiles' => 50)
    );
    die(var_dump($data));
}

$courseid = optional_param('cid', 0, PARAM_INT);
$studentid = optional_param('id', -1, PARAM_INT);

// get context
if ($courseid) {
    $context = context_course::instance($courseid);
}

$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');
$studentname = $DB->get_record_select('user', 'id=:id', array('id' => $studentid), 'firstname ,lastname');

// prepare image file
if (empty($student->id)) {
    $student = new stdClass;
    $student->id = $studentid;
    $student->student_name = $studentname->firstname . ' ' . $studentname->lastname;
    $student->course = $courseid;
    $student->context_id = $context->id;
}

$draftitemid = file_get_submitted_draft_itemid('student_photo');

file_prepare_draft_area(
    $draftitemid,
    $context->id,
    'local_participant_image_upload',
    'student_photo',
    $student->id,
    array('subdirs' => 0, 'maxfiles' => 1)
);

$student->student_photo = $draftitemid;

$mform->set_data($student);

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
