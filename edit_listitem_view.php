<?php

use block_customlist\customlist;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('edit_listitem_form.php');

$id = optional_param('id', null, PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$action = required_param('action', PARAM_TEXT);

$context = context_system::instance();
$site = get_site();

require_login();

if (!has_capability('block/customlist:addinstance', $context)) {
    print_error('nopermissions', 'error', '', 'block/customlist:addinstance');
}

$PAGE->set_context($context);

if ($action === 'delete' && confirm_sesskey() && $id)
{
    if ($DB->record_exists('block_customlist', array('id' => $id)))
    {
        $instances = $DB->get_records('block_customlist', null, 'sortorder');

        $DB->delete_records('block_customlist', array('id' => $id));
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'block_customlist', 'listitem', $id);

        customlist::change_sortorder($instances, $id, $action);
    }

    redirect($returnurl);
}

$pageurl = '/blocks/customlist/edit_listitem_view.php';
$pageparams = array();

if ($action === 'edit' && $id)
    $pageparams['id'] = $id;

$pageparams['action'] = $action;
$pageparams['returnurl'] = $returnurl;

$baseurl = new moodle_url($pageurl, $pageparams);

switch ($action) {
    case 'add':
        $pagetitle = get_string('addlistitem', 'block_customlist');
        break;
    case 'edit':
        $pagetitle = get_string('editlistitem', 'block_customlist');
        break;
    case 'delete':
        $pagetitle = get_string('dellistitem', 'block_customlist');
        break;
}

$PAGE->set_url($baseurl);
$PAGE->set_title($pagetitle);

$PAGE->set_heading($pagetitle);
$PAGE->set_pagelayout('standard');

$PAGE->navbar->ignore_active();
$backnode = $PAGE->navbar->add(get_string('back', 'block_customlist'), $returnurl);
$basenode = $backnode->add($pagetitle, $baseurl);
$basenode->make_active();

if ($action === 'add') {
    $add_returnurl_params = array(
        'mode' => 'full',
        /*'page' => ceil($listitem_count / $perpage) - 1,*/
        'page' => 0,
        'perpage' => (new moodle_url($returnurl))->get_param('perpage'),
        'returnurl' => new moodle_url('/', array('redirect' => 0))
    );
    $add_returnurl = new moodle_url('/blocks/customlist/listitem_view.php', $add_returnurl_params);

    $settingsnode = $PAGE->settingsnav->add(get_string('listitemsview', 'block_customlist'), $add_returnurl);

    $addurlparams = array(
        'action' => 'add',
        'returnurl' => $add_returnurl,
    );
    $addurl = new moodle_url('/blocks/customlist/edit_listitem_view.php', $addurlparams);
    $addnode = $settingsnode->add(get_string('addlistitem', 'block_customlist'), $addurl);
    $addnode->make_active();
}

$listitem = new stdClass();
$listitem->id = null;

if ($action === 'edit' && $id) {
    if (!$listitem = $DB->get_record('block_customlist', array('id' => $id))) {
        print_error('invalidentry');
    }
}

$maxsortorder = $DB->get_field_sql('SELECT MAX(sortorder) FROM {block_customlist}');
$maxsortorder = ($maxsortorder === null)? 0 : ++$maxsortorder;

$descriptionoptions = array(
    'trusttext' => true,
    'subdirs' => file_area_contains_subdirs($context, 'block_customlist', 'listitem', $listitem->id),
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'context' => $context
);

$listitem = file_prepare_standard_editor($listitem, 'description', $descriptionoptions, $context, 'block_customlist', 'listitem', $listitem->id);

$edit_listitem_form = new edit_listitem_form($baseurl,
    array($listitem, $maxsortorder, $action, $pagetitle, $descriptionoptions));

if($edit_listitem_form->is_cancelled()) {
    redirect($returnurl);
} else if ($listitem = $edit_listitem_form->get_data()) {
    require_capability('block/customlist:addinstance', $context);

    if ($action === 'edit' && $listitem->id) {
        $listitem = file_postupdate_standard_editor($listitem, 'description', $descriptionoptions, $context, 'block_customlist', 'listitem', $listitem->id);
        $listitem->timemodified = time();
    }
    else {
        $listitem->description = '';
        $listitem->descriptionformat = FORMAT_HTML;
        $listitem->timecreated = time();
        $listitem->timemodified = time();

        //$returnurl->param('page', );

        $maxsortorder = $DB->get_field_sql('SELECT MAX(sortorder) FROM {block_customlist}');
        if ($listitem->sortorder < 0) $listitem->sortorder = 0;
        if ($listitem->sortorder > $maxsortorder) $listitem->sortorder = $maxsortorder + 1;

        if ($prev_listitem = $DB->get_record('block_customlist', array('sortorder' => $listitem->sortorder)))
        {
            $instances = $DB->get_records('block_customlist', null, 'sortorder');
            customlist::change_sortorder($instances, $prev_listitem->id, $action);
        }

        $listitem->id = $DB->insert_record('block_customlist', $listitem);
        $listitem = file_postupdate_standard_editor($listitem, 'description', $descriptionoptions, $context, 'block_customlist', 'listitem', $listitem->id);
    }

    $DB->update_record('block_customlist', $listitem);

    redirect($returnurl);
} else {
    echo $OUTPUT->header();
    $edit_listitem_form->display();
    echo $OUTPUT->footer();
}
