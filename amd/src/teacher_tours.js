// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
* JavaScript for handling tour creation via button click.
*
* @module block_teacher_tours/teacher_tours
* @copyright 2025 Your Name <your.email@example.com>
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define(['jquery', 'core/ajax', 'core/str', 'core/templates'],
    function ($, Ajax, Str, Templates) {
        /*  $step = new step();
         $step->set_tourid($this->tour->get_id());
         $step->set_title($title);
         $step->set_content($content, FORMAT_HTML);
         $step->set_targettype($targettype);
         $step->set_targetvalue($targetvalue);
 
         // Set any additional configuration options
         foreach ($config as $key => $value) {
             $step->set_config($key, $value);
         } */

        /*
        Placement Options
            placement (string)
        Position of the step popup relative to the target element
        Options: 'top', 'bottom', 'left', 'right'
        Example: 'placement' => 'top'
        Behavior Options
            orphan (boolean)
        Whether the step should be displayed even if the target element is not found
        Example: 'orphan' => true
            backdrop (boolean)
        Whether to show a backdrop behind the tour step (highlighting the target element)
        Example: 'backdrop' => true
            reflex (boolean)
        Whether clicking on the element will automatically advance to the next step
        Example: 'reflex' => true
        */

        /*
        $this->tour = new tour();
        $this->tour->set_name($name);
        $this->tour->set_description($description);
        $this->tour->set_pathmatch($pathmatch);
        $this->tour->set_enabled(tour::ENABLED);
        $this->tour->set_filter_values('cssselector', ['#block-course-audit']);
        $this->tour->set_sortorder(0);
        */

        //there has to be a json object that holds the information for the tour that is being send to an endpoint when the teacher hits save
        let tourObject = {
            steps: [
            /*                 
            template step object
            {
                                title: '',
                                content: '',
                                targettype: '',
                                targetvalue: '',
                                placement: '',
                                orphan: 'true',
                                backdrop: '',
                                reflex: 'false',
                                config: '',
            } */
            ],
            name: '',
            description: '',
            pathmatch: '',
            enabled: '',
            filter_values: '',
            sortorder: '',
        };

        //init the tourobject and starts the editor
        const init = function (courseid) {
            this.resetTourObject(courseid);
            this.startEditor();
         }

        //starts the picker at first
        const startEditor = function () {
            this.startPicker();
         }
        //TODO starts an overlay from which the user can select specific elements from the course
        //this is the first step in creating a step for the tour
        const startPicker = function () {
            this.showOverlay();

            this.startTextEditor();
        }

        //show the overlay
        const showOverlay = function () { 
            //This should highlight differents elements of the ui in different colors.
            //sections in light green
            //mods in blue
            //on clicking on a highlighted element, it should be saved to the current step and the text editor should be shown in the block
            highlightElements();
        }

        //the second step on creating a step for the tour is creating a text that should show when the step is active
        const startTextEditor = function () { }

        //send the tourObject to the endpoint
        const saveTour = function () { }

        //reset the tourObject
        const resetTourObject = function () {

         }

        //add a step to the tourObject
        const addStep = function () { 
            this.tourObject.steps.push({});
        }
        
        

    }
);