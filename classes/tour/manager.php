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
 * Tour manager class.
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_tours\tour;

use tool_usertours\tour;
use tool_usertours\step;

/**
 * Manager class for handling teacher tour operations.
 *
 * This class provides the interface between the frontend and the core tour system,
 * handling CRUD operations for teacher-created tours in course contexts.
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string Prefix for teacher tours to distinguish them from system tours */
    const TOUR_PREFIX = 'teacher_tour_';

    /**
     * Create a new teacher tour for a course.
     *
     * @param int $courseid       Course ID
     * @param string $name        Tour name
     * @param string $description Tour description
     * @param array $steps        Array of step configurations
     *
     * @return int The newly created tour ID
     */
    public static function create_tour(int $courseid, string $name, string $description = '', array $steps = []): int {
        // Create tour with course-specific path match.
        $tour = new tour();
        $tour->set_name(self::TOUR_PREFIX . $name);
        $tour->set_description($description);
        $tour->set_pathmatch('/course/view.php?id=' . $courseid);
        $tour->set_enabled(tour::ENABLED);

        // Store course ID in config for filtering.
        $tour->set_config('courseid', $courseid);
        $tour->set_config('teacher_tour', true);

        // Set display options.
        $tour->set_config('displaystepnumbers', true);
        $tour->set_config('showtourwhen', tour::SHOW_TOUR_ON_EACH_PAGE_VISIT);

        // Save the tour.
        $tour->persist();

        // Add steps if provided.
        if (!empty($steps)) {
            foreach ($steps as $stepdata) {
                self::add_step_to_tour($tour, $stepdata);
            }
        }

        // Reset tour for all users so it's immediately visible.
        $tour->mark_major_change();

        return $tour->get_id();
    }

    /**
     * Update an existing tour.
     *
     * @param int $tourid Tour ID
     * @param array $data Data to update (name, description, steps, enabled)
     *
     * @return bool True if successful
     */
    public static function update_tour($tourid, $data): bool {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return false;
        }

        if (isset($data['name'])) {
            // Ensure teacher tour prefix is maintained.
            $name = $data['name'];
            if (!str_starts_with($name, self::TOUR_PREFIX)) {
                $name = self::TOUR_PREFIX . $name;
            }
            $tour->set_name($name);
        }

        if (isset($data['description'])) {
            $tour->set_description($data['description']);
        }

        if (isset($data['enabled'])) {
            $tour->set_enabled($data['enabled'] ? tour::ENABLED : tour::DISABLED);
        }

        $tour->persist();

        // Handle steps update.
        if (isset($data['steps'])) {
            // Remove existing steps.
            foreach ($tour->get_steps() as $step) {
                $step->remove();
            }

            // Add new steps.
            foreach ($data['steps'] as $stepdata) {
                self::add_step_to_tour($tour, $stepdata);
            }
        }

        return true;
    }

    /**
     * Delete a tour.
     *
     * @param int $tourid Tour ID
     *
     * @return bool True if successful
     */
    public static function delete_tour($tourid) {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return false;
        }

        $tour->remove();

        return true;
    }

    /**
     * Get a tour by ID.
     *
     * @param int $tourid Tour ID
     *
     * @return tour|null Tour object or null if not found
     */
    public static function get_tour($tourid) {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return null;
        }

        return $tour;
    }

    /**
     * Get all teacher tours for a course.
     *
     * @param int $courseid     Course ID
     * @param bool $enabledonly Only return enabled tours
     *
     * @return array Array of tour objects
     */
    public static function get_course_tours($courseid, $enabledonly = false) {
        global $DB;

        // Get all tours from the database.
        $sql = "SELECT * FROM {tool_usertours_tours} 
                WHERE name LIKE :prefix 
                AND pathmatch LIKE :pathmatch";

        $params = [
            'prefix' => self::TOUR_PREFIX . '%',
            'pathmatch' => '%/course/view.php?id=' . $courseid . '%',
        ];

        if ($enabledonly) {
            $sql .= " AND enabled = :enabled";
            $params['enabled'] = tour::ENABLED;
        }

        $sql .= " ORDER BY name ASC";

        $records = $DB->get_records_sql($sql, $params);

        $tours = [];
        foreach ($records as $record) {
            $tour = tour::instance($record->id);
            if ($tour && self::is_teacher_tour($tour)) {
                $tours[] = $tour;
            }
        }

        return $tours;
    }

    /**
     * Add a step to a tour.
     *
     * @param int $tourid     Tour ID
     * @param array $stepdata Step data containing:
     *                        - type: Type of element (activity, section, etc.)
     *                        - target: ID or selector of the target element
     *                        - title: Step title
     *                        - content: Step content/description
     *                        - placement: Tooltip placement (top, bottom, left, right)
     *
     * @return bool True if successful
     */
    public static function add_step($tourid, $stepdata) {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return false;
        }

        self::add_step_to_tour($tour, $stepdata);

        return true;
    }

    /**
     * Helper method to add a step to a tour object.
     *
     * @param tour $tour      Tour object
     * @param array $stepdata Step configuration
     *
     * @return step The created step
     */
    private static function add_step_to_tour($tour, $stepdata) {
        $step = new step();
        $step->set_tourid($tour->get_id());
        $step->set_title($stepdata['title'] ?? '');
        $step->set_content($stepdata['content'] ?? '');

        // Determine target type and value based on step type.
        if (isset($stepdata['type'])) {
            switch ($stepdata['type']) {
                case 'activity':
                    $step->set_targettype(step::TARGET_SELECTOR);
                    $step->set_targetvalue('#module-' . $stepdata['target']);
                    break;
                case 'section':
                    $step->set_targettype(step::TARGET_SELECTOR);
                    $step->set_targetvalue('#section-' . $stepdata['target']);
                    break;
                case 'block':
                    $step->set_targettype(step::TARGET_BLOCK);
                    $step->set_targetvalue($stepdata['target']);
                    break;
                default:
                    $step->set_targettype(step::TARGET_SELECTOR);
                    $step->set_targetvalue($stepdata['target']);
            }
        } else {
            $step->set_targettype(step::TARGET_SELECTOR);
            $step->set_targetvalue($stepdata['target'] ?? '');
        }

        // Set placement.
        if (isset($stepdata['placement'])) {
            $step->set_config('placement', $stepdata['placement']);
        }

        // Set additional configurations.
        $step->set_config('orphan', true); // Show even if target not found
        $step->set_config('backdrop', true); // Show backdrop

        $step->persist();

        return $step;
    }

    /**
     * Update a step in a tour.
     *
     * @param int $tourid     Tour ID
     * @param int $stepindex  Step index (0-based)
     * @param array $stepdata Updated step data
     *
     * @return bool True if successful
     */
    public static function update_step($tourid, $stepindex, $stepdata) {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return false;
        }

        $steps = $tour->get_steps();
        if (!isset($steps[$stepindex])) {
            return false;
        }

        $step = $steps[$stepindex];

        if (isset($stepdata['title'])) {
            $step->set_title($stepdata['title']);
        }
        if (isset($stepdata['content'])) {
            $step->set_content($stepdata['content']);
        }
        if (isset($stepdata['target'])) {
            $step->set_targetvalue($stepdata['target']);
        }
        if (isset($stepdata['placement'])) {
            $step->set_config('placement', $stepdata['placement']);
        }

        $step->persist();

        return true;
    }

    /**
     * Remove a step from a tour.
     *
     * @param int $tourid    Tour ID
     * @param int $stepindex Step index (0-based)
     *
     * @return bool True if successful
     */
    public static function remove_step($tourid, $stepindex) {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return false;
        }

        $steps = $tour->get_steps();
        if (!isset($steps[$stepindex])) {
            return false;
        }

        $steps[$stepindex]->remove();

        return true;
    }

    /**
     * Enable or disable a tour.
     *
     * @param int $tourid   Tour ID
     * @param bool $enabled Enabled status
     *
     * @return bool True if successful
     */
    public static function set_tour_enabled($tourid, $enabled) {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return false;
        }

        $tour->set_enabled($enabled ? tour::ENABLED : tour::DISABLED);
        $tour->persist();

        return true;
    }

    /**
     * Check if a tour is a teacher-created tour.
     *
     * @param tour $tour Tour object
     *
     * @return bool True if it's a teacher tour
     */
    private static function is_teacher_tour($tour) {
        // Check by name prefix or config flag
        return strpos($tour->get_name(), self::TOUR_PREFIX) === 0
            || $tour->get_config('teacher_tour') === true;
    }

    /**
     * Export tour data as JSON.
     *
     * @param int $tourid Tour ID
     *
     * @return string|false JSON string or false on failure
     */
    public static function export_tour($tourid) {
        $tour = tour::instance($tourid);
        if (!$tour || !self::is_teacher_tour($tour)) {
            return false;
        }

        $steps = [];
        foreach ($tour->get_steps() as $step) {
            $steps[] = [
                'title' => $step->get_title(),
                'content' => $step->get_content(),
                'targettype' => $step->get_targettype(),
                'targetvalue' => $step->get_targetvalue(),
                'placement' => $step->get_config('placement', 'bottom'),
            ];
        }

        $export = [
            'name' => str_replace(self::TOUR_PREFIX, '', $tour->get_name()),
            'description' => $tour->get_description(),
            'steps' => $steps,
        ];

        return json_encode($export, JSON_PRETTY_PRINT);
    }

    /**
     * Import tour data from JSON.
     *
     * @param int $courseid Course ID
     * @param string $json  JSON string containing tour data
     *
     * @return int|false New tour ID or false on failure
     */
    public static function import_tour($courseid, $json) {
        $data = json_decode($json, true);
        if (!$data || !isset($data['name']) || !isset($data['steps'])) {
            return false;
        }

        return self::create_tour(
            $courseid,
            $data['name'],
            $data['description'] ?? '',
            $data['steps']
        );
    }

    /**
     * Create a tour from frontend JSON data.
     *
     * This method accepts the exact JSON structure from frontend and stores it
     * in tool_usertours_tours and tool_usertours_steps tables.
     *
     * @param string $jsondata JSON data from frontend containing tour and steps
     *
     * @return array Result with success status and tour ID
     */
    public static function create_tour_from_json($jsondata) {
        global $DB;

        $data = json_decode($jsondata, true);
        if (!$data || !isset($data['tour']) || !isset($data['steps'])) {
            return ['success' => false, 'error' => 'Invalid JSON structure'];
        }

        $tourdata = $data['tour'];
        $stepsdata = $data['steps'];

        // Create tour record for tool_usertours_tours table
        $tour = new \stdClass();
        $tour->name = $tourdata['name'];
        $tour->description = $tourdata['description'] ?? '';
        $tour->pathmatch = $tourdata['pathmatch'] ?? '/course/view.php';
        $tour->enabled = $tourdata['enabled'] ?? 1;
        $tour->sortorder = $tourdata['sortorder'] ?? 0;
        $tour->configdata = json_encode($tourdata['configdata'] ?? [
            'placement' => 'bottom',
            'orphan' => false,
            'backdrop' => true,
            'reflex' => true,
        ]);

        // Insert tour into database.
        $tourid = $DB->insert_record('tool_usertours_tours', $tour);

        if (!$tourid) {
            return ['success' => false, 'error' => 'Failed to create tour'];
        }

        // Create step records for tool_usertours_steps table.
        foreach ($stepsdata as $index => $stepdata) {
            $step = new \stdClass();
            $step->tourid = $tourid;
            $step->title = $stepdata['title'] ?? '';
            $step->content = $stepdata['content'] ?? '';

            // Handle targettype and targetvalue based on frontend selection.
            // In Moodle 5.0, targettype values are:.
            // 0 = SELECTOR (CSS selector like #module-123).
            // 1 = BLOCK (block instance).
            // 2 = UNATTACHED (no specific target).
            $targetvalue = $stepdata['targetvalue'] ?? '';

            // Ensure targetvalue has proper format for CSS selectors
            if (!empty($targetvalue) && strpos($targetvalue, '#') !== 0) {
                // Add # prefix if missing
                $targetvalue = '#' . $targetvalue;
            }

            $step->targettype = $stepdata['targettype'] ?? 0; // Default to SELECTOR (0).
            $step->targetvalue = $targetvalue;
            $step->sortorder = $stepdata['sortorder'] ?? $index;
            $step->configdata = json_encode($stepdata['configdata'] ?? [
                'placement' => 'bottom',
                'width' => '300',
                'delay' => 0,
            ]);

            $DB->insert_record('tool_usertours_steps', $step);
        }

        // Reset tour for all users so it's immediately visible.
        $tour = tour::instance($tourid);
        $tour?->mark_major_change();

        return ['success' => true, 'tourid' => $tourid];
    }

    /**
     * Update a tour from frontend JSON data.
     *
     * @param int $tourid      Tour ID to update
     * @param string $jsondata JSON data from frontend containing tour and steps
     *
     * @return array Result with success status
     */
    public static function update_tour_from_json($tourid, $jsondata) {
        global $DB;

        $data = json_decode($jsondata, true);
        if (!$data || !isset($data['tour']) || !isset($data['steps'])) {
            return ['success' => false, 'error' => 'Invalid JSON structure'];
        }

        // Check if tour exists
        if (!$DB->record_exists('tool_usertours_tours', ['id' => $tourid])) {
            return ['success' => false, 'error' => 'Tour not found'];
        }

        $tourdata = $data['tour'];
        $stepsdata = $data['steps'];

        // Update tour record
        $tour = new \stdClass();
        $tour->id = $tourid;
        $tour->name = $tourdata['name'];
        $tour->description = $tourdata['description'] ?? '';
        $tour->pathmatch = $tourdata['pathmatch'] ?? '/course/view.php';
        $tour->enabled = $tourdata['enabled'] ?? 1;
        $tour->sortorder = $tourdata['sortorder'] ?? 0;
        $tour->configdata = json_encode($tourdata['configdata'] ?? [
            'placement' => 'bottom',
            'orphan' => false,
            'backdrop' => true,
            'reflex' => true,
        ]);

        $DB->update_record('tool_usertours_tours', $tour);

        // Delete existing steps
        $DB->delete_records('tool_usertours_steps', ['tourid' => $tourid]);

        // Insert new steps
        foreach ($stepsdata as $index => $stepdata) {
            $step = new \stdClass();
            $step->tourid = $tourid;
            $step->title = $stepdata['title'] ?? '';
            $step->content = $stepdata['content'] ?? '';

            // Handle targettype and targetvalue based on frontend selection
            // In Moodle 5.0, targettype values are:
            // 0 = SELECTOR (CSS selector like #module-123)
            // 1 = BLOCK (block instance)
            // 2 = UNATTACHED (no specific target)
            $targetvalue = $stepdata['targetvalue'] ?? '';

            // Ensure targetvalue has proper format for CSS selectors
            if (!empty($targetvalue) && strpos($targetvalue, '#') !== 0) {
                // Add # prefix if missing
                $targetvalue = '#' . $targetvalue;
            }

            $step->targettype = $stepdata['targettype'] ?? 0; // Default to SELECTOR (0)
            $step->targetvalue = $targetvalue;
            $step->sortorder = $stepdata['sortorder'] ?? $index;
            $step->configdata = json_encode($stepdata['configdata'] ?? [
                'placement' => 'bottom',
                'width' => '300',
                'delay' => 0,
            ]);

            $DB->insert_record('tool_usertours_steps', $step);
        }

        // Reset tour for all users so the updated tour is immediately visible.
        $tour = tour::instance($tourid);
        $tour?->mark_major_change();

        return ['success' => true, 'tourid' => $tourid];
    }

    /**
     * Delete a tour by ID.
     *
     * @param int $tourid Tour ID to delete
     *
     * @return array Result with success status
     */
    public static function delete_tour_by_id($tourid) {
        global $DB;

        // Check if tour exists
        if (!$DB->record_exists('tool_usertours_tours', ['id' => $tourid])) {
            return ['success' => false, 'error' => 'Tour not found'];
        }

        // Delete steps first
        $DB->delete_records('tool_usertours_steps', ['tourid' => $tourid]);

        // Delete tour
        $DB->delete_records('tool_usertours_tours', ['id' => $tourid]);

        return ['success' => true];
    }

    /**
     * Get tour data formatted for frontend.
     *
     * @param tour $tour Tour object
     *
     * @return array Formatted tour data
     */
    public static function format_tour_for_frontend($tour) {
        $steps = [];
        foreach ($tour->get_steps() as $step) {
            // Determine step type from target.
            $type = 'element';
            $target = $step->get_targetvalue();

            if (strpos($target, '#module-') === 0) {
                $type = 'activity';
                $target = str_replace('#module-', '', $target);
            } else if (strpos($target, '#section-') === 0) {
                $type = 'section';
                $target = str_replace('#section-', '', $target);
            } else if ($step->get_targettype() == step::TARGET_BLOCK) {
                $type = 'block';
            }

            $steps[] = [
                'type' => $type,
                'target' => $target,
                'title' => $step->get_title(),
                'content' => $step->get_content(),
                'placement' => $step->get_config('placement', 'bottom'),
            ];
        }

        return [
            'id' => $tour->get_id(),
            'courseid' => $tour->get_config('courseid'),
            'name' => str_replace(self::TOUR_PREFIX, '', $tour->get_name()),
            'description' => $tour->get_description(),
            'steps' => $steps,
            'enabled' => $tour->is_enabled(),
        ];
    }

}
