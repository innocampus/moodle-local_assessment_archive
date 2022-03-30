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
 * Strings for plugin.
 *
 * @package    local_assessment_archive
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Archive e-assessments';

// Settings.
$string['methods_archive'] = 'Archive activities with these assessment methods';
$string['methods_archive_desc'] = 'Assessment methods that indicate that the activity is part of an exam and must be archived.';
$string['methods_dont_archive'] = 'Do not archive activities with these assessment methods';
$string['methods_dont_archive_desc'] = 'Assessment methods that indicate that the activity does not need to be archived.';
$string['info'] = 'Info text';
$string['info_desc'] = 'Information that is displayed on the archiving information page. You may want to describe how archiving is done at your organisation.';
$string['wait_after_attempt'] = 'Waiting time after an attempt was submitted';
$string['wait_after_attempt_desc'] = 'Waiting time until the activity is (automatically) archived again after an attempt was submitted. A higher value avoids too many archives.';
$string['wait_after_grading'] = 'Waiting time after grading';
$string['wait_after_grading_desc'] = 'Waiting time until the activity is (automatically) archived again after grading. A higher value avoids too many archives.';

$string['form_assessment_archiving'] = 'Archiving';
$string['form_assessment_archiving_after'] = 'This activity is part of an exam and must be archived.';
