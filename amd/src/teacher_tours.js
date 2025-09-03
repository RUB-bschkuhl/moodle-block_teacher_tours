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

define(['jquery', 'core/ajax', 'core/str'], // 'core/templates'
    function ($, Ajax, Str) {

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

        let stickyTarget = null;
        let sticky = false;

        let currentStepObject = {};

        // Init the tourobject and starts the editor
        const init = function (courseid, customTours) {
            initializeEventBindings();
            Object.values(customTours).forEach(tour => {
                console.log('tour', tour);
                setPlacements(tour.placementid, courseid); //replace courseid with tour.id
            });
            resetTourObject(courseid);
        };

        // Starts the picker at first
        const startEditor = function () {
            // Hide the create new tour button when editing
            $('#start-tour-creation').hide();
            $('#start-sticky-creation').hide();
            $('#step-creation').hide();
            // Show the tour editor interface
            $('#tour-editor').show();
            if (sticky) {
                highlightPlacements();
            } else {
                highlightElements();
            }
        };

        // Show current step indicator
        const showCurrentStepIndicator = function (elementText) {
            $('#current-step-element').text(elementText);
            $('#current-step-indicator').show();
        };

        // Show current step indicator
        const showTourStepsPreview = function () {
            $('.tour-preview').html('');
            tourObject.steps.forEach((step, index) => {
                $('.tour-preview')
                    .append('<div class="tour-step-preview" data-step-index="' + index + '">Step ' + (index + 1) +
                        ' <strong> ' + step.targetvalue + ':</strong> ' + step.title +
                        ' <i class="fa fa-pencil edit-step-icon" style="float: right; cursor: pointer; margin-left: 10px;">' +
                        '</i></div>');
            });
            $('.tour-preview').show();
        };

        // Hide current step indicator
        const hideCurrentStepIndicator = function () {
            $('#current-step-indicator').hide();
        };

        const resetCurrentStepObject = function () {
            currentStepObject = {};
        };

        // Handle tour toggle switches
        const handleTourToggle = function (tourId, enabled) {
            // console.log('Toggling tour', tourId, 'to', enabled ? 'enabled' : 'disabled');

            // Make AJAX call to backend to save the state
            Ajax.call([{
                methodname: 'block_teacher_tours_toggle_tour_enabled',
                args: {tourid: tourId, enabled: enabled}
            }])[0].done(function (response) {
                if (response.success) {
                    // Update the UI based on the actual state from server.
                    const tourCard = $(`[data-tour-id="${tourId}"]`);
                    const statusElement = tourCard.find('.tour-status');

                    if (response.enabled) {
                        Str.get_string('enabled', 'block_teacher_tours')
                            .then(function (enabledText) {
                                statusElement.html('<i class="fa fa-check-circle text-success"></i> ' + enabledText);
                            });
                        tourCard.find('.tour-toggle').prop('checked', true);
                    } else {
                        Str.get_string('disabled', 'block_teacher_tours')
                            .then(function (disabledText) {
                                statusElement.html('<i class="fa fa-times-circle text-muted"></i> ' + disabledText);
                            });
                        tourCard.find('.tour-toggle').prop('checked', false);
                    }
                    // console.log('Tour toggle successful');
                } else {
                    // Revert the toggle if the operation failed
                    // console.error('Failed to toggle tour');
                    const tourCard = $(`[data-tour-id="${tourId}"]`);
                    tourCard.find('.tour-toggle').prop('checked', !enabled);
                    alert('Failed to update tour status. Please try again.');
                }
            }).fail(function () {
                // console.error('Error toggling tour:', error);
                // Revert the toggle on error
                const tourCard = $(`[data-tour-id="${tourId}"]`);
                tourCard.find('.tour-toggle').prop('checked', !enabled);
                alert('Error updating tour status. Please try again.');
            });
        };

        // Handle tour editing
        const handleTourEdit = function (tourId) {
            // console.log('Editing tour', tourId);
            // TODO: Should open the form in the backend to edit the tour
            alert('Edit functionality will be implemented when backend is ready. Tour ID: ' + tourId);
        };

        // Handle tour deletion
        const handleTourDelete = function (tourId) {
            // console.log('Deleting tour', tourId);
            if (confirm('Are you sure you want to delete this tour? This action cannot be undone.')) {
                // Make AJAX call to backend to delete the tour
                Ajax.call([{
                    methodname: 'block_teacher_tours_delete_tour',
                    args: {tourid: tourId}
                }])[0].done(function (response) {
                    if (response.success) {
                        // Remove the card from UI with animation
                        $(`[data-tour-id="${tourId}"]`).fadeOut(300, function () {
                            $(this).remove();
                            // Check if no tours left
                            if ($('.tour-card').length === 0) {
                                $('.existing-tours').hide();
                            }
                        });
                    } else {
                        alert('Failed to delete tour. Please try again.');
                    }
                }).fail(function () {
                    alert('Error deleting tour. Please try again.');
                });
            }
        };

        // Store event handlers for specific removal
        const tourEventHandlers = {
            stickySelectClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const element = e.currentTarget;
                if (element.classList.contains('section-sticky-highlight')) {
                    element.classList.remove('section-sticky-highlight');
                    element.classList.add('section-sticky-button');
                    Str.get_string('selectplacement', 'block_teacher_tours').then(function (text) {
                        element.html(text);
                    });
                } else if (element.classList.contains('header-sticky-highlight')) {
                    element.classList.remove('header-sticky-highlight');
                    element.classList.add('header-sticky-button');
                    Str.get_string('selectplacement', 'block_teacher_tours').then(function (text) {
                        element.html(text);
                    });
                }
                //todo element get parent and get id
                let parent = element.parentElement;
                stickyTarget = parent.getAttribute('id');
                removeHighlighting();
                sticky = false;
                highlightElements();
            },
            stickyStartClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const element = e.currentTarget;
                let customtourid = element.dataset.customtourid;
                //create_tour_from_custom
                Ajax.call([{
                methodname: 'block_teacher_tours_create_tour_from_custom',
                args: { courseid: customtourid},//TODO replace courseid with custom tour id from plugin table
            }])[0].then(function (response) {
                console.log(response);
                //If ok reset the tourObject, if not show error
                if (!response && !response.status === 'ok') {
                    alert('Error saving tour: ' + (response.message || 'Unknown error'));
                }
                //reload the page
                window.location.reload();
            });
//TODO START TOUR WITH CUSTOM TOUR ID
            },
            sectionClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const section = e.currentTarget;
                currentStepObject = {
                    targettype: '0',
                    targetvalue: '#' + section.getAttribute('id'),
                    placement: 'right',
                    orphan: 'false',
                    backdrop: 'true',
                    reflex: 'false',
                };
                // Show current step indicator
                showCurrentStepIndicator('Section: ' + section.getAttribute('data-sectionname'));
                removeHighlighting();
                startTextEditor();
            },
            moduleClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const mod = e.currentTarget;
                currentStepObject = {
                    targettype: '0',
                    targetvalue: '#' + mod.getAttribute('id'),
                    placement: 'right',
                    orphan: 'false',
                    backdrop: 'true',
                    reflex: 'false',
                };
                // Show current step indicator
                // check if direct child of mod has data-activityname
                showCurrentStepIndicator('Module: ' + mod.childNodes[1].getAttribute('data-activityname'));

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

        const setPlacements = function (placementid, customtourid) {
            Str.get_string('touravailable', 'block_teacher_tours').then(function (text) {
                if (placementid.startsWith('section-')) {
                    document.querySelectorAll('[id="' + placementid + '"]').forEach(section => {
                        const button = document.createElement('button');
                        button.dataset.customtourid = customtourid;
                        button.className = 'btn btn-sm btn-outline-primary section-sticky-button';
                        button.textContent = text + ' ';
                        const icon = document.createElement('i');
                        icon.className = 'fa fa-question-circle';
                        button.append(icon);
                        section.style.position = 'relative';
                        section.prepend(button);
                        button.addEventListener('click', tourEventHandlers.stickyStartClick);
                    });
                } else if (placementid === 'page-header') {
                    document.querySelectorAll('[id="page-header"]').forEach(header => {
                        const button = document.createElement('button');
                        button.dataset.customtourid = customtourid;
                        button.className = 'btn btn-sm btn-outline-primary header-sticky-button';
                        button.textContent = text + ' ';
                        const icon = document.createElement('i');
                        icon.className = 'fa fa-question-circle';
                        button.append(icon);
                        header.style.position = 'relative';
                        header.prepend(button);
                        button.addEventListener('click', tourEventHandlers.stickyStartClick);
                    });
                }
            });
        };

        // Highlight the elements
        const highlightPlacements = function () {
            // TODO consider case of multiple tours on one element
            // "Highlight" the section placements and the course header placement
            // onclick remove pseudo elements and handle click in differenct function
            Str.get_string('selectplacement', 'block_teacher_tours').then(function (text) {
                document.querySelectorAll('[id^="section-"]').forEach(section => {
                    const button = document.createElement('button');
                    button.className = 'btn btn-sm btn-outline-primary section-sticky-highlight';
                    button.textContent = text + ' ';
                    const icon = document.createElement('i');
                    icon.className = 'fa fa-question-circle';
                    button.append(icon);
                    section.style.position = 'relative';
                    section.prepend(button);
                    button.addEventListener('click', tourEventHandlers.stickySelectClick);
                });
                document.querySelectorAll('[id="page-header"]').forEach(mod => {
                    const button = document.createElement('button');
                    button.className = 'btn btn-sm btn-outline-primary header-sticky-highlight';
                    button.textContent = text + ' ';
                    const icon = document.createElement('i');
                    icon.className = 'fa fa-question-circle';
                    button.append(icon);
                    mod.style.position = 'relative';
                    mod.prepend(button);
                    button.addEventListener('click', tourEventHandlers.stickySelectClick);
                });
            });
        };

        // The second step on creating a step for the tour is creating a text that should show when the step is active
        const startTextEditor = function (editIndex = null) {
            $('#text-editor').show();
            if (editIndex === null) {
                clearTextEditor();
            }
            $('#save-tour').hide();
            $('#step-title').focus();

            // Store edit index for later use
            $('#text-editor').data('edit-index', editIndex);
        };

        const clearTextEditor = function () {
            $('#step-title').val('');
            $('#step-content').val('');
            // $('#step-placement').val('right');
            // $('#step-backdrop').prop('checked', true);
            // $('#step-orphan').prop('checked', false);
            // $('#step-reflex').prop('checked', false);
        };

        const removeHighlighting = function () {
            if (sticky) {
                document.querySelectorAll('.section-sticky-highlight').forEach(section => {
                    section.remove();
                });
                document.querySelectorAll('.header-sticky-highlight').forEach(mod => {
                    mod.remove();
                });
            } else {
                // Remove the highlighting from the elements and their specific event listeners
                document.querySelectorAll('[id^="section-"]').forEach(section => {
                    section.classList.remove('section-highlight');
                    section.removeEventListener('click', tourEventHandlers.sectionClick);
                });
                document.querySelectorAll('[id^="module-"]').forEach(mod => {
                    mod.classList.remove('module-highlight');
                    mod.removeEventListener('click', tourEventHandlers.moduleClick);
                });
            }

        };

        // Send the tourObject to the endpoint
        const saveTour = function () {
            $('#save-tour').prop('disabled', true).text('Saving...');
            // Send the tourObject to the endpoint
            let argsObj = {};
            if (stickyTarget) {
                tourObject.placementid = stickyTarget;
                tourObject.custom = true;
                argsObj = { tour: tourObject };
            } else {
                tourObject.custom = false;
                argsObj = { tour: tourObject };
            }
            Ajax.call([{
                methodname: 'block_teacher_tours_save_tour',
                args: argsObj,
            }])[0].then(function (response) {
                console.log(response);
                //If ok reset the tourObject, if not show error
                if (!response && !response.status === 'ok') {
                    alert('Error saving tour: ' + (response.message || 'Unknown error'));
                }
                resetTourObject();

                $('#tour-editor').hide();
                $('#start-tour-creation').show();
                $('#start-sticky-creation').show();
                $('#step-creation').show();
                hideCurrentStepIndicator();
                clearTextEditor();
                removeHighlighting();
                $('.tour-preview').html('');
                $('.tour-preview').hide();
                Str.get_string('savetour', 'block_teacher_tours')
                    .then(function (text) {
                        $('#save-tour').prop('disabled', false).html('<i class="fa fa-save"></i> ' + text);
                    });
            });
        };

        // Reset the tourObject
        const resetTourObject = function (courseid) {
            stickyTarget = null;
            sticky = false;

            tourObject = {
                steps: [],
                name: 'tour for course ' + courseid,
                description: 'A tour for course ' + courseid,
                pathmatch: '/course/view.php?id=' + courseid,
                enabled: '',
                filter_values: '',
                sortorder: '',
            };
        };

        // Add a step to the tourObject
        const addStep = function (stepData) {
            tourObject.steps.push(stepData || {});
        };

        // Edit an existing step
        const editStep = function (stepIndex) {
            if (stepIndex < tourObject.steps.length) {
                const step = tourObject.steps[stepIndex];

                // Populate the form with existing step data
                $('#step-title').val(step.title);
                $('#step-content').val(step.content);

                // Show the text editor with edit mode
                startTextEditor(stepIndex);

                // Hide tour save button while editing
                $('#save-tour').hide();

                // Update current step object for any placement changes
                currentStepObject = {
                    targettype: step.targettype,
                    targetvalue: step.targetvalue,
                    placement: step.placement,
                    orphan: step.orphan,
                    backdrop: step.backdrop,
                    reflex: step.reflex
                };

                // Show current step indicator
                showCurrentStepIndicator('Editing: ' + step.targetvalue);
            }
        };

        // Save the current step to the tour object
        const saveStep = function () {
            // Get values from the form
            const title = $('#step-title').val();
            const content = $('#step-content').val();
            // TODO maybe
            // const placement = $('#step-placement').val();
            // const backdrop = $('#step-backdrop').prop('checked') ? 'true' : 'false';
            // const orphan = $('#step-orphan').prop('checked') ? 'true' : 'false';
            // const reflex = $('#step-reflex').prop('checked') ? 'true' : 'false';

            // Check if we're editing an existing step
            const editIndex = $('#text-editor').data('edit-index');

            if (editIndex !== null && editIndex !== undefined) {
                // Update existing step
                tourObject.steps[editIndex].title = title;
                tourObject.steps[editIndex].content = content;
                showTourStepsPreview();
                clearTextEditor();
                $('#text-editor').hide();
                $('#save-tour').show();
                $('#text-editor').data('edit-index', null);
            } else {
                // Update the current step object with form values
                currentStepObject.title = title;
                currentStepObject.content = content;
                // currentStepObject.placement = placement;
                // currentStepObject.backdrop = backdrop;
                // currentStepObject.orphan = orphan;
                // currentStepObject.reflex = reflex;

                // Add the step to the tour
                addStep(currentStepObject);
            }
        };

        // Initialize event bindings
        const initializeEventBindings = function () {
            // Bind click event to start tour creation button
            $(document).on('click', '#start-tour-creation', function (e) {
                e.preventDefault();
                startEditor();
            });

            $(document).on('click', '#start-sticky-creation', function (e) {
                e.preventDefault();
                sticky = true;
                startEditor();
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
                // Hide the text editor
                $('#text-editor').hide();
                $('#save-tour').show();
                // Reset for next step
                showTourStepsPreview();
                hideCurrentStepIndicator();
                resetCurrentStepObject();
                // Restart the editor to pick the next element
                startEditor();
            });

            // Bind click event to edit step icon
            $(document).on('click', '.edit-step-icon', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const stepIndex = $(this).parent().data('step-index');
                editStep(stepIndex);
            });

            // Bind click event to cancel step edit button
            $(document).on('click', '#cancel-step-edit', function (e) {
                e.preventDefault();
                hideCurrentStepIndicator();
                resetCurrentStepObject();
                $('#text-editor').hide();
                startEditor();
            });

            // Bind click event to cancel tour creation button
            $(document).on('click', '#cancel-tour-creation', function (e) {
                e.preventDefault();
                $('#tour-editor').hide();
                $('#start-sticky-creation').show();
                $('#start-tour-creation').show();
                hideCurrentStepIndicator();
                resetTourObject();
                $('.tour-preview').html('');
                $('.tour-preview').hide();

            });

            $(document).on('click', '#step-creation', function () {
                startEditor();
            });

            // Bind events for tour management
            $(document).on('change', '.tour-toggle', function () {
                const tourId = $(this).data('tour-id');
                const enabled = $(this).is(':checked');
                handleTourToggle(tourId, enabled);
            });

            $(document).on('click', '.edit-tour', function (e) {
                e.preventDefault();
                const tourId = $(this).data('tour-id');
                handleTourEdit(tourId);
            });

            $(document).on('click', '.delete-tour', function (e) {
                e.preventDefault();
                const tourId = $(this).data('tour-id');
                handleTourDelete(tourId);
            });
        };

        // Return public API
        return {
            sticky: sticky,
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
