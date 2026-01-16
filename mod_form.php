<?php
/**
 * Module form definition for Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_selfdiscoverytracker_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setDefault('name', get_string('default_activity_name', 'mod_selfdiscoverytracker'));

        $this->standard_intro_elements();

        $this->standard_grading_coursemodule_elements();

        // Customization: Restrict grading to points only.
        $mform = $this->_form;
        if ($mform->elementExists('grade')) {
            $gradeitem = $mform->getElement('grade');
            if ($gradeitem instanceof MoodleQuickForm_group) {
                $elements = $gradeitem->getElements();
                foreach ($elements as $el) {
                    if ($el->getName() == 'modgrade_type') {
                        // Remove all existing options to ensure only 'point' is available
                        // regardless of what options Moodle core or plugins might add.
                        $el->removeOptions();
                        $el->addOption(get_string('modgradetypepoint', 'grades'), 'point');
                    }
                }
            }
        }

        $this->standard_coursemodule_elements();

        // Remove "Student must receive a grade" completion condition.
        // We rely on "completionalltests" instead, as grades are progressive.
        if ($mform->elementExists('completionusegrade')) {
            $mform->removeElement('completionusegrade');
        }

        // "Student must receive a passing grade" can only be enabled
        // if "Student must complete all 5 self-knowledge tests" is NOT enabled.
        if ($mform->elementExists('completionpassgrade')) {
             $mform->disabledIf('completionpassgrade', 'completionalltests', 'checked');

             // Ensure it is unchecked visually when disabled to avoid confusion.
             global $PAGE;
             $PAGE->requires->js_amd_inline("
                require(['jquery'], function($) {
                    $('input[name=\"completionalltests\"]').change(function() {
                        if ($(this).is(':checked')) {
                            $('input[name=\"completionpassgrade\"]').prop('checked', false);
                        }
                    });
                });
             ");
        }

        // Default completion settings (teacher can edit).
        $mform->setDefault('completion', COMPLETION_TRACKING_AUTOMATIC);
        $mform->setDefault('completionview', 0);
        $mform->setDefault('completionalltests', 1);

        $this->add_action_buttons();
    }

    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        // For new instances, ensure completion defaults to automatic (conditions-based),
        // not manual, even if core provides other defaults via set_data().
        $isnew = empty($this->_instance);
        if ($isnew) {
            $default_values['completion'] = COMPLETION_TRACKING_AUTOMATIC;
            $default_values['completionview'] = 0;
            $default_values['completionalltests'] = 1;
            
            // Set default grading to 0-5 points
            $default_values['grade'] = 5;
            $default_values['gradecat'] = 0;
        }
    }

    public function add_completion_rules() {
        $mform =& $this->_form;
        $mform->addElement('checkbox', 'completionalltests', get_string('completionalltests', 'mod_selfdiscoverytracker'), get_string('completionalltests_desc', 'mod_selfdiscoverytracker'));
        return array('completionalltests');
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionalltests']);
    }
}
