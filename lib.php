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
 * API of local_assessment_archive.
 *
 * @package    local_assessment_archive
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Add setting to quiz/assignment settings form.
 *
 * @param $formwrapper
 * @param MoodleQuickForm $mform
 * @throws coding_exception
 * @throws dml_exception
 */
function local_assessment_archive_coursemodule_standard_elements($formwrapper, $mform)  {
    global $DB;
    $wrapper = $formwrapper->get_current();
    if (!in_array($wrapper->modulename, ["quiz", "assign"])) {
        return;
    }

    $enabled = false;
    if (!empty($wrapper->coursemodule)
            && ($record = $DB->get_record('local_assessment_archive', ['cmid' => $wrapper->coursemodule]))) {
        $enabled = $record->archive;
    }

    $checkbox = $mform->createElement('advcheckbox', 'local_assessment_archiving_enabled',
        get_string('form_assessment_archiving', 'local_assessment_archive'),
        get_string('form_assessment_archiving_after', 'local_assessment_archive'));
    $mform->insertElementBefore($checkbox, 'introeditor');
    $mform->setDefault('local_assessment_archiving_enabled', $enabled);

    // If local_assessment_methods is installed, the selected method may indicate whether to archive or not.
    if (class_exists('\local_assessment_methods\helper')) {
        $config = get_config('local_assessment_archive');
        // Add empty string in order to also hide on the 'please select' option.
        $hideifmethods = array_merge([''], explode(',', $config->methods_archive), explode(',', $config->methods_dont_archive));
        $mform->hideIf('local_assessment_archiving_enabled', 'assessment_method', 'in', $hideifmethods);
    }
}

/**
 * Save setting.
 *
 * @param $data
 * @param $course
 * @throws dml_exception
 */
function local_assessment_archive_coursemodule_edit_post_actions($data, $course) {
    $archivingenabled = $data->local_assessment_archiving_enabled ?? false;
    $method = $data->assessment_method ?? null;
    \local_assessment_archive\helper::set_cm_archivingenabled($data->coursemodule, $archivingenabled, $method);
    return $data;
}

/**
 * Add link to overview page.
 *
 * @param settings_navigation $nav
 * @param $context
 */
function local_assessment_archive_extend_settings_navigation(settings_navigation $nav, $context) {
    if ($context && ($context instanceof context_course)) {
        if (has_capability('local/assessment_archive:manage', $context) && $course = $nav->get('courseadmin')) {
            $url = new moodle_url('/local/assessment_archive/index.php',
                array('courseid' => $context->get_course_context()->instanceid));
            $course->add(get_string('linkname', 'local_assessment_archive'), $url, navigation_node::TYPE_CUSTOM,
                null, null, new pix_icon('i/report', ''));
        }
    }
}
