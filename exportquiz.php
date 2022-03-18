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
 * Export a quiz attempt. TODO Remove this file (this is just for testing purposes).
 *
 * @package    local_assessment_export
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$attemptid = required_param('attemptid', PARAM_INT);

$tmppath = get_request_storage_directory();
$tmpfile = $tmppath . '/export.zip';

$attemptobj = quiz_attempt::create($attemptid);
$exportquiz = new \local_assessment_export\export_quiz($attemptobj, $tmpfile);
$exportquiz->process_attempt();
$exportquiz->save();

header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
header('Pragma: no-cache');
header('Expires: '. gmdate(DateTimeInterface::RFC1123, 0));
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=export.zip');
readfile($tmpfile);
