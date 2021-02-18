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

$available_actions = array('add', 'edit', 'delete');

if (!in_array($action, $available_actions))
    redirect($returnurl);

if ($action === 'delete' && confirm_sesskey())
{
    if ($DB->record_exists('block_customlist', array('id' => $id)))
    {
        $instances = $DB->get_records('block_customlist', null, 'sortorder');

        $DB->delete_records('block_customlist', array('id' => $id));
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'block_customlist', 'listitem', $id);

        customlist::recalc_sortorder($instances, $id, $action);
    }

    redirect($returnurl);
}

$pageurl = '/blocks/customlist/edit_listitem_view.php';
$pageparams = array();

if ($action === 'edit')
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
        'page' => 0,
        'returnurl' => $returnurl
    );

    if ((new moodle_url($returnurl))->get_param('perpage'))
        $add_returnurl_params['perpage'] = (new moodle_url($returnurl))->get_param('perpage');

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

if ($action === 'edit') {
    if (!$listitem = $DB->get_record('block_customlist', array('id' => $id))) {
        redirect($returnurl);
    }
}

$descriptionoptions = array(
    'trusttext' => true,
    'subdirs' => file_area_contains_subdirs($context, 'block_customlist', 'listitem', $listitem->id),
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'context' => $context
);

$listitem = file_prepare_standard_editor($listitem, 'description', $descriptionoptions, $context, 'block_customlist', 'listitem', $listitem->id);

$listitem_clone = $listitem;

if ($action === 'edit') {
    $listitem_clone = clone $listitem;
    $listitem_clone->sortorder += 1;
}

$maxsortorder = 0;

if ($action === 'add') {
    $maxsortorder = $DB->get_field_sql('SELECT MAX(sortorder) FROM {block_customlist}');
    $maxsortorder = ($maxsortorder === null)? 0 : ++$maxsortorder;
    $maxsortorder += 1;
}

$edit_listitem_form = new edit_listitem_form($baseurl,
    array($listitem_clone, $maxsortorder, $action, $pagetitle, $descriptionoptions));

if($edit_listitem_form->is_cancelled()) {
    redirect($returnurl);
}
else if ($new_listitem = $edit_listitem_form->get_data())
{
    if ($action === 'edit') {
        $new_listitem = file_postupdate_standard_editor($new_listitem, 'description', $descriptionoptions, $context, 'block_customlist', 'listitem', $new_listitem->id);
        $new_listitem->timemodified = time();

        $returnurl_params = array(
            'id' => $new_listitem->id,
            'action' => 'changeorder',
            'neworder' => $new_listitem->sortorder,
            'sesskey' => sesskey()
        );

        $returnurl = new moodle_url($returnurl);

        if ($param_mode = $returnurl->get_param('mode'))
            $returnurl_params['mode'] = $param_mode;
        else
            $returnurl_params['mode'] = 'full';

        if ($param_returnurl = $returnurl->get_param('returnurl'))
            $returnurl_params['returnurl'] = $param_returnurl;
        else
            $returnurl_params['returnurl'] = $returnurl;

        $returnurl = new moodle_url('/blocks/customlist/listitem_view.php', $returnurl_params);

        $new_listitem->sortorder = $listitem->sortorder;

        // TODO: use api function change_sortorder

        $DB->update_record('block_customlist', $new_listitem);
    }

    if ($action === 'add') {
        $new_listitem->description = '';
        $new_listitem->descriptionformat = FORMAT_HTML;
        $new_listitem->timecreated = time();
        $new_listitem->timemodified = time();

        // TODO: $returnurl->param('page', ); same as for edit

        $new_listitem->sortorder -= 1;

        if ($new_listitem->sortorder < 0) $new_listitem->sortorder = 0;
        if ($new_listitem->sortorder > $maxsortorder) $new_listitem->sortorder = $maxsortorder + 1;

        if ($prev_listitem = $DB->get_record('block_customlist', array('sortorder' => $new_listitem->sortorder)))
        {
            $instances = $DB->get_records('block_customlist', null, 'sortorder');
            customlist::recalc_sortorder($instances, $prev_listitem->id, $action);
        }

        $new_listitem->id = $DB->insert_record('block_customlist', $new_listitem);
        $new_listitem = file_postupdate_standard_editor($new_listitem, 'description', $descriptionoptions, $context, 'block_customlist', 'listitem', $new_listitem->id);

        $DB->update_record('block_customlist', $new_listitem);
    }

    redirect($returnurl);
} else {
    echo $OUTPUT->header();
    $edit_listitem_form->display();
    echo $OUTPUT->footer();
}
