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
 * Archive activity task.
 *
 * @package    local_assessment_archive
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessment_archive\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Archive activity task.
 *
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class archive_task extends \core\task\adhoc_task {
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        try {
            [$course, $cminfo] = get_course_and_cm_from_cmid($data->cmid);
        } catch (\moodle_exception $exception) {
            // Activity does not exist anymore. Ignore.
            return;
        }

        // Check if this activity should still be archived.
        if (!\local_assessment_archive\helper::get_cm_archiving_enabled($data->cmid)) {
            mtrace("Archiving is disabled for course module {$data->cmid}.");
            return;
        }

        $config = get_config('local_assessment_archive');
        if (empty($config->directory)) {
            throw new \moodle_exception('archive_directory_not_set', 'local_assessment_archive');
        }
        $export = new \local_assessment_archive\export($course, $cminfo, $data->reason);
        $export->archive($config->directory, $config->tsa_url);

        \local_assessment_archive\helper::delete_cache($data->cmid, $data->reason);

        $obj = new \stdClass();
        $obj->course = $course->id;
        $obj->cmid = $data->cmid;
        $obj->timecreated = time();
        $obj->reason = $data->reason;
        $DB->insert_record('local_assessment_archivehist', $obj);

        $reason = \local_assessment_archive\export::reason_to_string($data->reason);
        mtrace("Archived course module {$data->cmid} in course {$course->id} (reason {$reason}).");
    }
}
