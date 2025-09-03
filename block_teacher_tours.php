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
 * Block example main class.
 *
 * @package   block_teacher_tours
 * @copyright 2025 Christin Wolters <christian.wolters@uni-luebeck.de>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Example block class
 */
class block_teacher_tours extends block_base {

    /**
     * Initialize the block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_teacher_tours');
    }

    /**
     * Get the block content
     *
     * @return stdClass The block content
     */
    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Check if user has permission to view this block.
        $context = context_block::instance($this->instance->id);

        //TODO get all tours for the current context instead of only course
        $tours = $this->get_all_tours_for_course($this->page->course->id);
        $customtours = $this->get_all_custom_tours_for_course($this->page->course->id);

        if (has_capability('block/teacher_tours:view', $context)) {
            $this->content->text = '[Content visible to teachers]';
        }

        if (!has_capability('block/teacher_tours:view', $context)) {
            return $this->content;
        }

        // Main block content.
        // Structure data for template.
        $templatedata = [];
        if (!empty($tours)) {
            $templatedata['existing_tours'] = [
                'tours' => array_values($tours),
            ];
        }
        if (!empty($customtours)) {
            $templatedata['existing_custom_tours'] = [
                'tours' => array_values($customtours),
            ];
        }

        $this->content->text = $OUTPUT->render_from_template('block_teacher_tours/main', $templatedata);

        return $this->content;
    }

    /**
     * Allow multiple instances of this block
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Get all tours for the current course
     *
     * @param int $courseid
     *
     * @return array
     */
    public function get_all_tours_for_course($courseid) {
        global $DB;
        $tours = $DB->get_records('tool_usertours_tours', ['pathmatch' => '/course/view.php?id=' . $courseid]);

        return $tours;
    }

    /**
     * Get all tours for the current course
     *
     * @param int $courseid
     *
     * @return array
     */
    public function get_all_custom_tours_for_course($courseid) {
        global $DB;
        $tours = $DB->get_records('block_teacher_tours', ['courseid' => $courseid]);

        return $tours;
    }

    /**
     * Add required JavaScript.
     *
     * @return void
     */
    public function get_required_javascript() {
        global $PAGE;

        $courseid = $this->page->course->id;
        $customtours = $this->get_all_custom_tours_for_course($courseid);
        if ($courseid != SITEID) {
            $PAGE->requires->js_call_amd('block_teacher_tours/teacher_tours', 'init', [$courseid, $customtours]);
        }

    }

    /**
     * Allow configuration of this block
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'course-view' => true,
        ];
    }

    /**
     * Allow hiding/showing of header
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

}
