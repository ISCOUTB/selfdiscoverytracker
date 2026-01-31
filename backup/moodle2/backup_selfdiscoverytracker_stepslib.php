<?php
/**
 * Self Discovery Tracker Module Backup Structure Step
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_selfdiscoverytracker_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        // To know valid related tags, we'll see which info we extracted
        $selfdiscoverytracker = new backup_nested_element('selfdiscoverytracker', array('id'), array(
            'name', 'intro', 'introformat', 'grade', 'completionalltests', 'timecreated', 'timemodified'));

        // Define sources
        $selfdiscoverytracker->set_source_table('selfdiscoverytracker', array('id' => backup::VAR_ACTIVITYID));

        // Return the root element (selfdiscoverytracker), wrapped into standard activity structure
        return $this->prepare_activity_structure($selfdiscoverytracker);
    }
}
