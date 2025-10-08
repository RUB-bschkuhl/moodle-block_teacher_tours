# Teacher Tours Block
CC-BY-SA 4.0

## What are Teacher Tours?
The purpose of the Teacher Tours Block is to provide course instructors with the possibility to create user tours for their courses.

What is included in the block are options to create and delete user tours for the specific course, add and delete new steps to the tour (including a title and description) and create and delete a single entry point for a specific tour in the form of a button that can be placed in the header or at the top of a section.

Students can start the tour, once set as available by clicking the start button placed by the tour creator, whenever it is suitable for them.

It is intended to be used by teachers/lecturers.

## Usage
You can add the Teacher Tour to any course page.

With Teacher Tours teachers can:
- use a user tour editor within their course
- select items for the tour right from the screen without the need to find the right css-selector
- option to edit title and description of each step
- enable and disable tour availability
- create tour buttons to students can start them through a button and not automatically (no more distractions for students in the wrong moment)

Limititations:
- a tour can only refer to elements within the current window
- only sections and activities can be selected for the tour (no menu elements for now)

## Version support
This plugin has been developed to work on Moodle release 5.0.

## Development
The plugin was created by Bastian Schmidt-Kuhl, Nihaal Shaikh, Christan Wolters, Nikolai Jahreis and Julien Breunig during the DevCamp of MoodlemootDACH 2025 in Lübeck.

## Documentation
After adding the block to the course, a teacher can create a tour for the current page (course overview / section / activity) by clicking the "Create a tour" button in the block.
Upon clicking the "Add step" the selection mode is activated and the teacher can select the element that is supposed to be referenced in the tour step and then add a title and description for the little information window which will appear next to the element in the tour.
Afterwards more steps can be added by repeating those steps.
With the button "Add a tour start" the point of entry for the students can be created by selecting one of the appearing options (in the header or at top of sections).
Once completed the tour can be saved.

Technical details:
The tour is stored in a separate table. When a student clicks the button to start the tour is copied to the core table for user tours and activated with the according filters so it is shown to him. When the tour ends it is deleted again from the core table and only resides in the separate table until it is activated again.

## Installation
- Unzip and copy the "teacher_tours" folder into your Moodle's "blocks/" folder
- Visit the admin page to install plugin

Further installation instructions can be found on the "[Installing plugins](http://docs.moodle.org/en/Installing_contributed_modules_or_plugins)" Moodle documentation page.
