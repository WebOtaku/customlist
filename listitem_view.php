<?php

use block_customlist\customlist;

require_once('../../config.php');

require_once($CFG->libdir.'/blocklib.php');

$id = optional_param('id', null, PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$mode = required_param('mode', PARAM_TEXT);
$action = optional_param('action', '', PARAM_TEXT);
$updowncount = optional_param('updowncount', 1, PARAM_INT);
$neworder = optional_param('neworder', 0, PARAM_INT);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('customlist', 'maxlistitemnum'), PARAM_INT);

$context = context_system::instance();
$site = get_site();

require_login();

if (!has_capability('block/customlist:view', $context)) {
    print_error('nopermissions', 'error', '', 'block/customlist:addinstance');
}

$PAGE->set_context($context);

$available_modes = array('full', 'item');

if (!in_array($mode, $available_modes))
    $mode = 'full';

$available_actions = array('up', 'down', 'changeorder');

if ($action && !in_array($action, $available_actions))
    redirect($returnurl);

$pageurl = '/blocks/customlist/listitem_view.php';
$pageparams = array();

if ($mode === 'item') {
    $pageparams['id'] = $id;
    $pagetitle = get_string('listitemview', 'block_customlist');
}

$listitem_count = 0;

if ($mode === 'full') {
    $listitem_count = $DB->count_records('block_customlist');

    if (!$listitem_count)
        redirect($returnurl);

    if ($perpage < 1) $perpage = 1;

    if ($page < 0) $page = 0;
    else if ($page > (ceil($listitem_count / $perpage) - 1))
        $page = ceil($listitem_count / $perpage) - 1;

    $listitems = $DB->get_records('block_customlist', null, 'sortorder', '*', $page*$perpage, $perpage);

    $pagetitle = get_string('listitemsview', 'block_customlist');

    $pageparams['page'] = $page;
    $pageparams['perpage'] = $perpage;
    $updowncount = $page * $perpage + 1;
    $pageparams['updowncount'] = $updowncount;
}

$pageparams['mode'] = $mode;
$pageparams['returnurl'] = $returnurl;

$baseurl = new moodle_url($pageurl, $pageparams);

// Изменение порядка сортировки элементов (поле sortorder)
if ($action and confirm_sesskey()) {
    // TODO: put this code in change_sortorder function
    // TODO: use api function change_sortorder
    if ($DB->record_exists('block_customlist', array('id' => $id))) {
        if ($action === 'changeorder')
        {
            $listitem = $DB->get_record('block_customlist', array('id' => $id));

            $maxsortorder = $DB->get_field_sql('SELECT MAX(sortorder) FROM {block_customlist}');
            $neworder -= 1;

            if ($neworder < 0) $neworder = 0;
            if ($neworder > $maxsortorder) $neworder = $maxsortorder;

            if ((int)$listitem->sortorder !== $neworder)
            {
                if ($listitem->sortorder > $neworder)
                    $action = 'up';
                if ($listitem->sortorder < $neworder)
                    $action = 'down';

                $offset = abs($listitem->sortorder - $neworder);
            } else {
                if ($mode === 'full')
                    $baseurl->param('page', floor($listitem->sortorder / $perpage));

                redirect($baseurl);
            }
        } else $offset = 1;

        if ($action === 'up' || $action === 'down') {
            $instances = $DB->get_records('block_customlist', null, 'sortorder');
            $instanceid = $id;

            $instances = customlist::resort_instances($instances, $instanceid, $action, $offset);

            if ($mode === 'full')
                $baseurl->param('page', floor($instances[$instanceid]->sortorder / $perpage));

            redirect($baseurl);
        }
    }
}

$PAGE->set_url($baseurl);

$PAGE->set_title($pagetitle);

$PAGE->set_heading($pagetitle);
$PAGE->set_pagelayout('standard');

$PAGE->navbar->ignore_active();
$backnode = $PAGE->navbar->add(get_string('back', 'block_customlist'), $returnurl);

$modpageparams = $pageparams;
$modpageparams['page'] = 0;
$modbaseurl = new moodle_url($pageurl, $modpageparams);
$basenode = $backnode->add($pagetitle, $modbaseurl);
$basenode->make_active();

if ($mode === 'full') {
    if (has_capability('block/customlist:addinstance', $context)) {

        $add_returnurl_params = array(
            'mode' => 'full',
            'page' => 0,
            'perpage' => $perpage,
            'returnurl' => $returnurl
        );
        $add_returnurl = new moodle_url('/blocks/customlist/listitem_view.php', $add_returnurl_params);

        $settingsnode = $PAGE->settingsnav->add(get_string('listitemsview', 'block_customlist'), $add_returnurl);

        $addurlparams = array(
            'action' => 'add',
            'returnurl' => $add_returnurl,
        );
        $addurl = new moodle_url('/blocks/customlist/edit_listitem_view.php', $addurlparams);
        $addnode = $settingsnode->add(get_string('addlistitem', 'block_customlist'), $addurl);
        $settingsnode->make_active();
    }
}

if ($mode === 'item') {
    if (!$listitem = $DB->get_record('block_customlist', array('id' => $id))) {
        redirect($returnurl);
    }
}

// Для случая когда на текущей странице нет элементов
/*if ($listitem_count && !count($listitems)) {
    $baseurl->param('page', --$page);
    redirect($baseurl);
}*/

echo $OUTPUT->header();

echo '<link rel="stylesheet" href="main.css">';

if ($mode === 'item') {
    echo customlist::listitem_view($listitem, $context, $returnurl, $baseurl, $mode, $page, $perpage);
}

if ($mode === 'full') {
    echo $OUTPUT->paging_bar($listitem_count, $page, $perpage, $baseurl);

    echo customlist::fulllist_view($listitems, $context, $returnurl, $baseurl,
        $mode, $updowncount, $listitem_count);

    echo $OUTPUT->paging_bar($listitem_count, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
