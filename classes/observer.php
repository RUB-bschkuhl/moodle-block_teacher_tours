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
 * Observer for block_teacher_tours
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_tours;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Triggered when a user tour is completed or exited.
     *
     * @param \tool_usertours\event\tour_ended $event The event data.
     *
     * @return void
     */
    public static function tour_ended(\tool_usertours\event\tour_ended $event): void {
        global $DB;

        $eventdata = $event->get_data();
        $tourid = $eventdata['objectid'] ?? null;

        if (empty($tourid)) {
            return;
        }

        try {
            $tour = \tool_usertours\tour::instance($tourid);
        } catch (\Exception $e) {
            // Tour might already be deleted or inaccessible.
            return;
        }

        if (!$tour) {
            return;
        }

        $tourconfig = $tour->get_config();

        if (empty($tourconfig) || empty($tourconfig->custom_tour_id)) {
            // Only sticky tours (created from custom tours) should be removed.
            return;
        }

        $customtourid = (int)$tourconfig->custom_tour_id;
        if ($customtourid <= 0 || !$DB->record_exists('block_teacher_tours', ['id' => $customtourid])) {
            // No matching custom tour entry found, nothing to do.
            return;
        }

        try {
            $steps = $tour->get_steps();
            foreach ($steps as $step) {
                $step->remove();
            }
            $tour->remove();
        } catch (\Exception $e) {
            // Log the error but don't throw it to avoid breaking the event flow.
            debugging('Error deleting sticky teacher tour: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

}
