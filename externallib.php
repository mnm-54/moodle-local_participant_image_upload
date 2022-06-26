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
 * external api functions
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");

class local_participant_image_upload_api extends external_api
{
    public static function active_window_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, "Course id"),
                'changedby' => new external_value(PARAM_INT, "User id"),
                'active' => new external_value(PARAM_INT, 'new value')
            )
        );
    }

    public static function active_window($courseid, $changedby, $active)
    {
        global $DB;
        if ($DB->record_exists_select('local_piu_window', 'course_id = :id', array('id' => $courseid))) {
            $record = $DB->get_record_select('local_piu_window', 'course_id = :id', array('id' => $courseid));
            $record->active = $active;
            $record->changedby = $changedby;

            $DB->update_record('local_piu_window', $record);
        } else {
            $record = new stdClass();
            $record->course_id = $courseid;
            $record->active = $active;
            $record->changedby = $changedby;

            $DB->insert_record('local_piu_window', $record);
        }

        return ['status' => $active];
    }

    public static function active_window_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'done')
            )
        );
    }
}
