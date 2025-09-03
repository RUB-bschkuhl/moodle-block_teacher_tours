<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web service definitions for block_teacher_tours.
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_teacher_tours_save_tour' => [
        'classname'   => 'block_teacher_tours\external\tour_api',
        'methodname'  => 'save_tour',
        'classpath'   => '',
        'description' => 'Save a teacher tour (create or update)',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'block_teacher_tours_get_tour' => [
        'classname'   => 'block_teacher_tours\external\tour_api',
        'methodname'  => 'get_tour',
        'classpath'   => '',
        'description' => 'Get a tour by ID',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'block_teacher_tours_get_course_tours' => [
        'classname'   => 'block_teacher_tours\external\tour_api',
        'methodname'  => 'get_course_tours',
        'classpath'   => '',
        'description' => 'Get all tours for a course',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'block_teacher_tours_delete_tour' => [
        'classname'   => 'block_teacher_tours\external\tour_api',
        'methodname'  => 'delete_tour',
        'classpath'   => '',
        'description' => 'Delete a tour',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'block_teacher_tours_update_steps' => [
        'classname'   => 'block_teacher_tours\external\tour_api',
        'methodname'  => 'update_steps',
        'classpath'   => '',
        'description' => 'Update tour steps',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'block_teacher_tours_start_tour' => [
        'classname'   => 'block_teacher_tours\external\tour_api',
        'methodname'  => 'start_tour',
        'classpath'   => '',
        'description' => 'Start a tour for viewing',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/course:view',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'block_teacher_tours_toggle_tour_enabled' => [
        'classname'   => 'block_teacher_tours\external\tour_api',
        'methodname'  => 'toggle_tour_enabled',
        'classpath'   => '',
        'description' => 'Toggle tour enabled/disabled status',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ]
];

$services = [
    'Teacher Tours Service' => [
        'functions' => [
            'block_teacher_tours_save_tour',
            'block_teacher_tours_get_tour',
            'block_teacher_tours_get_course_tours',
            'block_teacher_tours_delete_tour',
            'block_teacher_tours_update_steps',
            'block_teacher_tours_start_tour',
            'block_teacher_tours_toggle_tour_enabled'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'teacher_tours_service'
    ]
];