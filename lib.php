<?php

defined('MOODLE_INTERNAL') || die;

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
