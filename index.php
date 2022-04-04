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
 * List all assignments and quizzes in a course.
 *
 * @package    local_assessment_archive
 * @copyright  2022 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_assessment_archive\helper;

require_once(dirname(__FILE__) . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHAEXT);

$context = context_course::instance($courseid);
$title = get_string('pluginname', 'local_assessment_archive');
$url = new moodle_url('/local/assessment_archive/index.php', [
    'courseid' => $courseid
]);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');

require_login($courseid);
require_capability('local/assessment_archive:manage', $context);

$cms = helper::get_course_modules($courseid);

if ($action == 'save') {
    require_sesskey();

    foreach ($cms as $cmdata) {
        $method = null;
        if (class_exists('\local_assessment_methods\helper')) {
            $method = optional_param("method_{$cmdata->cmid}", null, PARAM_ALPHANUMEXT);
            $options = \local_assessment_methods\helper::get_method_options($cmdata->method, $cmdata->modname);
            if ($method) {
                if (!array_key_exists($method, $options)) {
                    throw new \moodle_exception('invalid_method', 'local_assessment_archive');
                }
                \local_assessment_methods\helper::set_cm_method($cmdata->cmid, $method);
            } else if ($method !== null && !$cmdata->method) {
                // Delete local_assessment_archive record if no assessment method was selected.
                helper::reset_cm_archivingenabled($cmdata->cmid);
                continue;
            }
        }
        $archivingenabled = optional_param("archiving_{$cmdata->cmid}", null, PARAM_BOOL);
        if ($archivingenabled !== null || $method !== null) {
            helper::set_cm_archivingenabled($cmdata->cmid, $archivingenabled, $method);
        }
    }

    redirect($url);
    return;
}

$modinfo = get_fast_modinfo($courseid);
$data = new \stdClass();

$availcourses = array();
foreach (enrol_get_my_courses() as $course) {
    $context = context_course::instance($course->id, IGNORE_MISSING);
    if (has_capability('local/assessment_archive:manage', $context)) {
        $availcourses[$course->id] = $course->shortname;
    }
}
$data->course_selection_html = $OUTPUT->single_select(
    new moodle_url('/local/assessment_archive/index.php'),
    'courseid', $availcourses, $courseid, null, 'courseselector'
);

$data->action_url = new moodle_url('/local/assessment_archive/index.php', ['courseid' => $courseid, 'action' => 'save']);
$data->cancel_url = $url;
$data->sesskey = sesskey();

$data->method_plugin_exists = class_exists('\local_assessment_methods\helper');

$config = get_config('local_assessment_archive');
$data->info = format_text($config->info);
$data->methods_archive = $config->methods_archive;
$data->methods_dont_archive = $config->methods_dont_archive;

foreach ($cms as $cmdata) {
    $cm = $modinfo->get_cm($cmdata->cmid);
    $row = new \stdClass();
    $row->cmid = $cmdata->cmid;

    $url = new \moodle_url("/mod/{$cm->modname}/view.php", ['id' => $cm->id]);
    $activitylink = \html_writer::empty_tag('img', array('src' => $cm->get_icon_url(),
            'class' => 'iconlarge activityicon', 'alt' => '', 'role' => 'presentation', 'aria-hidden' => 'true')) . ' ' .
        \html_writer::tag('span', $cm->get_formatted_name(), array('class' => 'instancename'));
    $row->link = \html_writer::link($url, $activitylink, array('class' => 'aalink'));

    $row->module = $cm->get_module_type_name();

    if (class_exists('\local_assessment_methods\helper')) {
        if ($options = \local_assessment_methods\helper::get_method_options($cmdata->method, $cm->modname)) {
            $row->method_options = [];
            foreach ($options as $key => $value) {
                $option = new \stdClass();
                $option->key = $key;
                $option->value = $value;
                $option->selected = ($key == $cmdata->method);
                $row->method_options[] = $option;
            }
            $row->has_method_options = true;
        }
    }

    $row->archive = $cmdata->archive;

    $data->activities[] = $row;
}
$data->activities_exist = !empty($data->activities);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $OUTPUT->render_from_template('local_assessment_archive/archive_form', $data);
echo $OUTPUT->footer();