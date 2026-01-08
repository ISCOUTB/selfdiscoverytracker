<?php
/**
 * View page for the Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/selfdiscoverytracker/lib.php');
require_once($CFG->dirroot.'/mod/selfdiscoverytracker/classes/tracker_helper.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$n  = optional_param('n', 0, PARAM_INT);  // Instance ID

if ($id) {
    $cm         = get_coursemodule_from_id('selfdiscoverytracker', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $selfdiscoverytracker  = $DB->get_record('selfdiscoverytracker', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $selfdiscoverytracker  = $DB->get_record('selfdiscoverytracker', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $selfdiscoverytracker->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('selfdiscoverytracker', $selfdiscoverytracker->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Determine view type (Student or Teacher).
$coursecontext = context_course::instance($course->id);
$is_teacher = has_capability('mod/selfdiscoverytracker:addinstance', $context) ||
    has_capability('moodle/course:update', $coursecontext) ||
    has_capability('moodle/course:manageactivities', $coursecontext) ||
    has_capability('moodle/course:viewhiddenactivities', $coursecontext) ||
    has_capability('moodle/site:config', $coursecontext);

// Trigger module viewed event
$event = \mod_selfdiscoverytracker\event\course_module_viewed::create(array(
    'objectid' => $selfdiscoverytracker->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('selfdiscoverytracker', $selfdiscoverytracker);
$event->trigger();

// Check completion (students only).
$completion = new completion_info($course);
if (!$is_teacher && $completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);

    // Check custom completion rule "All tests completed".
    \mod_selfdiscoverytracker\tracker_helper::check_and_update_completion($cm, $USER->id);
}

$PAGE->set_url('/mod/selfdiscoverytracker/view.php', array('id' => $cm->id));

// Populate standard activity context (needed for activity header behaviour).
$PAGE->set_cm($cm, $course, $selfdiscoverytracker);

$PAGE->set_title(format_string($selfdiscoverytracker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Teachers should not see personal completion prompts in this activity view.
if ($is_teacher) {
    $PAGE->add_body_class('sdt-staff');
    if (isset($PAGE->activityheader)) {
        $PAGE->activityheader->set_hidecompletion(true);
    }
}

$progress_data = [];
if (!$is_teacher) {
    $progress_data = \mod_selfdiscoverytracker\tracker_helper::get_all_tests_progress($USER->id);
    
    // Check if all completed to update completion state if using custom rules
    // Note: Standard 'view' completion is handled by set_module_viewed above.
    // If we want to enforce "All tests completed" as a condition, we might need to use custom completion.
    // But for now, let's just ensure the UI reflects the state.
}

$output = $PAGE->get_renderer('mod_selfdiscoverytracker');
$dashboard = new \mod_selfdiscoverytracker\output\dashboard($course, $cm, $progress_data, $is_teacher);

echo $output->header();
echo $output->render($dashboard);
echo $output->footer();
