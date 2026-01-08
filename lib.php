<?php
/**
 * Library functions for Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Supports the module.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function selfdiscoverytracker_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_BACKUP_MOODLE2:    return true;
        default: return null;
    }
}

/**
 * Dynamically updates course-module information for the current user.
 *
 * We use this to suppress completion UI for teacher/admin users so they don't
 * see student-oriented "to do"/"done" completion prompts in the course page.
 *
 * @param cm_info $cm
 * @return void
 */
function selfdiscoverytracker_cm_info_dynamic(cm_info $cm): void {
    $coursecontext = context_course::instance($cm->course);

    $isteacheroradmin = has_capability('moodle/course:update', $coursecontext) ||
        has_capability('moodle/course:manageactivities', $coursecontext) ||
        has_capability('moodle/course:viewhiddenactivities', $coursecontext) ||
        has_capability('moodle/site:config', $coursecontext);

    if ($isteacheroradmin) {
        // Hide completion status display for privileged users without changing
        // the actual activity settings or completion state.
        $cm->completion = COMPLETION_TRACKING_NONE;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->completionpassgrade = null;
        $cm->completiongradeitemnumber = null;
    }
}

/**
 * Prefered callback name (Moodle checks mod_{modname}_* first).
 *
 * @param cm_info $cm
 * @return void
 */
function mod_selfdiscoverytracker_cm_info_dynamic(cm_info $cm): void {
    selfdiscoverytracker_cm_info_dynamic($cm);
}

/**
 * Adds per-user view-only data for the course page.
 *
 * We can't reliably alter completion tracking flags per-user (they are cached/static),
 * but we can add an extra CSS class and hide the completion UI in the course page.
 *
 * @param cm_info $cm
 * @return void
 */
function selfdiscoverytracker_cm_info_view(cm_info $cm): void {
    global $USER;
    
    $coursecontext = context_course::instance($cm->course);
    $isteacheroradmin = has_capability('moodle/course:update', $coursecontext) ||
        has_capability('moodle/course:manageactivities', $coursecontext) ||
        has_capability('moodle/course:viewhiddenactivities', $coursecontext) ||
        has_capability('moodle/site:config', $coursecontext);

    if ($isteacheroradmin) {
        $existing = trim($cm->extraclasses ?? '');
        $cm->set_extra_classes(trim($existing . ' sdt-hidecompletion'));
    } else {
        // For students, we check completion status dynamically on course view
        // to ensure it updates immediately without page refresh.
        if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC && isloggedin() && !isguestuser()) {
            // We use the class loader or include checking to stay safe
            if (class_exists('\mod_selfdiscoverytracker\tracker_helper')) {
                \mod_selfdiscoverytracker\tracker_helper::check_and_update_completion($cm, $USER->id);
            } else {
                global $CFG;
                require_once($CFG->dirroot . '/mod/selfdiscoverytracker/classes/tracker_helper.php');
                \mod_selfdiscoverytracker\tracker_helper::check_and_update_completion($cm, $USER->id);
            }
        }
    }
}

/**
 * Prefered callback name (Moodle checks mod_{modname}_* first).
 *
 * @param cm_info $cm
 * @return void
 */
function mod_selfdiscoverytracker_cm_info_view(cm_info $cm): void {
    selfdiscoverytracker_cm_info_view($cm);
}

/**
 * Adds a new instance of the selfdiscoverytracker activity.
 *
 * @param stdClass $selfdiscoverytracker The object containing the data to be inserted
 * @return int The id of the new instance
 */
function selfdiscoverytracker_add_instance($selfdiscoverytracker) {
    global $DB;

    $selfdiscoverytracker->timecreated = time();
    $selfdiscoverytracker->timemodified = time();

    return $DB->insert_record('selfdiscoverytracker', $selfdiscoverytracker);
}

/**
 * Updates an instance of the selfdiscoverytracker activity.
 *
 * @param stdClass $selfdiscoverytracker The object containing the data to be updated
 * @return boolean True on success, false on failure
 */
function selfdiscoverytracker_update_instance($selfdiscoverytracker) {
    global $DB;

    $selfdiscoverytracker->timemodified = time();
    $selfdiscoverytracker->id = $selfdiscoverytracker->instance;

    return $DB->update_record('selfdiscoverytracker', $selfdiscoverytracker);
}

/**
 * Deletes an instance of the selfdiscoverytracker activity.
 *
 * @param int $id The id of the instance to be deleted
 * @return boolean True on success, false on failure
 */
function selfdiscoverytracker_delete_instance($id) {
    global $DB;

    if (!$selfdiscoverytracker = $DB->get_record('selfdiscoverytracker', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('selfdiscoverytracker', array('id' => $selfdiscoverytracker->id));

    return true;
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in selfdiscoverytracker settings.
 *
 * @param stdClass $course Course
 * @param stdClass $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function selfdiscoverytracker_get_completion_state($course, $cm, $userid, $type) {
    global $DB, $CFG;

    $selfdiscoverytracker = $DB->get_record('selfdiscoverytracker', array('id' => $cm->instance));

    if ($selfdiscoverytracker && $selfdiscoverytracker->completionalltests) {
        require_once($CFG->dirroot . '/mod/selfdiscoverytracker/classes/tracker_helper.php');
        $all_completed = \mod_selfdiscoverytracker\tracker_helper::are_all_tests_completed($userid);
        return $all_completed;
    }
    
    return $type;
}

