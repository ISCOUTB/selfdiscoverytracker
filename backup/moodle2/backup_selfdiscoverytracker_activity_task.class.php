<?php
/**
 * Self Discovery Tracker Module Backup Task
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/selfdiscoverytracker/backup/moodle2/backup_selfdiscoverytracker_stepslib.php');

class backup_selfdiscoverytracker_activity_task extends backup_activity_task {

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
        $this->add_step(new backup_selfdiscoverytracker_activity_structure_step('selfdiscoverytracker_structure', 'selfdiscoverytracker.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of selfdiscoverytrackers
        $search = "/(" . $base . "\/mod\/selfdiscoverytracker\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SELFDISCOVERYTRACKERINDEX*$2@$', $content);

        // Link to selfdiscoverytracker view by moduleid
        $search = "/(" . $base . "\/mod\/selfdiscoverytracker\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SELFDISCOVERYTRACKERVIEWBYID*$2@$', $content);

        return $content;
    }
}
