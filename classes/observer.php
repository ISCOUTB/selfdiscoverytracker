<?php
/**
 * Observer class for Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_selfdiscoverytracker;

defined('MOODLE_INTERNAL') || die();

class observer {
    /**
     * Observer for course_viewed event.
     * Checks if any selfdiscoverytracker instances in the course need completion updates.
     * 
     * @param \core\event\course_viewed $event
     */
    public static function course_viewed(\core\event\course_viewed $event) {
        $userid = $event->userid;
        $courseid = $event->courseid;

        // Validation of user.
        if (!isloggedin() || isguestuser() || $userid == 0) {
            return;
        }

        // Get fast modinfo.
        try {
            $modinfo = get_fast_modinfo($courseid);
        } catch (\moodle_exception $e) {
            // If course doesn't exist or other error, bail.
            return;
        }

        // Get instances of our module.
        $instances = $modinfo->get_instances_of('selfdiscoverytracker');
        if (empty($instances)) {
            return;
        }

        foreach ($instances as $cm) {
            // Only strictly needed if the user can view it, but completion might track hidden items too?
            // Usually we only care if user can likely complete it.
            if (!$cm->uservisible) {
                // If it's hidden, maybe we shouldn't update? 
                // But logically completion state exists regardless of visibility.
                // However, tracker_helper uses current user context implicitly?
                // No, tracker helper takes $userid.
            }
            
            \mod_selfdiscoverytracker\tracker_helper::check_and_update_completion($cm, $userid);
        }
    }
}
