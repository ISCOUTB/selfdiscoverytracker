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
        case FEATURE_GRADE_HAS_GRADE:   return true;
        case FEATURE_GRADE_OUTCOMES:    return true;
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
    } else {
        // Students should see this activity embedded without a view link.
        $cm->set_no_view_link();
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
        global $PAGE, $CFG;

        // For students, we check completion status dynamically on course view
        // to ensure it updates immediately without page refresh.
        if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC && isloggedin() && !isguestuser()) {
            // We use the class loader or include checking to stay safe
            if (class_exists('\mod_selfdiscoverytracker\tracker_helper')) {
                \mod_selfdiscoverytracker\tracker_helper::check_and_update_completion($cm, $USER->id);
            } else {
                require_once($CFG->dirroot . '/mod/selfdiscoverytracker/classes/tracker_helper.php');
                \mod_selfdiscoverytracker\tracker_helper::check_and_update_completion($cm, $USER->id);
            }
        }

        // Render dashboard inline for students on the course page.
        if ($cm->uservisible && has_capability('mod/selfdiscoverytracker:view', $cm->context) &&
            isset($PAGE) && $PAGE->context && $PAGE->context->contextlevel == CONTEXT_COURSE &&
            $PAGE->context->instanceid == $cm->course) {
            if (!class_exists('\mod_selfdiscoverytracker\tracker_helper')) {
                require_once($CFG->dirroot . '/mod/selfdiscoverytracker/classes/tracker_helper.php');
            }

            try {
                $progress_data = \mod_selfdiscoverytracker\tracker_helper::get_all_tests_progress($USER->id);
                $course = $cm->get_modinfo()->get_course();

                $output = $PAGE->get_renderer('mod_selfdiscoverytracker');
                $dashboard = new \mod_selfdiscoverytracker\output\dashboard($course, $cm, $progress_data, false);

                // Inject the dashboard HTML into the content area of the module on the course page.
                $cm->set_content($output->render($dashboard), true);
                $cm->set_extra_classes(trim(($cm->extraclasses ?? '') . ' sdt-inline-dashboard'));
            } catch (Exception $e) {
                // Fail silently to avoid breaking the course page.
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

    $selfdiscoverytracker->id = $DB->insert_record('selfdiscoverytracker', $selfdiscoverytracker);

    selfdiscoverytracker_grade_item_update($selfdiscoverytracker);

    return $selfdiscoverytracker->id;
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

    if (!$DB->update_record('selfdiscoverytracker', $selfdiscoverytracker)) {
        return false;
    }

    selfdiscoverytracker_grade_item_update($selfdiscoverytracker);
    selfdiscoverytracker_update_grades($selfdiscoverytracker);

    return true;
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

    selfdiscoverytracker_grade_item_delete($selfdiscoverytracker);

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

/**
 * Create or update the grade item for given selfdiscoverytracker instance.
 *
 * @param stdClass $selfdiscoverytracker
 * @param mixed $grades Optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function selfdiscoverytracker_grade_item_update($selfdiscoverytracker, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname' => $selfdiscoverytracker->name, 'idnumber' => $selfdiscoverytracker->course);

    if (isset($selfdiscoverytracker->grade)) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $selfdiscoverytracker->grade;
        $params['grademin']  = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/selfdiscoverytracker', $selfdiscoverytracker->course, 'mod', 'selfdiscoverytracker', $selfdiscoverytracker->id, 0, $grades, $params);
}

/**
 * Delete grade item for given selfdiscoverytracker instance.
 *
 * @param stdClass $selfdiscoverytracker
 * @return int 0 if ok, error code otherwise
 */
function selfdiscoverytracker_grade_item_delete($selfdiscoverytracker) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/selfdiscoverytracker', $selfdiscoverytracker->course, 'mod', 'selfdiscoverytracker', $selfdiscoverytracker->id, 0, null, array('deleted'=>1));
}

/**
 * Update grades in gradebook.
 *
 * @param stdClass $selfdiscoverytracker
 * @param int $userid Specific user only, 0 means all
 * @param bool $nullifnone If true and user has no grade, set grade to null
 */
function selfdiscoverytracker_update_grades($selfdiscoverytracker, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/mod/selfdiscoverytracker/classes/tracker_helper.php');

    $grades = array();

    if ($userid) {
        $users = array($userid);
    } else {
        // This can be very slow if many users.
        // We get all users capable of being graded.
        // We need cmid. If not in object, try to find it.
        $cm = get_coursemodule_from_instance('selfdiscoverytracker', $selfdiscoverytracker->id, $selfdiscoverytracker->course);
        $context = context_module::instance($cm->id);
        $users = get_enrolled_users($context, 'mod/selfdiscoverytracker:view', 0, 'u.id');
        $users = array_keys($users);
    }

    foreach ($users as $uid) {
        $progress = \mod_selfdiscoverytracker\tracker_helper::get_all_tests_progress($uid);
        $completed_count = 0;
        foreach ($progress as $test) {
            if (!empty($test['completed'])) {
                $completed_count++;
            }
        }

        // Calculation: (completed / 5) * max_grade
        // We assume max_grade is present. Defaults to 100 if not.
        $maxgrade = $selfdiscoverytracker->grade ?? 100;
        $final_grade = ($completed_count / 5.0) * $maxgrade;

        $grades[$uid] = new stdClass();
        $grades[$uid]->userid = $uid;
        $grades[$uid]->rawgrade = $final_grade;
    }

    selfdiscoverytracker_grade_item_update($selfdiscoverytracker, $grades);
}

/**
 * Extends the settings navigation with the selfdiscoverytracker settings.
 *
 * @param settings_navigation $settingsnav
 * @param navigation_node $selfdiscoverytrackernode
 * @return void
 */
function selfdiscoverytracker_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $selfdiscoverytrackernode) {
    global $PAGE, $DB;
    
    // Only available to users who can see all grades (teachers).
    if (!has_capability('moodle/grade:viewall', $PAGE->context)) {
        return;
    }

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    // Ensure we have the instance.
    $grade_item = $DB->get_record('grade_items', array(
        'itemtype' => 'mod',
        'itemmodule' => 'selfdiscoverytracker',
        'iteminstance' => $cm->instance,
        'courseid' => $cm->course
    ));

    if ($grade_item) {
        // Link to the Single View grade report for this item.
        $url = new moodle_url('/grade/report/singleview/index.php', array(
            'id' => $cm->course,
            'item' => 'grade',
            'itemid' => $grade_item->id
        ));

        // In Moodle 4.0+ boosting theme, adding to this node creates a secondary nav tab.
        $selfdiscoverytrackernode->add(
            get_string('grades'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'selfdiscoverytrackergrades',
            new pix_icon('i/grades', '')
        );
    }
}


