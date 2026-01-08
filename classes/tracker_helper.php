<?php
/**
 * Helper class for Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_selfdiscoverytracker;

defined('MOODLE_INTERNAL') || die();

class tracker_helper {

    /**
     * Get the progress of all tests for a user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_all_tests_progress($userid) {
        return [
            'learning_style' => self::get_learning_style_progress($userid),
            'personality' => self::get_personality_progress($userid),
            'chaside' => self::get_chaside_progress($userid),
            'tmms24' => self::get_tmms24_progress($userid),
            'student_path' => self::get_student_path_progress($userid),
        ];
    }

    /**
     * Check if all tests are completed.
     *
     * @param int $userid
     * @return bool
     */
    public static function are_all_tests_completed($userid) {
        $progress = self::get_all_tests_progress($userid);
        foreach ($progress as $test) {
            if (!$test['completed']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check completion state and update if necessary.
     *
     * @param \cm_info $cm
     * @param int $userid
     * @return void
     */
    public static function check_and_update_completion($cm, $userid) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        // This check runs frequently on course view, so we must be efficient.
        static $checked_instances = [];
        $cache_key = $cm->id . '_' . $userid;

        if (isset($checked_instances[$cache_key])) {
            return;
        }
        $checked_instances[$cache_key] = true;

        $completion = new \completion_info(\get_course($cm->course));
        if (!$completion->is_enabled($cm)) {
            return;
        }

        // We need to check if 'completionalltests' is enabled for this instance.
        // cm_info should have custom data but we might need to query DB if not available.
        // We use a light query.
        $tracker = $DB->get_record('selfdiscoverytracker', ['id' => $cm->instance], 'completionalltests', IGNORE_MISSING);
        if (!$tracker || empty($tracker->completionalltests)) {
            return;
        }

        $all_completed = self::are_all_tests_completed($userid);
        
        // Get current completion state effectively.
        // We use get_data with $wholecourse = false for speed.
        $current_state = $completion->get_data($cm, false, $userid);
        $is_marked_complete = ($current_state->completionstate == COMPLETION_COMPLETE);

        // Update if state mismatches.
        if ($all_completed && !$is_marked_complete) {
              $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
        } elseif (!$all_completed && $is_marked_complete) {
              $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
        }
    }

    private static function get_chaside_progress($userid) {
        global $DB;
        $record = $DB->get_record('block_chaside_responses', ['userid' => $userid]);
        $total = 98;
        $answered = 0;
        $completed = false;

        if ($record) {
            for ($i = 1; $i <= $total; $i++) {
                if (isset($record->{'q'.$i}) && $record->{'q'.$i} !== null) {
                    $answered++;
                }
            }
            if ($record->is_completed) {
                $completed = true;
            }
        }

        return self::format_progress($completed, $answered, $total);
    }

    private static function get_learning_style_progress($userid) {
        global $DB;
        $record = $DB->get_record('learning_style', ['user' => $userid]);
        $total = 44;
        $answered = 0;
        $completed = false;

        if ($record) {
            for ($i = 1; $i <= $total; $i++) {
                if (isset($record->{'q'.$i}) && $record->{'q'.$i} !== null) {
                    $answered++;
                }
            }
            if ($record->is_completed) {
                $completed = true;
            }
        }

        return self::format_progress($completed, $answered, $total);
    }

    private static function get_personality_progress($userid) {
        global $DB;
        $record = $DB->get_record('personality_test', ['user' => $userid]);
        $total = 72;
        $answered = 0;
        $completed = false;

        if ($record) {
            for ($i = 1; $i <= $total; $i++) {
                if (isset($record->{'q'.$i}) && $record->{'q'.$i} !== null) {
                    $answered++;
                }
            }
            if ($record->is_completed) {
                $completed = true;
            }
        }

        // Some deployments mark the test as completed but do not persist per-question answers.
        // In that case, show full completion progress.
        if ($completed && $answered === 0) {
            $answered = $total;
        }

        return self::format_progress($completed, $answered, $total);
    }

    private static function get_tmms24_progress($userid) {
        global $DB;
        $record = $DB->get_record('tmms_24', ['user' => $userid]);
        $total = 24;
        $answered = 0;
        $completed = false;

        if ($record) {
            for ($i = 1; $i <= $total; $i++) {
                if (isset($record->{'item'.$i}) && $record->{'item'.$i} !== null) {
                    $answered++;
                }
            }
            if ($record->is_completed) {
                $completed = true;
            }
        }

        return self::format_progress($completed, $answered, $total);
    }

    private static function get_student_path_progress($userid) {
        global $DB;
        $record = $DB->get_record('block_student_path', ['user' => $userid]);
        
        $fields_to_check = [
            'name', 'program', 'admission_year', 'admission_semester', 'email', 'code',
            'personality_strengths', 'personality_weaknesses', 
            'vocational_areas', 'vocational_areas_secondary', 'vocational_description',
            'emotional_skills_level',
            'goal_short_term', 'goal_medium_term', 'goal_long_term',
            'action_short_term', 'action_medium_term', 'action_long_term'
        ];
        
        $total = count($fields_to_check);
        $answered = 0;
        $completed = false;

        if ($record) {
            foreach ($fields_to_check as $field) {
                if (!empty($record->$field)) {
                    $answered++;
                }
            }
            if ((isset($record->is_completed) && $record->is_completed == 1) || $answered >= $total) {
                $completed = true;
            }
        }

        return self::format_progress($completed, $answered, $total);
    }

    private static function format_progress($completed, $answered, $total) {
        $status = 'not-started';
        if ($completed) {
            $status = 'completed';
        } elseif ($answered > 0) {
            $status = 'in-progress';
        }

        // Check for "All questions answered but not submitted"
        $unsubmitted_alert = (!$completed && $answered >= $total);

        return [
            'completed' => $completed,
            'answered' => $answered,
            'total' => $total,
            'percentage' => ($total > 0) ? round(($answered / $total) * 100) : 0,
            'status' => $status,
            'unsubmitted_alert' => $unsubmitted_alert
        ];
    }
}
