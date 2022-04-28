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
    $settings = new admin_settingpage('local_assessment_archive', get_string('pluginname', 'local_assessment_archive'));

    /** @var admin_category $ADMIN */
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configdirectory(
        'local_assessment_archive/directory',
        get_string('directory', 'local_assessment_archive'),
        get_string('directory_desc', 'local_assessment_archive'),
        ''
    ));

    $settings->add(new admin_setting_configdirectory(
        'local_assessment_archive/tempdir',
        get_string('temp_directory', 'local_assessment_archive'),
        get_string('temp_directory_desc', 'local_assessment_archive'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_assessment_archive/tsa_url',
        get_string('time_stamp_server', 'local_assessment_archive'),
        get_string('time_stamp_server_desc', 'local_assessment_archive'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_assessment_archive/meta_data_custom_profile_fields',
        get_string('meta_data_custom_profile_fields', 'local_assessment_archive'),
        get_string('meta_data_custom_profile_fields_desc', 'local_assessment_archive'),
        ''
    ));

    $waitafterattempt = new admin_setting_configduration(
        'local_assessment_archive/wait_after_attempt',
        get_string('wait_after_attempt', 'local_assessment_archive'),
        get_string('wait_after_attempt_desc', 'local_assessment_archive'),
        43200 // 12 hours
    );
    $waitafterattempt->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $settings->add($waitafterattempt);

    $waitaftergrading = new admin_setting_configduration(
        'local_assessment_archive/wait_after_grading',
        get_string('wait_after_grading', 'local_assessment_archive'),
        get_string('wait_after_grading_desc', 'local_assessment_archive'),
        604800 // 7 days
    );
    $waitaftergrading->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $settings->add($waitaftergrading);

    $settings->add(new admin_setting_confightmleditor(
        'local_assessment_archive/info',
        get_string('info', 'local_assessment_archive'),
        get_string('info_desc', 'local_assessment_archive'),
        ''
    ));


    // Add more settings if local_assessment_methods is installed.
    if (class_exists('\local_assessment_methods\helper')) {
        $methods = [];
        foreach (\local_assessment_methods\helper::get_methods() as $methodid => $value) {
            $methods[$methodid] = $methodid;
        }

        $settings->add(new admin_setting_configmultiselect(
            'local_assessment_archive/methods_archive',
            get_string('methods_archive', 'local_assessment_archive'),
            get_string('methods_archive_desc', 'local_assessment_archive'),
            [], $methods
        ));

        $settings->add(new admin_setting_configmultiselect(
            'local_assessment_archive/methods_dont_archive',
            get_string('methods_dont_archive', 'local_assessment_archive'),
            get_string('methods_dont_archive_desc', 'local_assessment_archive'),
            [], $methods
        ));
    }

}
