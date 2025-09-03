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
 * External API for teacher tours.
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_tours\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use block_teacher_tours\tour\manager;
use context_course;

/**
 * External API class for teacher tours.
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tour_api extends external_api {

    /**
     * Parameters for save_tour.
     *
     * @return external_function_parameters
     */
    public static function save_tour_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tour' => new external_single_structure([
                'steps' => new external_multiple_structure(
                    new external_single_structure([
                        'title' => new external_value(PARAM_TEXT, 'Step title'),
                        'content' => new external_value(PARAM_TEXT, 'Step content'),
                        'targettype' => new external_value(PARAM_TEXT, 'Target type'),
                        'targetvalue' => new external_value(PARAM_TEXT, 'Target value'),
                        'placement' => new external_value(PARAM_TEXT, 'Placement'),
                        'orphan' => new external_value(PARAM_TEXT, 'Orphan setting'),
                        'backdrop' => new external_value(PARAM_TEXT, 'Backdrop setting'),
                        'reflex' => new external_value(PARAM_TEXT, 'Reflex setting'),
                    ])
                ),
                'name' => new external_value(PARAM_TEXT, 'Tour name'),
                'description' => new external_value(PARAM_TEXT, 'Tour description'),
                'pathmatch' => new external_value(PARAM_TEXT, 'Path match'),
                'enabled' => new external_value(PARAM_TEXT, 'Enabled status'),
                'filter_values' => new external_value(PARAM_TEXT, 'Filter values'),
                'sortorder' => new external_value(PARAM_TEXT, 'Sort order'),
                'custom' => new external_value(PARAM_BOOL, 'Custom tour flag', VALUE_DEFAULT, false),
            ]),
        ]);
    }

    /**
     * Save a tour (create or update).
     *
     * @param array $tour Tour data from frontend
     *
     * @return array Result
     */
    public static function save_tour(array $tour): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::save_tour_parameters(), [
            'tour' => $tour,
        ]);

        $tourdata = $params['tour'];

        // Extract course ID from pathmatch
        preg_match('/id=(\d+)/', $tourdata['pathmatch'], $matches);
        $courseid = isset($matches[1]) ? (int) $matches[1] : 0;

        if (!$courseid) {
            throw new \invalid_parameter_exception('Invalid course ID in pathmatch');
        }

        // Check course context.
        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        // Prepare tour data for database
        $toursave = new \stdClass();
        $toursave->name = $tourdata['name'];
        $toursave->description = $tourdata['description'];
        $toursave->pathmatch = $tourdata['pathmatch'];
        // Enable by default if not specified or empty
        $toursave->enabled =
            (empty($tourdata['enabled']) || $tourdata['enabled'] === 'true' || $tourdata['enabled'] === '1') ? 1 : 0;
        $toursave->sortorder = is_numeric($tourdata['sortorder']) ? (int) $tourdata['sortorder'] : 0;

        // Check if this is a custom tour
        $iscustom = isset($tourdata['custom']) && $tourdata['custom'];
        $iscustom = true;

        // Prepare configdata.
        $configdata = [
            'courseid' => $courseid,
            'teacher_tour' => true,
        ];
        $toursave->configdata = json_encode($configdata);

        // Handle differently based on custom flag.
        if ($iscustom) {
            // For custom tours, store only in block_teacher_tours table,
            // Include steps in the rawdata.
            $toursave->steps = $tourdata['steps'];

            $customsave = new \stdClass();
            $customsave->rawdata = json_encode($toursave);
            $customsave->courseid = $courseid;
            $tourid = $DB->insert_record('block_teacher_tours', $customsave);

            if (!$tourid) {
                return [
                    'success' => false,
                    'tourid' => 0,
                    'message' => 'Failed to create custom tour',
                ];
            }

            return [
                'success' => true,
                'tourid' => $tourid,
                'message' => 'Custom tour created successfully',
            ];
        }

        // Insert tour into database
        $tourid = $DB->insert_record('tool_usertours_tours', $toursave);

        if (!$tourid) {
            return [
                'success' => false,
                'tourid' => 0,
                'message' => 'Failed to create tour',
            ];
        }

        // Insert steps
        foreach ($tourdata['steps'] as $index => $step) {
            $stepsave = new \stdClass();
            $stepsave->tourid = $tourid;
            $stepsave->title = $step['title'] ?? '';
            $stepsave->content = $step['content'] ?? '';

            // Convert targettype from string to int (frontend sends "2" but we want 0 for SELECTOR)
            // targettype: 0 = SELECTOR, 1 = BLOCK, 2 = UNATTACHED
            $stepsave->targettype = 0; // Always use 0 for CSS selectors

            // Ensure targetvalue has proper format
            $targetvalue = $step['targetvalue'] ?? '';
            if (!empty($targetvalue) && !str_starts_with($targetvalue, '#')) {
                $targetvalue = '#' . $targetvalue;
            }
            $stepsave->targetvalue = $targetvalue;

            $stepsave->sortorder = $index;

            // Prepare step configdata
            $stepconfig = [
                'placement' => $step['placement'] ?? 'bottom',
                'orphan' => ($step['orphan'] === 'true'),
                'backdrop' => ($step['backdrop'] === 'true'),
                'reflex' => ($step['reflex'] === 'true'),
            ];
            $stepsave->configdata = json_encode($stepconfig);

            $DB->insert_record('tool_usertours_steps', $stepsave);
        }

        // Reset tour for all users so it's immediately visible.
        // Use Moodle's built-in method to mark a major change which resets tour state for all users.
        require_once($CFG->dirroot . '/admin/tool/usertours/classes/tour.php');
        $tour = \tool_usertours\tour::instance($tourid);
        $tour?->mark_major_change();

        return [
            'success' => true,
            'tourid' => $tourid,
            'message' => 'Tour created successfully',
        ];
    }

    /**
     * Return definition for save_tour.
     *
     * @return external_single_structure
     */
    public static function save_tour_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'tourid' => new external_value(PARAM_INT, 'Tour ID'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }

    /**
     * Parameters for get_tour.
     *
     * @return external_function_parameters
     */
    public static function get_tour_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID'),
        ]);
    }

    /**
     * Get a tour by ID.
     *
     * @param int $tourid Tour ID
     *
     * @return array Tour data
     */
    public static function get_tour(int $tourid): array {

        $params = self::validate_parameters(self::get_tour_parameters(), [
            'tourid' => $tourid,
        ]);

        $tour = manager::get_tour($params['tourid']);
        if (!$tour) {
            throw new \moodle_exception('tournotfound', 'block_teacher_tours');
        }

        // Get formatted data for frontend
        $tourdata = manager::format_tour_for_frontend($tour);

        // Check course context.
        $context = context_course::instance($tourdata['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        return [
            'id' => $tourdata['id'],
            'courseid' => $tourdata['courseid'],
            'name' => $tourdata['name'],
            'description' => $tourdata['description'],
            'steps' => json_encode($tourdata['steps']),
            'enabled' => $tourdata['enabled'],
        ];
    }

    /**
     * Return definition for get_tour.
     *
     * @return external_single_structure
     */
    public static function get_tour_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Tour ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Tour name'),
            'description' => new external_value(PARAM_TEXT, 'Tour description'),
            'steps' => new external_value(PARAM_RAW, 'JSON encoded steps'),
            'enabled' => new external_value(PARAM_BOOL, 'Enabled status'),
        ]);
    }

    /**
     * Parameters for get_course_tours.
     *
     * @return external_function_parameters
     */
    public static function get_course_tours_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'enabledonly' => new external_value(PARAM_BOOL, 'Only return enabled tours', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Get all tours for a course.
     *
     * @param int $courseid     Course ID
     * @param bool $enabledonly Only return enabled tours
     *
     * @return array Tours data
     */
    public static function get_course_tours(int $courseid, bool $enabledonly = false): array {

        $params = self::validate_parameters(self::get_course_tours_parameters(), [
            'courseid' => $courseid,
            'enabledonly' => $enabledonly,
        ]);

        // Check course context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        $tours = manager::get_course_tours($params['courseid'], $params['enabledonly']);

        $result = [];
        foreach ($tours as $tour) {
            $tourdata = manager::format_tour_for_frontend($tour);
            $result[] = [
                'id' => $tourdata['id'],
                'courseid' => $tourdata['courseid'],
                'name' => $tourdata['name'],
                'description' => $tourdata['description'],
                'steps' => json_encode($tourdata['steps']),
                'enabled' => $tourdata['enabled'],
            ];
        }

        return $result;
    }

    /**
     * Return definition for get_course_tours.
     *
     * @return external_multiple_structure
     */
    public static function get_course_tours_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Tour ID'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'name' => new external_value(PARAM_TEXT, 'Tour name'),
                'description' => new external_value(PARAM_TEXT, 'Tour description'),
                'steps' => new external_value(PARAM_RAW, 'JSON encoded steps'),
                'enabled' => new external_value(PARAM_BOOL, 'Enabled status'),
            ])
        );
    }

    /**
     * Parameters for delete_tour.
     *
     * @return external_function_parameters
     */
    public static function delete_tour_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID'),
        ]);
    }

    /**
     * Delete a tour.
     *
     * @param int $tourid Tour ID
     *
     * @return array Result
     */
    public static function delete_tour(int $tourid): array {
        global $DB;

        $params = self::validate_parameters(self::delete_tour_parameters(), [
            'tourid' => $tourid,
        ]);

        $tour = manager::get_tour($params['tourid']);
        if (!$tour) {
            throw new \moodle_exception('tournotfound', 'block_teacher_tours');
        }

        // Get tour data for context check
        $tourdata = manager::format_tour_for_frontend($tour);

        // Check course context.
        $context = context_course::instance($tourdata['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        $success = manager::delete_tour($params['tourid']);

        return ['success' => $success];
    }

    /**
     * Return definition for delete_tour.
     *
     * @return external_single_structure
     */
    public static function delete_tour_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }

    /**
     * Parameters for update_steps.
     *
     * @return external_function_parameters
     */
    public static function update_steps_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID'),
            'steps' => new external_value(PARAM_RAW, 'JSON encoded steps array'),
        ]);
    }

    /**
     * Update tour steps.
     *
     * @param int $tourid   Tour ID
     * @param string $steps JSON encoded steps
     *
     * @return array Result
     */
    public static function update_steps(int $tourid, string $steps): array {

        $params = self::validate_parameters(self::update_steps_parameters(), [
            'tourid' => $tourid,
            'steps' => $steps,
        ]);

        $tour = manager::get_tour($params['tourid']);
        if (!$tour) {
            throw new \moodle_exception('tournotfound', 'block_teacher_tours');
        }

        // Get tour data for context check
        $tourdata = manager::format_tour_for_frontend($tour);

        // Check course context.
        $context = context_course::instance($tourdata['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        // Decode steps JSON.
        $stepsarray = json_decode($params['steps'], true);
        if ($stepsarray === null && $params['steps'] !== 'null' && $params['steps'] !== '[]') {
            throw new \invalid_parameter_exception('Invalid JSON in steps parameter');
        }
        $stepsarray = $stepsarray ?? [];

        $success = manager::update_tour($params['tourid'], [
            'steps' => $stepsarray,
        ]);

        return ['success' => $success];
    }

    /**
     * Return definition for update_steps.
     *
     * @return external_single_structure
     */
    public static function update_steps_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }

    /**
     * Parameters for start_tour.
     *
     * @return external_function_parameters
     */
    public static function start_tour_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID'),
        ]);
    }

    /**
     * Start a tour for the current user.
     *
     * @param int $tourid Tour ID
     *
     * @return array Tour data with rendered steps
     */
    public static function start_tour(int $tourid): array {

        $params = self::validate_parameters(self::start_tour_parameters(), [
            'tourid' => $tourid,
        ]);

        $tour = manager::get_tour($params['tourid']);
        if (!$tour) {
            throw new \moodle_exception('tournotfound', 'block_teacher_tours');
        }

        // Get tour data
        $tourdata = manager::format_tour_for_frontend($tour);

        // Check course context - for starting a tour, we might want different permissions
        $context = context_course::instance($tourdata['courseid']);
        self::validate_context($context);

        // Teachers and students can view tours
        require_capability('moodle/course:view', $context);

        // Mark tour as started for this user (optional - depends on requirements)
        // This would use the core tour completion tracking

        return [
            'id' => $tourdata['id'],
            'name' => $tourdata['name'],
            'description' => $tourdata['description'],
            'steps' => json_encode($tourdata['steps']),
        ];
    }

    /**
     * Return definition for start_tour.
     *
     * @return external_single_structure
     */
    public static function start_tour_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Tour ID'),
            'name' => new external_value(PARAM_TEXT, 'Tour name'),
            'description' => new external_value(PARAM_TEXT, 'Tour description'),
            'steps' => new external_value(PARAM_RAW, 'JSON encoded steps'),
        ]);
    }

    /**
     * Parameters for toggle_tour_enabled.
     *
     * @return external_function_parameters
     */
    public static function toggle_tour_enabled_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID'),
            'enabled' => new external_value(PARAM_BOOL, 'Enabled status'),
        ]);
    }

    /**
     * Toggle tour enabled/disabled status.
     *
     * @param int $tourid   Tour ID
     * @param bool $enabled Whether tour should be enabled
     *
     * @return array Result with success status
     */
    public static function toggle_tour_enabled(int $tourid, bool $enabled): array {
        $params = self::validate_parameters(self::toggle_tour_enabled_parameters(), [
            'tourid' => $tourid,
            'enabled' => $enabled,
        ]);

        // Get the tour to check permissions
        $tour = manager::get_tour($params['tourid']);
        if (!$tour) {
            throw new \moodle_exception('tournotfound', 'block_teacher_tours');
        }

        // Get tour data for context check
        $tourdata = manager::format_tour_for_frontend($tour);

        // Check course context.
        $context = context_course::instance($tourdata['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        // Toggle the enabled status
        $success = manager::set_tour_enabled($params['tourid'], $params['enabled']);

        return [
            'success' => $success,
            'enabled' => $params['enabled'],
        ];
    }

    /**
     * Return definition for toggle_tour_enabled.
     *
     * @return external_single_structure
     */
    public static function toggle_tour_enabled_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'enabled' => new external_value(PARAM_BOOL, 'New enabled status'),
        ]);
    }

    /**
     * Parameters for create_tour_from_custom.
     *
     * @return external_function_parameters
     */
    public static function create_tour_from_custom_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Create a tour in Moodle core tables from the first custom tour for a course.
     * This fetches the first custom tour from block_teacher_tours table and creates
     * it in tool_usertours_tours and tool_usertours_steps tables.
     *
     * @param int $courseid Course ID
     *
     * @return array Result with success status and tour ID
     */
    public static function create_tour_from_custom(int $courseid): array {
        global $DB, $CFG, $USER;

        $params = self::validate_parameters(self::create_tour_from_custom_parameters(), [
            'courseid' => $courseid,
        ]);

        // Check course context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        // Get the first custom tour for this course
        $customtour = $DB->get_record('block_teacher_tours', ['courseid' => $params['courseid']], '*', IGNORE_MULTIPLE);

        if (!$customtour) {
            return [
                'success' => false,
                'tourid' => 0,
                'message' => 'No custom tour found for this course',
            ];
        }

        // Decode the raw data
        $tourdata = json_decode($customtour->rawdata, true);
        if (!$tourdata) {
            return [
                'success' => false,
                'tourid' => 0,
                'message' => 'Invalid tour data in custom tour',
            ];
        }

        // Check if the tour has steps
        if (empty($tourdata['steps'])) {
            return [
                'success' => false,
                'tourid' => 0,
                'message' => 'Custom tour has no steps defined',
            ];
        }

        // Prepare tour data for core table
        $coretour = new \stdClass();
        $coretour->name = $tourdata['name'] ?? 'Teacher Tour';
        $coretour->description = $tourdata['description'] ?? '';
        $coretour->pathmatch = $tourdata['pathmatch'] ?? '/course/view.php%id=' . $params['courseid'];
        $coretour->enabled = $tourdata['enabled'] ?? 1;
        $coretour->sortorder = isset($tourdata['sortorder']) ? (int) $tourdata['sortorder'] : 0;

        // Prepare configdata with cssselector filter
        $configdata = [
            'courseid' => $params['courseid'],
            'teacher_tour' => true,
            'custom_tour_id' => $customtour->id, // Reference to original custom tour
            'filtervalues' => [
                'cssselector' => ["#nav-notification-popover-container[data-userid=\"{$USER->id}\"]"]
            ]
        ];
        $coretour->configdata = json_encode($configdata);

        // Insert tour into core table
        $tourid = $DB->insert_record('tool_usertours_tours', $coretour);

        if (!$tourid) {
            return [
                'success' => false,
                'tourid' => 0,
                'message' => 'Failed to create tour in core tables',
            ];
        }

        // Insert steps into core table
        $stepsinserted = 0;
        foreach ($tourdata['steps'] as $index => $step) {
            $corestep = new \stdClass();
            $corestep->tourid = $tourid;
            $corestep->title = $step['title'] ?? '';
            $corestep->content = $step['content'] ?? '';

            // Handle target type and value
            if (isset($step['targettype'])) {
                // Convert string to int if needed
                if ($step['targettype'] === "2") {
                    $corestep->targettype = 2; // UNATTACHED
                } else {
                    $corestep->targettype = 0; // SELECTOR (default)
                }
            } else {
                $corestep->targettype = 0; // Default to SELECTOR
            }

            // Handle target value for CSS selectors
            $targetvalue = $step['targetvalue'] ?? '';
            if ($corestep->targettype === 0 && !empty($targetvalue)) {
                // Ensure proper CSS selector format
                if (!str_starts_with($targetvalue, '#') && !str_starts_with($targetvalue, '.')) {
                    $targetvalue = '#' . $targetvalue;
                }
            }
            $corestep->targetvalue = $targetvalue;

            $corestep->sortorder = $index;

            // Prepare step configdata
            $stepconfig = [
                'placement' => $step['placement'] ?? 'bottom',
                'orphan' => isset($step['orphan']) && ($step['orphan'] === true || $step['orphan'] === 'true'),
                'backdrop' => isset($step['backdrop']) && ($step['backdrop'] === true || $step['backdrop'] === 'true'),
                'reflex' => isset($step['reflex']) && ($step['reflex'] === true || $step['reflex'] === 'true'),
            ];
            $corestep->configdata = json_encode($stepconfig);

            if ($DB->insert_record('tool_usertours_steps', $corestep)) {
                $stepsinserted++;
            }
        }

        // Reset tour for all users so it's immediately visible
        require_once($CFG->dirroot . '/admin/tool/usertours/classes/tour.php');
        $tour = \tool_usertours\tour::instance($tourid);
        $tour?->mark_major_change();

        return [
            'success' => true,
            'tourid' => $tourid,
            'message' => "Tour created successfully with {$stepsinserted} steps",
            'reload' => true,  // Signal the frontend to reload the page
        ];
    }

    /**
     * Return definition for create_tour_from_custom.
     *
     * @return external_single_structure
     */
    public static function create_tour_from_custom_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'tourid' => new external_value(PARAM_INT, 'Created tour ID'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'reload' => new external_value(PARAM_BOOL, 'Whether to reload the page', VALUE_OPTIONAL),
        ]);
    }

}
