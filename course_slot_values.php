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
 * Alexa skill course slot values.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
global $DB, $PAGE, $OUTPUT;

require_login(0, false);
admin_externalpage_setup('alexacourseslotvalues');

$site = get_site();

$PAGE->set_url($CFG->wwwroot . '/local/alexaskill/course_slot_values.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($site->fullname);
$PAGE->set_heading($site->fullname);

$courses = $DB->get_records('course', array(), '', 'id, fullname');
$coursepreferrednames = get_string('alexaskill_courseslotvalues_desc', 'local_alexaskill');
$coursepreferrednames . '<p>';

foreach ($courses as $course) {
    $coursename = $course->fullname;
    $pattern = get_config('local_alexaskill', 'alexaskill_coursenameregex');

    // If no regular expression is configured, set to default.
    if ($pattern == '') {
        $pattern = '/(.*)/';
    }

    if (preg_match($pattern, $coursename, $coursenamearray)) {
        // Set course name to first capturing group of provided regular expression.
        $coursename = $coursenamearray[1];
    }
    $coursepreferrednames .= strtolower($coursename) . '<br />';
}
$coursepreferrednames .= '</p>';

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('alexaskill_courseslotvalues', 'local_alexaskill'));
echo $coursepreferrednames;
echo $OUTPUT->footer();