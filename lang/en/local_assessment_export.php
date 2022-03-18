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
 * @package    local_assessment_export
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Export and archive e-assessments';

// Settings.
$string['wait_after_grading'] = 'Waiting time after grading';
$string['wait_after_grading_desc'] = 'Waiting time until an attempt is (automatically) exported again after grading. A higher value avoids too many exports.';

// Exceptions and errors.
$string['file_error'] = 'Could not add file {$a->file} with hash {$a->hash} to archive: {$a->message}';
$string['image_not_found'] = 'Could not add image {$a} to archive.';
$string['js_not_found'] = 'Could not add javascript file {$a} to archive.';
$string['preg_replace_files_error'] = 'Error in preg_replace_callback during replacing the files.';
$string['preg_replace_images_error'] = 'Error in preg_replace_callback during replacing images.';
$string['preg_replace_javascript_error'] = 'Error in preg_replace_callback during replacing the javascript files.';
$string['unsupported_file_area'] = 'Cannot replace url {$a} as the file area is not supported by local_assessment_export.';
$string['zip_create_error'] = 'Error while creating the zip file containing the attempt.';
