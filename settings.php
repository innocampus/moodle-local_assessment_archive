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

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_assessment_export', get_string('pluginname', 'local_assessment_export'));

    /** @var admin_category $ADMIN */
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configduration(
        'local_assessment_export/wait_after_attempt',
        get_string('wait_after_attempt', 'local_assessment_export'),
        get_string('wait_after_attempt_desc', 'local_assessment_export'),
        43200 // 12 hours
    ));

    $settings->add(new admin_setting_configduration(
        'local_assessment_export/wait_after_grading',
        get_string('wait_after_grading', 'local_assessment_export'),
        get_string('wait_after_grading_desc', 'local_assessment_export'),
        604800 // 7 days
    ));

    $settings->add(new admin_setting_confightmleditor(
        'local_assessment_export/info',
        get_string('info', 'local_assessment_export'),
        get_string('info_desc', 'local_assessment_export'),
        ''
    ));


    // Add more settings if local_assessment_methods is installed.
    if (class_exists('\local_assessment_methods\helper')) {
        $methods = [];
        foreach (\local_assessment_methods\helper::get_methods() as $methodid => $value) {
            $methods[$methodid] = $methodid;
        }

        $settings->add(new admin_setting_configmultiselect(
            'local_assessment_export/methods_archive',
            get_string('methods_archive', 'local_assessment_export'),
            get_string('methods_archive_desc', 'local_assessment_export'),
            [], $methods
        ));

        $settings->add(new admin_setting_configmultiselect(
            'local_assessment_export/methods_dont_archive',
            get_string('methods_dont_archive', 'local_assessment_export'),
            get_string('methods_dont_archive_desc', 'local_assessment_export'),
            [], $methods
        ));
    }

}
