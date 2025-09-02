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
            'tourid' => new external_value(PARAM_INT, 'Tour ID (0 for new)', VALUE_DEFAULT, 0),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Tour name'),
            'description' => new external_value(PARAM_TEXT, 'Tour description', VALUE_DEFAULT, ''),
            'steps' => new external_value(PARAM_RAW, 'JSON encoded steps array'),
            'enabled' => new external_value(PARAM_BOOL, 'Enabled status', VALUE_DEFAULT, true)
        ]);
    }

    /**
     * Save a tour (create or update).
     *
     * @param int $tourid         Tour ID (0 for new)
     * @param int $courseid       Course ID
     * @param string $name        Tour name
     * @param string $description Tour description
     * @param string $steps       JSON encoded steps
     * @param bool $enabled       Enabled status
     *
     * @return array Result
     */
    public static function save_tour(int $tourid, int $courseid, string $name, string $description, string $steps, bool $enabled): array {

        $params = self::validate_parameters(self::save_tour_parameters(), [
            'tourid' => $tourid,
            'courseid' => $courseid,
            'name' => $name,
            'description' => $description,
            'steps' => $steps,
            'enabled' => $enabled
        ]);

        // Check course context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        // Decode steps JSON.
        $stepsarray = json_decode($params['steps'], true);
        if ($stepsarray === null && $params['steps'] !== 'null' && $params['steps'] !== '[]') {
            throw new \invalid_parameter_exception('Invalid JSON in steps parameter');
        }
        $stepsarray = $stepsarray ?? [];

        if ($params['tourid'] > 0) {
            // Update existing tour.
            $success = manager::update_tour($params['tourid'], [
                'name' => $params['name'],
                'description' => $params['description'],
                'steps' => $stepsarray,
                'enabled' => $params['enabled']
            ]);
            
            return [
                'success' => $success,
                'tourid' => $params['tourid']
            ];
        } else {
            // Create new tour.
            $newtourid = manager::create_tour(
                $params['courseid'],
                $params['name'],
                $params['description'],
                $stepsarray
            );
            
            if ($newtourid && !$params['enabled']) {
                manager::set_tour_enabled($newtourid, false);
            }
            
            return [
                'success' => $newtourid > 0,
                'tourid' => $newtourid
            ];
        }
    }

    /**
     * Return definition for save_tour.
     *
     * @return external_single_structure
     */
    public static function save_tour_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'tourid' => new external_value(PARAM_INT, 'Tour ID')
        ]);
    }

    /**
     * Parameters for get_tour.
     *
     * @return external_function_parameters
     */
    public static function get_tour_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID')
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
            'tourid' => $tourid
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
            'enabled' => $tourdata['enabled']
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
            'enabled' => new external_value(PARAM_BOOL, 'Enabled status')
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
            'enabledonly' => new external_value(PARAM_BOOL, 'Only return enabled tours', VALUE_DEFAULT, false)
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
            'enabledonly' => $enabledonly
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
                'enabled' => $tourdata['enabled']
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
                'enabled' => new external_value(PARAM_BOOL, 'Enabled status')
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
            'tourid' => new external_value(PARAM_INT, 'Tour ID')
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
            'tourid' => $tourid
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
            'success' => new external_value(PARAM_BOOL, 'Success status')
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
            'steps' => new external_value(PARAM_RAW, 'JSON encoded steps array')
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
            'steps' => $steps
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
            'steps' => $stepsarray
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
            'success' => new external_value(PARAM_BOOL, 'Success status')
        ]);
    }

    /**
     * Parameters for start_tour.
     *
     * @return external_function_parameters
     */
    public static function start_tour_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID')
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
            'tourid' => $tourid
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
            'steps' => json_encode($tourdata['steps'])
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
            'steps' => new external_value(PARAM_RAW, 'JSON encoded steps')
        ]);
    }
}
