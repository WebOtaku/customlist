<?php
function xmldb_block_customlist_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    $result = TRUE;

    if ($oldversion < 2021100214) {

        // Define table block_customlist to be created.
        $table = new xmldb_table('block_customlist');

        // Adding fields to table block_customlist.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('title', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('descriptiontrust', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('link', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_customlist.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_customlist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Customlist savepoint reached.
        upgrade_block_savepoint(true, 2021100214, 'customlist');
    }

    return $result;
}
?>