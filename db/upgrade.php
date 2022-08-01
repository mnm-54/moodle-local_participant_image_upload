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
 * Upgrade file.
 *
 * @package    block_face_recognition_student_attendance
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param int $oldversion The old version of the local participant image uplaod
 * @return bool
 */
function xmldb_local_participant_image_upload_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.
    

    if ($oldversion < 2022051001) {

        // Changing precision of field session_id and session_name on table local_piu_window.
        $table = new xmldb_table('local_piu_window');
        $field1 = new xmldb_field('session_id', XMLDB_TYPE_INTEGER, '15', null, XMLDB_NOTNULL, false);
        $field2 = new xmldb_field('session_name', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, false);
        /// To change the precision of one field:
        $dbman->change_field_precision($table, $field1);
        $dbman->change_field_precision($table, $field2);
        // Plugin savepoint reached.
        upgrade_plugin_savepoint(true, 2022051001, 'local', 'participant_image_upload');
    }

    return true;
}
