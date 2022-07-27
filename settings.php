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
 * plugin settings
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../config.php');

// For admin users and manager users. 
// This block adds a link to site administraion root. 
if (has_capability('local/participant_image_upload:view', context_system::instance())) {
    $ADMIN->add('root', new admin_category('local_attendance_plugin', get_string('attendance_plugin', 'local_participant_image_upload')));
    $ADMIN->add('local_attendance_plugin', 
                new admin_externalpage('local_plugin_courselist', 
                get_string('pluginname', 'local_participant_image_upload'), 
                $CFG->wwwroot . '/local/participant_image_upload/courselist.php',
                'local/participant_image_upload:view'
            ));
}

// For Admin users.
if($hassiteconfig) {
    $ADMIN->add('localplugins', 
            new admin_externalpage('local_participant_image_upload', 
            get_string('pluginname', 'local_participant_image_upload'), 
            $CFG->wwwroot . '/local/participant_image_upload/courselist.php'
        ));
}
    