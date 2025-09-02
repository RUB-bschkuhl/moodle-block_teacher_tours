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

define(['jquery'], //, 'core/ajax', 'core/str', 'core/templates'
    function ($) { //, Ajax, Str, Templates
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

        // JSON object that holds the information for the tour that is being sent to an endpoint when the teacher hits save
        let tourObject = {
            steps: [
                /*
                template step object
                {
                    title: '',
                    content: '',
                    targettype: '2',
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

        let currentStepObject = {};

        // Init the tourobject and starts the editor
        const init = function (courseid) {
            initializeEventBindings();
            resetTourObject(courseid);
        };

        // Starts the picker at first
        const startEditor = function () {
            // Show the tour editor interface
            $('#tour-editor').show();
            highlightElements();
        };

        // Store event handlers for specific removal
        const tourEventHandlers = {
            sectionClick: function(e) {
                e.preventDefault();
                e.stopPropagation();
                const section = e.currentTarget;
                currentStepObject = {
                    targettype: '2',
                    targetvalue: section.getAttribute('id'),
                    placement: 'right',
                    orphan: 'false',
                    backdrop: 'true',
                    reflex: 'false',
                };
                removeHighlighting();
                startTextEditor();
            },
            moduleClick: function(e) {
                e.preventDefault();
                e.stopPropagation();
                const mod = e.currentTarget;
                currentStepObject = {
                    targettype: '2',
                    targetvalue: mod.getAttribute('id'),
                    placement: 'right',
                    orphan: 'false',
                    backdrop: 'true',
                    reflex: 'false',
                };
                removeHighlighting();
                startTextEditor();
            }
        };

        // Highlight the elements
        const highlightElements = function () {
            // Highlight the sections in light green
            // Highlight the mods in blue
            document.querySelectorAll('[id^="section-"]').forEach(section => {
                section.classList.add('section-highlight');
                section.addEventListener('click', tourEventHandlers.sectionClick);
            });
            document.querySelectorAll('[id^="module-"]').forEach(mod => {
                mod.classList.add('module-highlight');
                mod.addEventListener('click', tourEventHandlers.moduleClick);
            });
        };

        // The second step on creating a step for the tour is creating a text that should show when the step is active
        const startTextEditor = function () {
            // TODO: Implement text editor functionality this should show a textarea with the current step object and a button to save the step
            $('#text-editor').show();
            $('#text-editor').html(currentStepObject.content);
        };

        const removeHighlighting = function () {
            // Remove the highlighting from the elements and their specific event listeners
            document.querySelectorAll('[id^="section-"]').forEach(section => {
                section.classList.remove('section-highlight');
                section.removeEventListener('click', tourEventHandlers.sectionClick);
            });
            document.querySelectorAll('[id^="module-"]').forEach(mod => {
                mod.classList.remove('module-highlight');
                mod.removeEventListener('click', tourEventHandlers.moduleClick);
            });
        };

        const saveStep = function () {
            // TODO: bind save event to the button in the text editor
            // TODO add currentStepObject to the tourObject.steps array
            addStep(currentStepObject);
            $('#text-editor').hide();
            $('#text-editor').html('');
            currentStepObject = {};
           // highlightElements();
        };

        // Send the tourObject to the endpoint
        const saveTour = function () {
            // TODO: Implement save functionality on save button click
            // TODO check if all required fields are filled
            // TODO send the tourObject to the endpoint via ajax
            console.log('Saving tour:', tourObject);
        };

        // Reset the tourObject
        const resetTourObject = function (courseid) {
            tourObject = {
                steps: [],
                name: '',
                description: '',
                pathmatch: '',
                enabled: '',
                filter_values: '',
                sortorder: '',
                courseid: courseid
            };
        };

        // Add a step to the tourObject
        const addStep = function (stepData) {
            tourObject.steps.push(stepData || {});
        };

        // Save the current step to the tour object
        const saveStep = function () {
            // Get values from the form
            const title = $('#step-title').val();
            const content = $('#step-content').val();
            const placement = $('#step-placement').val();
            const backdrop = $('#step-backdrop').prop('checked') ? 'true' : 'false';
            const orphan = $('#step-orphan').prop('checked') ? 'true' : 'false';
            const reflex = $('#step-reflex').prop('checked') ? 'true' : 'false';

            // Update the current step object with form values
            currentStepObject.title = title;
            currentStepObject.content = content;
            currentStepObject.placement = placement;
            currentStepObject.backdrop = backdrop;
            currentStepObject.orphan = orphan;
            currentStepObject.reflex = reflex;

            // Add the step to the tour
            addStep(currentStepObject);

            // Hide the text editor
            $('#text-editor').hide();

            // Reset for next step
            currentStepObject = {};

            // Show success message or update UI
            console.log('Step saved to tour:', tourObject);
        };

        // Initialize event bindings
        const initializeEventBindings = function () {
            // Bind click event to start tour creation button
            $(document).on('click', '#start-tour-creation', function (e) {
                e.preventDefault();
                startEditor();
            });

            // Bind click event to cancel tour creation button
            $(document).on('click', '#cancel-tour-creation', function (e) {
                e.preventDefault();
                $('#tour-editor').hide();
            });

            // Bind click event to save tour button
            $(document).on('click', '#save-tour', function (e) {
                e.preventDefault();
                saveTour();
            });

            // Bind click event to save step button
            $(document).on('click', '#save-step', function (e) {
                e.preventDefault();
                saveStep();
            });

            // Bind click event to cancel step edit button
            $(document).on('click', '#cancel-step-edit', function (e) {
                e.preventDefault();
                $('#text-editor').hide();
            });
        };

        // Return public API
        return {
            currentStepObject: currentStepObject,
            tourObject: tourObject,
            init: init,
            startEditor: startEditor,
            resetTourObject: resetTourObject,
            addStep: addStep,
            saveStep: saveStep,
            saveTour: saveTour,
            startTextEditor: startTextEditor,
            highlightElements: highlightElements,
            initializeEventBindings: initializeEventBindings
        };
    }
);