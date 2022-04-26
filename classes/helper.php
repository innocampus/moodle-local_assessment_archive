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
     * Returns a list of all quiz and assign activities in a course containing their ID, module name, archiving setting
     * (and assessment method, if local_assessment_methods is installed).
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public static function get_course_modules(int $courseid): array {
        global $DB;
        $select_assessment_methods = "";
        $join_assessment_methods = "";
        if (class_exists('\local_assessment_methods\helper')) {
            $select_assessment_methods = ", lam.method";
            $join_assessment_methods = "LEFT JOIN {local_assessment_methods} lam ON lam.cmid = cm.id";
        }
        $results = $DB->get_records_sql("
            SELECT cm.id AS cmid, md.name AS modname, cs.section, cs.sequence, laa.archive$select_assessment_methods FROM {course_modules} cm
            INNER JOIN {modules} md ON md.id = cm.module
            INNER JOIN {course_sections} cs ON cm.section = cs.id
            LEFT JOIN {local_assessment_archive} laa ON laa.cmid = cm.id
            $join_assessment_methods
            WHERE cm.course = :courseid AND md.name IN ('quiz', 'assign')
        ", [
            'courseid' => $courseid
        ]);
        // Sort modules by order in course.
        usort($results, function ($a, $b) {
            if ($a->section != $b->section)
                return $a->section <=> $b->section;

            // Sequence of modules inside a section is stored as a comma-separated list of cmids for some reason.
            $seq = explode(',', $a->sequence);
            $apos = array_search($a->cmid, $seq);
            $bpos = array_search($b->cmid, $seq);
            return $apos <=> $bpos;
        });
        return $results;
    }

    /**
     * Returns a list of all quiz and assign activities that should be archived according to their assessment method or
     * archiving setting.
     *
     * @param bool $neverarchived Set to true to only return activities that have never been archived before.
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_cmids_to_archive(bool $neverarchived = false): array {
        global $DB;
        $join_assessment_methods = "";
        if (class_exists('\local_assessment_methods\helper')) {
            $join_assessment_methods = "LEFT JOIN {local_assessment_methods} lam ON lam.cmid = cm.id";

            $config = get_config('local_assessment_archive');
            $methodsarchive = explode(',', $config->methods_archive);
            $methodsdontarchive = explode(',', $config->methods_dont_archive);
            list($inmethodsarchive, $inmethodsarchiveparams) = $DB->get_in_or_equal($methodsarchive);
            list($inmethodsdontarchive, $inmethodsdontarchiveparams) = $DB->get_in_or_equal($methodsdontarchive);
            $wherearchiving = "AND (lam.method $inmethodsarchive OR (laa.archive = 1 AND NOT (lam.method $inmethodsdontarchive)))";
            $paramsarchiving = array_merge($inmethodsarchiveparams, $inmethodsdontarchiveparams);
        } else {
            $wherearchiving = "AND laa.archive = 1";
            $paramsarchiving = [];
        }

        $whereneverarchived = "";
        if ($neverarchived) {
            $whereneverarchived = "AND cm.id NOT IN (SELECT lah.cmid FROM {local_assessment_archivehist} lah)";
        }

        return $DB->get_fieldset_sql("
            SELECT cm.id FROM {course_modules} cm
            INNER JOIN {modules} md ON md.id = cm.module
            INNER JOIN {course_sections} cs ON cm.section = cs.id
            LEFT JOIN {local_assessment_archive} laa ON laa.cmid = cm.id
            $join_assessment_methods
            WHERE md.name IN ('quiz', 'assign')
            $wherearchiving
            $whereneverarchived
        ", $paramsarchiving);
    }

    const ARCHIVING_DISABLED = 0;
    const ARCHIVING_SCHEDULED = 2;

    /**
     * Check if archiving is enabled and schedule an archive task.
     *
     * @param \context $context context of the activity
     * @param int $runtime run time of the ad-hoc task
     * @param int $reason why this activity is being exported (one of the export::REASON_* constonts)
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function check_archiving_enabled_and_schedule_task(\context $context, int $runtime, int $reason) {
        if ($context instanceof \context_module) {
            $cmid = $context->instanceid;
            $cache = \cache::make('local_assessment_archive', 'archiving_scheduled');
            $cachekey = "{$cmid}_{$reason}";
            $archivingstatus = $cache->get($cachekey);
            if ($archivingstatus === false) {
                // No cache entry.
                $enabled = self::get_cm_archiving_enabled($cmid);
                $cache->set($cachekey, ($enabled) ? self::ARCHIVING_SCHEDULED : self::ARCHIVING_DISABLED);
            } else {
                // When there is a cache entry, archiving is either disabled or already scheduled.
                // The cache entry will be deleted by the archive task.
                return;
            }

            self::schedule_archiving($cmid, $runtime, $reason);
        }
    }

    /**
     * Delete cache entries belonging to a cmid.
     *
     * @param int $cmid
     * @param int|null $reason
     */
    public static function delete_cache(int $cmid, ?int $reason = null) {
        $cache = \cache::make('local_assessment_archive', 'archiving_scheduled');

        if ($reason) {
            $cache->delete("{$cmid}_{$reason}");
        } else {
            $keys = array_map(function($r) use ($cmid) {
                return "{$cmid}_{$r}";
            }, export::ALL_REASONS);
            $cache->delete_many($keys);
        }
    }

    /**
     * Schedule an archiving task.
     *
     * This function will not schedule another task if there is already one for a cmid with the same reason.
     *
     * @param int $cmid
     * @param int $runtime run time of the ad-hoc task
     * @param int $reason why this activity is being exported (one of the export::REASON_* constonts)
     * @return bool
     */
    public static function schedule_archiving(int $cmid, int $runtime, int $reason): bool {
        $task = new task\archive_task();
        $task->set_custom_data([
            'cmid' => (int) $cmid,
            'reason' => (int) $reason,
        ]);
        $task->set_next_run_time($runtime);
        return \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Resets the stored archivingenabled value for an activity.
     *
     * @param int $cmid
     * @throws \dml_exception
     */
    public static function reset_cm_archivingenabled(int $cmid) {
        global $DB;
        $DB->delete_records('local_assessment_archive', ['cmid' => $cmid]);
    }

    /**
     * Set whether archiving is enabled for an activity. If the given assessment method overrides this setting, the db
     * record is removed instead.
     *
     * @param int $cmid
     * @param bool $archivingenabled
     * @param string|null $method
     * @throws \dml_exception
     */
    public static function set_cm_archivingenabled(int $cmid, bool $archivingenabled, ?string $method) {
        global $DB;
        if (class_exists('\local_assessment_methods\helper') && $method) {
            $override = self::is_assessment_method_archiving_enabled($method);
            if ($override !== null) {
                // Reset archivingenabled if it's overridden by local_assessment_methods.
                self::reset_cm_archivingenabled($cmid);
                self::delete_cache($cmid);
                return;
            }
        }

        if ($record = $DB->get_record('local_assessment_archive', ['cmid' => $cmid])) {
            $record->archive = $archivingenabled;
            $DB->update_record('local_assessment_archive', $record);
        } else {
            $DB->insert_record('local_assessment_archive', ['cmid' => $cmid, 'archive' => $archivingenabled]);
        }

        self::delete_cache($cmid);
    }

    /**
     * Returns whether archiving is enabled for an activity.
     *
     * @param int $cmid
     * @return bool
     * @throws \dml_exception
     */
    public static function get_cm_archiving_enabled(int $cmid) : bool {
        global $DB;

        if (class_exists('\local_assessment_methods\helper')) {
            $method = \local_assessment_methods\helper::get_cm_method($cmid);
            if ($method) {
                $enabled = self::is_assessment_method_archiving_enabled($method);
                if ($enabled !== null) {
                    return $enabled;
                }
            }
        }
        return $DB->get_field('local_assessment_archive', 'archive', ['cmid' => $cmid]);
    }

    /**
     * Determine whether archiving is enabled for this method. Returns null if the user has to decide.
     *
     * @param string $method
     * @return bool|null
     * @throws \dml_exception
     */
    public static function is_assessment_method_archiving_enabled(string $method) : ?bool {
        $config = get_config('local_assessment_archive');
        // Setting methods_archive precedes methods_dont_archive.
        if ($config->methods_archive && in_array($method, explode(',', $config->methods_archive))) {
            return true;
        } else if ($config->methods_dont_archive && in_array($method, explode(',', $config->methods_dont_archive))) {
            return false;
        }
        return null;
    }
}
