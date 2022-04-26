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
 * Export class.
 *
 * @package    local_assessment_archive
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessment_archive;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/user/profile/lib.php');


/**
 * Export class.
 *
 * This class offers the following callbacks:
 *   local_assessment_archive_pre(\local_assessment_archive\export $export)
 *   local_assessment_archive_post(\local_assessment_archive\export $export, bool $success)
 *   local_assessment_archive_modify_metadata(\local_assessment_archive\export $export, \stdClass $metadata);
 *
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export {
    const REASON_ATTEMPT_SUBMITTED = 1;
    const REASON_ATTEMPT_GRADED = 2;
    const REASON_ARCHIVING_INITIALLY_ENABLED = 3;
    const REASON_ADMIN_SCRIPT = 4;

    const ALL_REASONS = [self::REASON_ATTEMPT_SUBMITTED, self::REASON_ATTEMPT_GRADED, self::REASON_ARCHIVING_INITIALLY_ENABLED,
        self::REASON_ADMIN_SCRIPT];

    /** @var \stdClass course data */
    private $course;

    /** @var \cm_info course module / activity */
    private $cminfo;

    /** @var int why this activity is being exported (one of the self::REASON_* constonts) */
    private $reason;


    /**
     * Constructor.
     *
     * @param \stdClass $course
     * @param \cm_info $cminfo course module
     * @param int $reason why this activity is being exported (one of the self::REASON_* constonts)
     */
    public function __construct(\stdClass $course, \cm_info $cminfo, int $reason) {
        $this->course = $course;
        $this->cminfo = $cminfo;
        $this->reason = $reason;
    }

    /**
     * Get course data.
     *
     * @return \stdClass
     */
    public function get_course() : \stdClass {
        return $this->course;
    }

    /**
     * Get course module.
     *
     * @return \cm_info
     */
    public function get_course_module_info() : \cm_info {
        return $this->cminfo;
    }

    /**
     * Get reason why this activity is being archived.
     *
     * @return int
     */
    public function get_reason() : int {
        return $this->reason;
    }

    /**
     * Archive an activity.
     *
     * @param string $directory base directory where to archive activities
     * @param null|string $tsaurl URL to a time stamping authority
     */
    public function archive(string $directory, ?string $tsaurl) {
        $callbacks = get_plugins_with_function('local_assessment_archive_pre');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($this);
            }
        }

        $time = time();
        $fileprefix = $this->cminfo->id . '-' . $this->reason . '-' . date(DATE_W3C, $time);

        // The temp files should be under $directory in order to rename/move the files later atomically.
        $tmpprefix = $directory . '/.' . $fileprefix;
        $tmpbackupfile = $tmpprefix . '.mbz';
        $tmpsigfile = $tmpprefix . '.tsr';
        $tmpjsonfile = $tmpprefix . '.json';

        $finaldir = $directory . '/' . $this->course->id;
        $finalbackupfile = $finaldir . "/{$fileprefix}.mbz";
        $finalsigfile = $finaldir . "/{$fileprefix}.tsr";
        $finaljsonfile = $finaldir . "/{$fileprefix}.json";

        if (!is_dir($finaldir)) {
            if (!mkdir($finaldir)) {
                throw new \moodle_exception('mkdir_error', 'local_assessment_archive');
            }
        }

        try {
            $this->backup_activity($tmpbackupfile);
            $this->save_metadata($tmpjsonfile, $time);

            if ($tsaurl) {
                $this->sign_file($tmpbackupfile, $tmpsigfile, $tsaurl);
                if (!rename($tmpsigfile, $finalsigfile)) {
                    throw new \moodle_exception('rename_error', 'local_assessment_archive');
                }
            }

            if (!rename($tmpbackupfile, $finalbackupfile) ||
                !rename($tmpjsonfile, $finaljsonfile)) {
                throw new \moodle_exception('rename_error', 'local_assessment_archive');
            }

            $callbacks = get_plugins_with_function('local_assessment_archive_post');
            foreach ($callbacks as $type => $plugins) {
                foreach ($plugins as $plugin => $pluginfunction) {
                    $pluginfunction($this, true);
                }
            }

        } catch (\Exception $e) {
            @unlink($tmpbackupfile);
            @unlink($tmpsigfile);
            @unlink($tmpjsonfile);
            @unlink($finalbackupfile);
            @unlink($finalsigfile);
            @unlink($finaljsonfile);

            $callbacks = get_plugins_with_function('local_assessment_archive_post');
            foreach ($callbacks as $type => $plugins) {
                foreach ($plugins as $plugin => $pluginfunction) {
                    $pluginfunction($this, false);
                }
            }

            throw $e;
        }

    }

    /**
     * Create a backup of an activity.
     *
     * @param string $filepath where to store backup
     */
    protected function backup_activity($filepath) {
        // Backup the activity.
        $user = get_admin();
        $controller = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $this->cminfo->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            0,
            $user->id
        );

        $settings = [
            'users' => true,
            'anonymize' => false,
            'role_assignments' => true,
            'activities' => true,
            'blocks' => true,
            'filters' => true,
            'comments' => true,
            //'badges' => false,
            //'calendarevents' => false,
            'userscompletion' => true,
            'logs' => true,
            'grade_histories' => true,
            'questionbank' => true,
            'groups' => true,
            'competencies' => true,
            'contentbankcontent' => true,
            //'legacyfiles' => false,
        ];
        foreach ($settings as $name => $value) {
            $controller->get_plan()->get_setting($name)->set_value($value);
        }

        $controller->execute_plan();
        $results = $controller->get_results();
        $file = $results['backup_destination'] ?? null;
        $controller->destroy();

        if (!$file || !$file->get_contenthash()) {
            throw new \moodle_exception('backup_error', 'local_assessment_archive');
        }

        if ($file->copy_content_to($filepath)) {
            $file->delete();
        }
    }

    /**
     * Save metadata to file.
     *
     * @param string $filepath where to store metadata
     * @param int $time
     */
    protected function save_metadata(string $filepath, int $time) {
        $data = new \stdClass();

        // General information.
        $data->date = date(DATE_W3C, $time);
        $data->date_timestamp = $time;
        $data->site_short_name = get_config('core', 'shortname');
        $data->archive_reason = self::reason_to_string($this->reason);

        // Course information.
        $data->course = new \stdClass();
        $data->course->id = $this->course->id;
        $data->course->name = $this->course->fullname;
        $data->course->short_name = $this->course->shortname;
        $data->course->idnumber = $this->course->idnumber;

        // Activity information.
        $data->activity = new \stdClass();
        $data->activity->cmid = $this->cminfo->id;
        $data->activity->instanceid = $this->cminfo->instance;
        $data->activity->idnumber = $this->cminfo->idnumber;
        $data->activity->type = $this->cminfo->modname;
        $data->activity->name = $this->cminfo->name;
        if (class_exists('\local_assessment_methods\helper')) {
            $data->activity->assessment_method = \local_assessment_methods\helper::get_cm_method($this->cminfo->id);
        }

        // Users enrolled in course.
        $data->users = [];
        $additionalfields = explode(',', get_config('local_assessment_archive', 'meta_data_custom_profile_fields'));
        $additionalfields = array_map('trim', $additionalfields);
        $dbgroups = groups_get_all_groups($this->course->id, 0, 0, 'g.*', true);
        $dbusers = get_enrolled_users($this->cminfo->context);
        foreach ($dbusers as $dbuser) {
            $user = new \stdClass();
            $user->id = $dbuser->id;
            $user->full_name = fullname($dbuser);
            $user->idnumber = $dbuser->idnumber;
            $user->email = $dbuser->email;
            $namefields = ['firstname', 'lastname', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename'];
            foreach ($namefields as $field) {
                if ($dbuser->$field) {
                    $user->$field = $dbuser->$field;
                }
            }

            $profilefields = profile_get_user_fields_with_data($dbuser->id);
            foreach ($profilefields as $field) {
                if (in_array($field->get_shortname(), $additionalfields)) {
                    $user->{$field->get_shortname()} = $field->data;
                }
            }

            $user->groups = array_column(array_filter($dbgroups, function($group) use ($dbuser) {
                return array_key_exists($dbuser->id, $group->members);
            }), 'name');

            $data->users[] = $user;
        }

        $callbacks = get_plugins_with_function('local_assessment_archive_modify_metadata');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($this, $data);
            }
        }

        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filepath, $json);
    }

    /**
     * Sign a file by a time stamping authority.
     *
     * Mostly copied from:
     * https://d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161/
     *
     * @param string $filepath file to sign
     * @param string $output
     * @param string $tsaurl URL to a time stamping authority
     */
    protected function sign_file(string $filepath, string $output, string $tsaurl) {
        $tmppath = $output . '.tmp.tsq';
        $cmd = 'openssl ts -query -data ' . escapeshellarg($filepath) . ' -sha256 -cert -out ' . escapeshellarg($tmppath);

        try {
            $retarray = [];
            exec($cmd . ' 2>&1', $retarray, $retcode);
            mtrace(implode("\n", $retarray));
            if ($retcode !== 0 || stripos($retarray[0], "openssl:Error") !== false || !file_exists($tmppath)) {
                throw new \moodle_exception('openssl_error', 'local_assessment_archive');
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tsaurl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($tmppath));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/timestamp-query'));
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status != 200 || strlen($response) < 100) {
                throw new \moodle_exception('tsa_signing_error', 'local_assessment_archive');
            }

            file_put_contents($output, $response);
        } finally {
            @unlink($tmppath);
        }
    }

    /**
     * Get reason code as a string for the metadata export.
     *
     * @param int $reason
     * @return string
     */
    static public function reason_to_string(int $reason) : string {
        switch ($reason) {
            case self::REASON_ATTEMPT_SUBMITTED:
                return 'attempt_submitted';
            case self::REASON_ATTEMPT_GRADED:
                return 'attempt_graded';
            case self::REASON_ARCHIVING_INITIALLY_ENABLED:
                return 'archiving_initially_enabled';
            case self::REASON_ADMIN_SCRIPT:
                return 'admin_script';
            default:
                return 'unknown';
        }
    }
}
