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
class block_teacher_tours extends block_base
{

    /**
     * Initialize the block
     */
    public function init()
    {
        $this->title = get_string('pluginname', 'block_teacher_tours');
    }

    /**
     * Get the block content
     *
     * @return stdClass The block content
     */
    public function get_content()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Check if user has permission to view this block.
        $context = context_block::instance($this->instance->id);
        if (!has_capability('block/teacher_tours:view', $context)) {
            return $this->content;
        }

        // Main block content.
        $this->content->text = html_writer::div(
            get_string('blockcontent', 'block_teacher_tours'),
            'block-example-content'
        );

        // Optional footer.
        $this->content->footer = html_writer::link(
            new moodle_url('/blocks/example/view.php', ['id' => $this->instance->id]),
            get_string('viewmore', 'block_teacher_tours')
        );

        return $this->content;
    }

    /**
     * Allow multiple instances of this block
     *
     * @return bool
     */
    public function instance_allow_multiple()
    {
        return true;
    }


    /**
     * Add required JavaScript.
     *
     * @return void
     */
    public function get_required_javascript()
    {
        global $PAGE;

        $courseid = $this->page->course->id;
        if ($courseid != SITEID) {
            $PAGE->requires->js_call_amd('block_teacher_tours/teacher_tours', 'init', [$courseid]);
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
    public function applicable_formats()
    {
        return [
            'course-view' => true,
        ];
    }

    /**
     * Allow hiding/showing of header
     *
     * @return bool
     */
    public function instance_allow_config()
    {
        return true;
    }
}
