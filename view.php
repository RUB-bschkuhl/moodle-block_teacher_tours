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
 * Example block view page.
 *
 * @package   block_teacher_tours
 * @copyright 2025 Your Name <your.email@example.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';

// Get parameters.
$id = required_param('id', PARAM_INT); // Block instance ID.

// Security checks.
require_login();

// Get block instance and context.
$blockinstance = $DB->get_record('block_instances', ['id' => $id], '*', MUST_EXIST);
$context = context_block::instance($id);

// Check capability.
require_capability('block/example:view', $context);

// Set up page.
$PAGE->set_context($context);
$PAGE->set_url('/blocks/example/view.php', ['id' => $id]);
$PAGE->set_title(get_string('pluginname', 'block_teacher_tours'));
$PAGE->set_heading(get_string('pluginname', 'block_teacher_tours'));

// Output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_teacher_tours'));

echo html_writer::div(
    get_string('blockcontent', 'block_teacher_tours'),
    'block-example-view-content'
);

echo $OUTPUT->footer();
