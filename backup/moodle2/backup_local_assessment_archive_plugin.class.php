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
 * Define backup structure.
 *
 * @package    local_assessment_archive
 * @author     Martin Gauk <gauk@math.tu-berlin.de>
 * @copyright  2022 innoCampus, Technische Universität Berlin <info@isis.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/backup/moodle2/backup_local_plugin.class.php');

class backup_local_assessment_archive_plugin extends backup_local_plugin {
    protected function define_module_plugin_structure() {
        // Define virtual plugin element.
        $plugin = $this->get_plugin_element(null, null, null);

        // Create plugin container element with standard name.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Add wrapper to plugin.
        $plugin->add_child($pluginwrapper);

        // Plugin's structure and add to wrapper.
        $archive = new backup_nested_element('archive', ['cmid'], ['archive']);
        $pluginwrapper->add_child($archive);

        // Use database to get source.
        $archive->set_source_table('local_assessment_archive', ['cmid' => backup::VAR_MODID]);
    }
}
