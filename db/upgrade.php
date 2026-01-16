<?php
/**
 * Upgrade logic for Self Discovery Tracker module
 *
 * @package    mod_selfdiscoverytracker
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the selfdiscoverytracker module instance.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_selfdiscoverytracker_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026010801) {

        // Define field grade to be added to selfdiscoverytracker.
        $table = new xmldb_table('selfdiscoverytracker');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '100', 'introformat');

        // Conditionally launch add field grade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // selfdiscoverytracker savepoint reached.
        upgrade_mod_savepoint(true, 2026010801, 'selfdiscoverytracker');
    }

    if ($oldversion < 2026011601) {

        // Define field grade to be modified in selfdiscoverytracker.
        $table = new xmldb_table('selfdiscoverytracker');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '5', 'introformat');

        // Launch change of default for field grade.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field);
        }

        // selfdiscoverytracker savepoint reached.
        upgrade_mod_savepoint(true, 2026011601, 'selfdiscoverytracker');
    }

    return true;
}
