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

namespace block_teacher_tours\local;

/**
 * Class hook_callbacks
 *
 * @package    block_teacher_tours
 * @copyright  2025 Christian Wolters <christian.wolters@uni-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Extends secondary navigation
     *
     * @param \core\hook\navigation\secondary_extend $hook
     */
    public static function secondary_extend(\core\hook\navigation\secondary_extend $hook): void {
        $secondarynav = $hook->get_secondaryview();
        $node = \navigation_node::create('[i18n-Todo] Teacher Blocks',
                    new \moodle_url('/blocks/teacher_tours/configure.php', ['view' => '[i18n-Todo] Teacher Blocks']),
                    \navigation_node::TYPE_CONTAINER,
                    null,
                    'teachertours-1');
        if (isloggedin()) {
            $secondarynav->add_node($node);
        }
    }
}
