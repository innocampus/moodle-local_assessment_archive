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
 * @package    local_assessment_archive
 * @copyright  2022 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessment_archive;

defined('MOODLE_INTERNAL') || die();

/**
 * Assessment archive helper class
 *
 * @package    local_assessment_archive
 * @copyright  2022 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class helper {

    /**
     * @throws \dml_exception
     */
    public static function get_course_modules($courseid): array {
        global $DB;
        $select_assessment_methods = "";
        $join_assessment_methods = "";
        if (class_exists('\local_assessment_methods\helper')) {
            $select_assessment_methods = ", lam.method";
            $join_assessment_methods = "LEFT JOIN {local_assessment_methods} lam ON lam.cmid = cm.id";
        }
        return $DB->get_records_sql("
            SELECT cm.id AS cmid, md.name AS modname, laa.archive$select_assessment_methods FROM {course_modules} cm
            INNER JOIN {modules} md ON md.id = cm.module
            LEFT JOIN {local_assessment_archive} laa ON laa.cmid = cm.id
            $join_assessment_methods
            WHERE cm.course = :courseid AND md.name IN ('quiz', 'assign')
        ", [
            'courseid' => $courseid
        ]);
    }

    public static function reset_cm_archivingenabled($cmid) {
        global $DB;
        $DB->delete_records('local_assessment_archive', ['cmid' => $cmid]);
    }

    public static function set_cm_archivingenabled($cmid, $archivingenabled, $method = null) {
        global $DB;
        if (class_exists('\local_assessment_methods\helper') && $method) {
            $config = get_config('local_assessment_archive');
            // Setting methods_archive precedes methods_dont_archive.
            if (in_array($method, explode(',', $config->methods_archive))) {
                $archivingenabled = true;
            } else if (in_array($method, explode(',', $config->methods_dont_archive))) {
                $archivingenabled = false;
            }
        }

        if ($record = $DB->get_record('local_assessment_archive', ['cmid' => $cmid])) {
            $record->archive = $archivingenabled;
            $DB->update_record('local_assessment_archive', $record);
        } else {
            $DB->insert_record('local_assessment_archive', ['cmid' => $cmid, 'archive' => $archivingenabled]);
        }
    }
}
