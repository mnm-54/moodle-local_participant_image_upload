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
 * Web services description
 *
 * @package    local_participant_image_upload
 * @copyright  2022 munem
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_participant_image_upload_active_window' => array(
        'classname' => 'local_participant_image_upload_api',
        'methodname'  => 'active_window',
        'classpath'   => 'local/participant_image_upload/externallib.php',
        'description' => 'Start or stops attendance window',
        'type'        => 'write',
        'ajax' => true,
    )
);

$services = array(
    'local_participant_image_upload_services' => array(
        'functions' => array(
            'local_participant_image_upload_active_window'
        ),
        'restrictedusers' => 0,
        // into the administration
        'enabled' => 1,
        'shortname' =>  'local_piu_api',
    )
);
