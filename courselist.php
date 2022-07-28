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
 * List of visible courses
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$PAGE->set_url(new moodle_url('/local/participant_image_upload/courselist.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_courselist', 'local_participant_image_upload'));

if (!is_siteadmin()) {
    redirect($CFG->wwwroot, 'Dont have proper permission to view the page', null, \core\output\notification::NOTIFY_ERROR);
}

global $DB, $PAGE, $USER;

$sql = "SELECT  c.id AS id,c.fullname fullname, lpw.active active, lpw.session_id FROM {course} c 
        left join {local_piu_window} lpw on c.id =lpw.course_id  and lpw.active=1
        where visible=1;";

$courses = $DB->get_records_sql($sql);

array_shift($courses);

// die(var_dump($courses));

$templatecontext = (object)[
    'course_list' => $courses,
    'redirecturl' => new moodle_url('/local/participant_image_upload/manage.php'),
    'actionurl' => $CFG->wwwroot . '/local/participant_image_upload/togglesession.php',
];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_participant_image_upload/courselist', $templatecontext);

//$PAGE->requires->js_call_amd('local_participant_image_upload/time_window_handler', 'init', array($USER->id));

echo $OUTPUT->footer();
