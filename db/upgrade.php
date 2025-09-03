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
 * Upgrade script for block_teacher_tours.
 *
 * @package   block_teacher_tours
 * @copyright 2025 Your Name <your.email@example.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for block_teacher_tours.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_teacher_tours_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025090308) {
        // Define table block_teacher_tours to be created.
        $table = new xmldb_table('block_teacher_tours');

        // Adding fields to table block_teacher_tours.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('rawdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('placementid', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_teacher_tours.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_teacher_tours.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Teacher tours savepoint reached.
        upgrade_block_savepoint(true, 2025090308, 'teacher_tours');
    }

    return true;
}
