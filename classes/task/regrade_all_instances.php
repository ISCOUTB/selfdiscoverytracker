<?php
/**
 * Recalculate Self Discovery Tracker grades after the 0/5 policy upgrade.
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_selfdiscoverytracker\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Applies the binary grade rule to all existing activity instances.
 */
class regrade_all_instances extends \core\task\adhoc_task {
    /**
     * Return the task name shown in Moodle's task administration.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskregradeallinstances', 'mod_selfdiscoverytracker');
    }

    /**
     * Rebuild the gradebook entries once Moodle has finished upgrading.
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/selfdiscoverytracker/lib.php');

        $instances = $DB->get_records('selfdiscoverytracker');
        foreach ($instances as $instance) {
            // Ensure both the stored setting and its gradebook item use 5.
            $instance->grade = 5;
            selfdiscoverytracker_grade_item_update($instance);
            selfdiscoverytracker_update_grades($instance);
        }
    }
}
