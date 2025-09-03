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
        $courseid = $eventdata['courseid'] ?? null;

        if (empty($tourid)) {
            return;
        }

        // Check if this tour exists in block_teacher_tours table using courseid.
        if (!empty($courseid)) {
            $records = $DB->get_records('block_teacher_tours', ['courseid' => $courseid]);
        } else {
            // If no courseid in event, we need to get it from the tour itself.
            try {
                $tour = \tool_usertours\tour::instance($tourid);
                $tourconfig = $tour->get_config();

                // Check if tour is restricted to a specific course.
                if (!empty($tourconfig->courseid)) {
                    $courseid = $tourconfig->courseid;
                }

                if (empty($courseid)) {
                    return; // No course association found.
                }

                $records = $DB->get_records('block_teacher_tours', ['courseid' => $courseid]);
            } catch (\Exception $e) {
                return; // Tour might already be deleted or inaccessible.
            }
        }

        $foundintable = !empty($records);
        $recordtodelete = $foundintable ? reset($records) : null;

        // Only delete if the tour was found in our block_teacher_tours table.
        if ($foundintable) {
            try {
                // Get the tour instance.
                $tour = \tool_usertours\tour::instance($tourid);

                // Delete all steps first.
                $steps = $tour->get_steps();
                foreach ($steps as $step) {
                    $step->remove();
                }

                // Delete the tour.
                $tour->remove();
            } catch (\Exception $e) {
                // Log the error but don't throw it to avoid breaking the event flow.
                debugging('Error deleting teacher tour: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

}
