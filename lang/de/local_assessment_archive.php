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
 * @copyright  2022 Lars Bonczek, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Archivierung von E-Assessments';

// Settings.
$string['methods_archive'] = 'Archiviere Aktivitäten mit diesen Prüfungsformaten';
$string['methods_archive_desc'] = 'Prüfungsformate, welche bestimmen, dass die Aktivität archiviert werden muss.';
$string['methods_dont_archive'] = 'Archiviere Aktivitäten mit diesen Prüfungsformaten NICHT';
$string['methods_dont_archive_desc'] = 'Prüfungsformate, welche bestimmen, dass die Aktivität NICHT archiviert werden muss.';
$string['info'] = 'Info-Text';
$string['info_desc'] = 'Information, die auf der Archivierungs-Seite angezeigt wird. Hier können Sie beschreiben, wie die Archivierung in Ihrer Organisation funktioniert.';
$string['wait_after_attempt'] = 'Wartezeit nach abgegebenem Versuch';
$string['wait_after_attempt_desc'] = 'Wartezeit, nach der die Aktivität (automatisch) neu archiviert wird, wenn ein Versuch abgegeben wurde. Ein höherer Wert verringert die Anzahl an Archivierungen.';
$string['wait_after_grading'] = 'Wartezeit nach Bewertung';
$string['wait_after_grading_desc'] = 'Wartezeit, nach der die Aktivität (automatisch) neu archiviert wird, wenn ein Versuch bewertet wurde. Ein höherer Wert verringert die Anzahl an Archivierungen.';

$string['form_assessment_archiving'] = 'Archivierung';
$string['form_assessment_archiving_after'] = 'Diese Aktivität ist prüfungsrelevant und muss archiviert werden.';

$string['linkname'] = 'Archivierung';
$string['nothingfound'] = 'Keine passenden Aktivitäten gefunden.';
$string['moduletype'] = 'Typ';
$string['nomethodsavailable'] = 'Keine verfügbar';
