<?php
/**
 * Dashboard renderable class for Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_selfdiscoverytracker\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;
use moodle_url;

class dashboard implements renderable, templatable {
    
    protected $course;
    protected $cm;
    protected $progress_data;
    protected $is_teacher;

    public function __construct($course, $cm, $progress_data, $is_teacher = false) {
        $this->course = $course;
        $this->cm = $cm;
        $this->progress_data = $progress_data;
        $this->is_teacher = $is_teacher;
    }

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->courseid = $this->course->id;
        $data->modid = $this->cm->id;
        $data->is_teacher = $this->is_teacher;
        
        if ($this->is_teacher) {
            $data->title = get_string('teacher_view_title', 'mod_selfdiscoverytracker');
            $data->block_url = (new moodle_url('/blocks/student_path/admin_view.php', ['cid' => $this->course->id]))->out(false);
            $data->str_view_full_report = get_string('view_full_report', 'mod_selfdiscoverytracker');
            $data->str_view_full_report_desc = get_string('view_full_report_desc', 'mod_selfdiscoverytracker');
            return $data;
        }

        $data->title = get_string('dashboard_title', 'mod_selfdiscoverytracker');
        $data->subtitle = get_string('dashboard_subtitle', 'mod_selfdiscoverytracker');
        
        $cards = [];
        $step = 1;
        foreach ($this->progress_data as $key => $info) {
            $is_completed = !empty($info['completed']);
            $is_started = $info['answered'] > 0;
            
            $answeredparams = (object) [
                'answered' => (int) $info['answered'],
                'total' => (int) $info['total'],
            ];

            // Determine button text and URL
            $action_label = '';
            $action_url = '';
            $is_sidebar_result = in_array($key, ['learning_style', 'personality']);

            if ($is_completed) {
                if ($is_sidebar_result) {
                    $action_label = get_string('action_view_sidebar', 'mod_selfdiscoverytracker');
                    $action_url = $this->get_sidebar_results_url($key, $this->course->id);
                } else if ($key === 'student_path') {
                    $action_label = get_string('action_view_map', 'mod_selfdiscoverytracker');
                    $action_url = $this->get_test_url($key, $this->course->id);
                } else {
                    $action_label = get_string('action_view_results', 'mod_selfdiscoverytracker');
                    $action_url = $this->get_test_url($key, $this->course->id);
                }
            } else {
                if (!empty($info['unsubmitted_alert'])) {
                    $action_label = get_string('action_finalize', 'mod_selfdiscoverytracker');
                    $action_url = $this->get_finalize_url($key, $this->course->id);
                } else if ($key === 'student_path' && !$is_started) {
                    $action_label = get_string('action_start_map', 'mod_selfdiscoverytracker');
                } else {
                    $action_label = $is_started ? get_string('action_continue', 'mod_selfdiscoverytracker') : get_string('action_start', 'mod_selfdiscoverytracker');
                }
                if (empty($action_url)) {
                    $action_url = $this->get_test_url($key, $this->course->id);
                }
            }

            $card = [
                'key' => $key,
                'key_class' => str_replace('_', '-', $key),
                'step' => $step++,
                'title' => get_string('test_' . $key, 'mod_selfdiscoverytracker'),
                'status_class' => $info['status'],
                'status_label' => get_string('status_' . str_replace('-', '_', $info['status']), 'mod_selfdiscoverytracker'),
                'percentage' => $info['percentage'],
                'answered' => $info['answered'],
                'total' => $info['total'],
                'answeredtext' => get_string('questions_answered', 'mod_selfdiscoverytracker', $answeredparams),
                'unsubmitted_alert' => $info['unsubmitted_alert'],
                'icon' => $this->get_icon_url($key),
                'action_label' => $action_label,
                'action_url' => $action_url,
                'is_completed' => $is_completed,
                'is_locked' => false // Could implement locking logic here if needed
            ];
            $cards[] = $card;
        }
        $data->cards = $cards;
        
        // Global Progress
        $completed_count = 0;
        foreach ($this->progress_data as $p) {
            if (!empty($p['completed'])) $completed_count++;
        }
        $data->global_percentage = (int) round(($completed_count / 5) * 100);
        $data->all_completed = ($completed_count === 5);

        return $data;
    }

    private function get_icon_url($key) {
        global $CFG;
        if ($key === 'student_path') {
            return $CFG->wwwroot . '/mod/selfdiscoverytracker/pix/icon.svg';
        }
        return $CFG->wwwroot . '/mod/selfdiscoverytracker/pix/' . $key . '_icon.svg';
    }

    private function get_test_url($key, $courseid) {
        switch ($key) {
            case 'student_path':
                return (new moodle_url('/blocks/student_path/view.php', ['cid' => $courseid]))->out(false);
            case 'chaside':
                return (new moodle_url('/blocks/chaside/view.php', ['courseid' => $courseid]))->out(false);
            case 'learning_style':
                return (new moodle_url('/blocks/learning_style/view.php', ['cid' => $courseid]))->out(false);
            case 'personality':
                return (new moodle_url('/blocks/personality_test/view.php', ['cid' => $courseid]))->out(false);
            case 'tmms24':
                return (new moodle_url('/blocks/tmms_24/view.php', ['cid' => $courseid]))->out(false);
            default:
                return '#';
        }
    }

    private function get_finalize_url($key, $courseid) {
        switch ($key) {
            case 'chaside':
                return (new moodle_url('/blocks/chaside/view.php', ['courseid' => $courseid, 'page' => 10, 'scroll_to_finish' => 1]))->out(false);
            case 'learning_style':
                return (new moodle_url('/blocks/learning_style/view.php', ['cid' => $courseid, 'page' => 4, 'scroll_to_finish' => 1]))->out(false);
            case 'personality':
                return (new moodle_url('/blocks/personality_test/view.php', ['cid' => $courseid, 'page' => 8, 'scroll_to_finish' => 1]))->out(false);
            case 'tmms24':
                return (new moodle_url('/blocks/tmms_24/view.php', ['cid' => $courseid, 'scroll_to_finish' => 1]))->out(false);
            default:
                return $this->get_test_url($key, $courseid);
        }
    }

    private function get_sidebar_results_url(string $key, int $courseid): string {
        $blockname = null;
        if ($key === 'learning_style') {
            $blockname = 'learning_style';
        } else if ($key === 'personality') {
            $blockname = 'personality_test';
        }

        $courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
        if (!$blockname) {
            return $courseurl->out(false);
        }

        $blockinstanceid = $this->get_course_block_instance_id($courseid, $blockname);
        if (!$blockinstanceid) {
            return $courseurl->out(false);
        }

        return $courseurl->out(false) . '#inst' . $blockinstanceid;
    }

    private function get_course_block_instance_id(int $courseid, string $blockname): ?int {
        global $DB;

        $contextid = \context_course::instance($courseid)->id;
        $record = $DB->get_record('block_instances', [
            'parentcontextid' => $contextid,
            'blockname' => $blockname,
        ], 'id', IGNORE_MISSING);

        if (!$record) {
            return null;
        }

        return (int)$record->id;
    }
}
