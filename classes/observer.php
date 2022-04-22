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
 * Event handlers.
 *
 * @package    local_assessment_archive
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessment_archive;

defined('MOODLE_INTERNAL') || die();

/**
 * Export base class.
 *
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Assignment submission has been created.
     *
     * @param \mod_assign\event\base $event
     */
    static public function assign_submission_created_updated(\mod_assign\event\base $event) {
        $wait = get_config('local_assessment_archive', 'wait_after_attempt');
        if ($wait) {
            $time = time() + $wait;
            helper::check_archiving_enabled_and_schedule_task($event->get_context(), $time, export::REASON_ATTEMPT_SUBMITTED);
        }
    }

    /**
     * Assignment submission has been graded.
     *
     * @param \mod_assign\event\base $event
     */
    static public function assign_submission_graded(\mod_assign\event\base $event) {
        $wait = get_config('local_assessment_archive', 'wait_after_grading');
        if ($wait) {
            $time = time() + $wait;
            helper::check_archiving_enabled_and_schedule_task($event->get_context(), $time, export::REASON_ATTEMPT_GRADED);
        }
    }

    /**
     * Quiz attempt has been submitted.
     *
     * @param \core\event\base $event
     */
    static public function quiz_attempt_submitted(\core\event\base $event) {
        $wait = get_config('local_assessment_archive', 'wait_after_attempt');
        if ($wait) {
            $time = time() + $wait;
            helper::check_archiving_enabled_and_schedule_task($event->get_context(), $time, export::REASON_ATTEMPT_SUBMITTED);
        }
    }

    /**
     * Quiz attempt has been submitted.
     *
     * @param \core\event\base $event
     */
    static public function quiz_attempt_graded(\core\event\base $event) {
        $wait = get_config('local_assessment_archive', 'wait_after_grading');
        if ($wait) {
            $time = time() + $wait;
            helper::check_archiving_enabled_and_schedule_task($event->get_context(), $time, export::REASON_ATTEMPT_GRADED);
        }
    }

    /**
     * Course module (activity) has been deleted.
     *
     * @param \core\event\course_module_deleted $event
     */
    static public function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;
        $DB->delete_records('local_assessment_archive', ['cmid' => $event->objectid]);
        $DB->delete_records('local_assessment_archivehist', ['cmid' => $event->objectid]);
    }
}
