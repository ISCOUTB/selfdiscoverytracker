<?php
/**
 * Self Discovery Tracker Module Restore Task
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/selfdiscoverytracker/backup/moodle2/restore_selfdiscoverytracker_stepslib.php');

class restore_selfdiscoverytracker_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // selfdiscoverytracker only has one structure step
        $this->add_step(new restore_selfdiscoverytracker_activity_structure_step('selfdiscoverytracker_structure', 'selfdiscoverytracker.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('selfdiscoverytracker', array('intro'), 'selfdiscoverytracker');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('SELFDISCOVERYTRACKERINDEX', '/mod/selfdiscoverytracker/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('SELFDISCOVERYTRACKERVIEWBYID', '/mod/selfdiscoverytracker/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * selfdiscoverytracker activity logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('selfdiscoverytracker', 'add', 'view.php?id={course_module}', '{selfdiscoverytracker}');
        $rules[] = new restore_log_rule('selfdiscoverytracker', 'update', 'view.php?id={course_module}', '{selfdiscoverytracker}');
        $rules[] = new restore_log_rule('selfdiscoverytracker', 'view', 'view.php?id={course_module}', '{selfdiscoverytracker}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('selfdiscoverytracker', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
