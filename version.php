<?php
/**
 * Self Discovery Tracker Module First Update
 * Version 1.0.0 - Production Ready
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 Jairo Serrano, Yuranis Henriquez, Isaac Sanchez, Santiago Orejuela, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2026010800;
$plugin->requires = 2022041900; // Moodle 4.0+
$plugin->component = 'mod_selfdiscoverytracker';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->dependencies = [
    'block_student_path' => 2026010800,
    'block_chaside' => 2026010800,
    'block_learning_style' => 2026010800,
    'block_personality_test' => 2026010800,
    'block_tmms_24' => 2026010800,
];
