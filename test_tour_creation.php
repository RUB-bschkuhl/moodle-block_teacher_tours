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
 * Test script for creating teacher tours from JSON.
 *
 * @package    block_teacher_tours
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use block_teacher_tours\tour\manager;

// Require login
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/teacher_tours/test_tour_creation.php');
$PAGE->set_title('Test Tour Creation');
$PAGE->set_heading('Test Tour Creation');

echo $OUTPUT->header();

// Example 1: Hardcoded JSON for creating a new tour
$jsondata = '{
    "tour": {
        "name": "Course Introduction Tour",
        "description": "A guided tour for new students in this course",
        "pathmatch": "/course/view.php?id=2",
        "enabled": 1,
        "sortorder": 0,
        "configdata": {
            "placement": "bottom",
            "orphan": false,
            "backdrop": true,
            "reflex": true
        }
    },
    "steps": [
        {
            "tourid": null,
            "title": "Welcome to the course",
            "content": "This is your course homepage where you can access all materials",
            "contentformat": 1,
            "targettype": 0,
            "targetvalue": "#section-0",
            "sortorder": 0,
            "configdata": {
                "placement": "bottom",
                "width": "300",
                "delay": 0
            }
        },
        {
            "tourid": null,
            "title": "Assignment Section",
            "content": "Submit your assignments here",
            "contentformat": 1,
            "targettype": 0,
            "targetvalue": "#module-5",
            "sortorder": 1,
            "configdata": {
                "placement": "right",
                "width": "300",
                "delay": 0
            }
        },
        {
            "tourid": null,
            "title": "Forum Activity",
            "content": "Participate in discussions with your classmates",
            "contentformat": 1,
            "targettype": 0,
            "targetvalue": "#module-7",
            "sortorder": 2,
            "configdata": {
                "placement": "left",
                "width": "300",
                "delay": 0
            }
        }
    ]
}';

echo html_writer::tag('h2', 'Creating a New Tour');
echo html_writer::tag('pre', htmlspecialchars($jsondata, ENT_QUOTES, 'UTF-8'));

// Create the tour
$result = manager::create_tour_from_json($jsondata);

if ($result['success']) {
    echo $OUTPUT->notification('Tour created successfully! Tour ID: ' . $result['tourid'], 'success');
    
    // Example 2: Update the tour we just created
    $updatejson = '{
        "tour": {
            "name": "Updated Course Tour",
            "description": "This tour has been updated with new content",
            "pathmatch": "/course/view.php?id=2",
            "enabled": 1,
            "sortorder": 0,
            "configdata": {
                "placement": "top",
                "orphan": true,
                "backdrop": true,
                "reflex": false
            }
        },
        "steps": [
            {
                "tourid": null,
                "title": "Updated Welcome",
                "content": "Welcome to the updated course tour!",
                "contentformat": 1,
                "targettype": 0,
                "targetvalue": "#section-0",
                "sortorder": 0,
                "configdata": {
                    "placement": "top",
                    "width": "400",
                    "delay": 500
                }
            },
            {
                "tourid": null,
                "title": "New Resource Section",
                "content": "Access course resources and materials here",
                "contentformat": 1,
                "targettype": 0,
                "targetvalue": "#module-10",
                "sortorder": 1,
                "configdata": {
                    "placement": "bottom",
                    "width": "350",
                    "delay": 0
                }
            }
        ]
    }';
    
    echo html_writer::tag('h2', 'Updating the Tour');
    echo html_writer::tag('pre', htmlspecialchars($updatejson, ENT_QUOTES, 'UTF-8'));
    
    $updateresult = manager::update_tour_from_json($result['tourid'], $updatejson);
    
    if ($updateresult['success']) {
        echo $OUTPUT->notification('Tour updated successfully!', 'success');
    } else {
        echo $OUTPUT->notification('Failed to update tour: ' . ($updateresult['error'] ?? 'Unknown error'), 'error');
    }
    
} else {
    echo $OUTPUT->notification('Failed to create tour: ' . ($result['error'] ?? 'Unknown error'), 'error');
}

// Example 3: Delete tour (commented out to preserve the created tour)
echo html_writer::tag('h2', 'Delete Tour (Example Code)');
echo html_writer::tag('pre', '// To delete a tour:
// $deleteresult = manager::delete_tour_by_id($tourid);
// if ($deleteresult[\'success\']) {
//     echo "Tour deleted successfully!";
// }');

// Display link to view tours in database
$dburl = new moodle_url('/admin/tool/adminer/', ['db' => 'tool_usertours_tours']);
echo html_writer::tag('p', 
    html_writer::link($dburl, 'View tours in database', ['class' => 'btn btn-primary'])
);

echo $OUTPUT->footer();
