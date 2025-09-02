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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading
        'block_teacher_tours_settings_heading',
        get_string('settings_heading', 'block_teacher_tours')
    )

    $settings->add(new admin_setting_configcolourpicker(
        'block_teacher_tours/module-highlight',
        get_string('module-highlight', 'block_teacher_tours'),
        get_string('module-highlight_desc', 'block_teacher_tours'),
        '0000ff'
    ));
    $settings->add(new admin_setting_configcolourpicker(
        'block_teacher_tours/section-highlight',
        get_string('section-highlight', 'block_teacher_tours'),
        get_string('section-highlight_desc', 'block_teacher_tours')
        '90ee90'
    ));
}