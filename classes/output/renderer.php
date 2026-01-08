<?php
/**
 * Renderer class for Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_selfdiscoverytracker\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    public function render_dashboard(dashboard $dashboard) {
           return $this->render_from_template('mod_selfdiscoverytracker/dashboard', $dashboard->export_for_template($this));
    }
}
