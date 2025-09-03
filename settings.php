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
 * Settings for the teacher tour block
 *
 * @package    block_teacher_tours
 * @copyright  2025 Nikolai Jahreis <Nikolai.Jahreis@uni-bayreuth.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcolourpicker(
        'block_teacher_tours/modulehighlight',
        get_string('modulehighlight', 'block_teacher_tours'),
        get_string('modulehighlight_desc', 'block_teacher_tours'),
        '007bff'
    ));
    $settings->add(new admin_setting_configcolourpicker(
        'block_teacher_tours/sectionhighlight',
        get_string('sectionhighlight', 'block_teacher_tours'),
        get_string('sectionhighlight_desc', 'block_teacher_tours'),
        '28a745'
    ));
}
