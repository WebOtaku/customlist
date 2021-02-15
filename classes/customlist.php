<?php

namespace block_customlist;

use http\Exception;
use moodle_url;
use pix_icon;

class customlist {
    public static function listitem_view($listitem, $context, $returnurl, $baseurl, $mode, $page, $perpage) {
        global $OUTPUT;

        $html_str = '';
        $html_str .= '<div style="margin: 20px 10px">';

        if ($mode === 'full') {
            $listitemurl_params = array(
                'id' => $listitem->id,
                'mode' => 'item',
                'returnurl' => $baseurl,
            );
            $html_str .= '<a href=' . new moodle_url('/blocks/customlist/listitem_view.php', $listitemurl_params) . ' class="">
            <h4 style="color: #009688;">' . $listitem->title . '</h4>
        </a>';
        }

        if ($mode === 'item')
            $html_str .= '<h4>' . $listitem->title . '</h4>';

        $html_str .= '<div>';
        $description = file_rewrite_pluginfile_urls($listitem->description, 'pluginfile.php',
            $context->id, 'block_customlist', 'listitem', $listitem->id);
        $html_str .= '<p>'.$description.'</p>';
        $html_str .= get_string('link', 'block_customlist').': <a href="'.$listitem->link.'">'.$listitem->link.'</a>';
        $html_str .= '<hr>';
        $html_str .= '</div>';

        if (has_capability('block/customlist:addinstance', $context))
        {
            $html_str .= '<div>';

            if ($mode === 'full') {
                $edit_returnurl_params = array(
                    'mode' => 'full',
                    'page' => $page,
                    'perpage' => $perpage,
                    'returnurl' => $returnurl,
                );
                $edit_returnurl = new moodle_url('/blocks/customlist/listitem_view.php', $edit_returnurl_params);
            }

            if ($mode === 'item') {
                $edit_returnurl_params = array(
                    'id' => $listitem->id,
                    'mode' => 'item',
                    'returnurl' => $returnurl,
                );
                $edit_returnurl = new moodle_url('/blocks/customlist/listitem_view.php', $edit_returnurl_params);
            }

            $editurl_params = array(
                'id' => $listitem->id,
                'action' => 'edit',
                'returnurl' => $edit_returnurl,
            );

            $editurl = new moodle_url('/blocks/customlist/edit_listitem_view.php', $editurl_params);
            $html_str .= $OUTPUT->single_button($editurl, get_string('edit', 'block_customlist'));

            if ($mode === 'full') {
                $del_returnurl_params = array(
                    'mode' => 'full',
                    'page' => $page,
                    'perpage' => $perpage,
                    'returnurl' => $returnurl
                );
                $del_returnurl = new moodle_url('/blocks/customlist/listitem_view.php', $del_returnurl_params);
            }

            if ($mode === 'item') {
                /*$del_returnurl_params = array('redirect' => 0);
                $del_returnurl = new moodle_url('/', $del_returnurl_params);*/
                $del_returnurl = $returnurl;
            }

            $delurl_params = array(
                'id' => $listitem->id,
                'action' => 'delete',
                'returnurl' => $del_returnurl,
                'sesskey' => sesskey(),
            );
            $delurl = new moodle_url('/blocks/customlist/edit_listitem_view.php', $delurl_params);
            $html_str .= $OUTPUT->single_button($delurl, get_string('delete', 'block_customlist'));
            $html_str .= '</div>';
        }

        if ($mode === 'item') {
            $html_str .= '<div style="margin-top: .5rem;">';
            $listitemsurlparams = array(
                'mode' => 'full',
                'returnurl' => new moodle_url('/', array('redirect' => 0))
            );
            $listitemsurl = new moodle_url('/blocks/customlist/listitem_view.php', $listitemsurlparams);
            $html_str .= $OUTPUT->single_button($listitemsurl, get_string('listitemsview', 'block_customlist'));
            $html_str .= '</div>';
        }

        $html_str .= '</div>';

        return $html_str;
    }

    public static function fulllist_view($listitems, $context, $returnurl, $baseurl, $mode, $page, $perpage, $updowncount, $listitem_count) {
        global $OUTPUT, $DB;

        $html_str = '';

        // Первый способ
        /*foreach($listitems as $listitem){
            $html_str .= listitem_view($listitem, $context, $returnurl, $baseurl, $mode, $page, $perpage);
        }*/

        $urlparams = array(
            'mode' => 'full',
            'returnurl' => $returnurl,
            'sesskey' => sesskey(),
        );

        // Второй способ
        $html_str .= '<div class="accordion" id="clAccordion">';

        foreach($listitems as $listitem)
        {
            $html_str .= '
            <div class="cm-accordion-item">
                <h2 class="cm-accordion-header" id="heading'. $listitem->id .'">
                    <button class="cm-accordion-button collapsed" type="button" data-toggle="collapse" 
                            data-target="#collapse'. $listitem->id .'" aria-expanded="true" aria-controls="collapse'. $listitem->id .'">';



            if (has_capability('block/customlist:addinstance', $context)) {

                if ($updowncount > 1) {
                    $aurlparams = array(
                        'id' => $listitem->id,
                        'action' => 'up',
                    );
                    $aurl = new moodle_url('/blocks/customlist/listitem_view.php', $urlparams + $aurlparams);
                    $html_str .= $OUTPUT->action_icon($aurl, new pix_icon('t/up', 'up', 'core', array('class' => 'iconsmall')));
                } else {
                    $html_str .= $OUTPUT->spacer() . '<span style="margin-right: .5rem"></span>';
                }

                /*$changeorderurlparams = array(
                    'id' => $listitem->id,
                    'action' => 'changeorder'
                );*/
                $changeorderurl = new moodle_url('/blocks/customlist/listitem_view.php');

                $html_str .= '
                    <form class="cm-sortorder-changer" action="' . $changeorderurl . '" method="get">
                        <input type="hidden" name="id" value="' . $listitem->id . '">
                        <input type="hidden" name="action" value="changeorder">
                        <input type="hidden" name="mode" value="full">
                        <input type="hidden" name="returnurl" value="' . $returnurl . '">
                        <input type="hidden" name="sesskey" value="' . sesskey() . '">
                        <input class="cm-input cm-input-sortorder" type="text" name="neworder" value="' . $listitem->sortorder . '" pattern="[0-9\-]+">
                    </form>
                ';

                if ($updowncount < $listitem_count) {
                    $aurlparams = array(
                        'id' => $listitem->id,
                        'action' => 'down',
                    );
                    $aurl = new moodle_url('/blocks/customlist/listitem_view.php', $urlparams + $aurlparams);
                    $html_str .= $OUTPUT->action_icon($aurl, new pix_icon('t/down', 'down', 'core', array('class' => 'iconsmall')));
                } else {
                    $html_str .= $OUTPUT->spacer() . '<span style="margin-right: .5rem"></span>';
                }

                ++$updowncount;

            }

            $html_str .= $listitem->title;

            $html_str .= '
                    </button>
                </h2>
                <div id="collapse'. $listitem->id .'" class="cm-accordion-collapse collapse" aria-labelledby="heading'. $listitem->id .'" data-parent="#clAccordion">
                    <div class="cm-accordion-body">
                        '. customlist::listitem_view($listitem, $context, $returnurl, $baseurl, $mode, $page, $perpage) .'
                    </div>
                </div>
            </div>
        ';
        }

        $html_str .= '</div>';

        return $html_str;
    }

    public static function resort_instances($instances, $instanceid, $action, $offset = 1) {
        global $DB;

        if ($action === 'up' || $action === 'down')
        {
            $resorted = array_values($instances);
            $order = array_keys($instances);
            $order = array_flip($order);
            $pos = $order[$instanceid];

            if ((($action === 'up') && ($pos > 0)) ||
                (($action === 'down') && ($pos < count($instances) - 1))) {
                while ($offset > 0) {
                    if ($action === 'up')
                        $switch = $pos - 1;
                    if ($action === 'down')
                        $switch = $pos + 1;

                    $temp = $resorted[$pos];
                    $resorted[$pos] = $resorted[$switch];
                    $resorted[$switch] = $temp;

                    if ($action === 'up')
                        $pos--;
                    if ($action === 'down')
                        $pos++;

                    $offset--;
                }

                foreach ($resorted as $sortorder => $instance) {
                    if ($instance->sortorder != $sortorder) {
                        $instance->sortorder = $sortorder;
                        $DB->update_record('block_customlist', $instance);
                    }
                }
            }
        } else print_error('nosuchaction', 'block_customlist');

        return $instances;
    }

    public static function change_sortorder($instances, $instanceid, $action) {
        global $DB;

        if ($action === 'delete' || $action === 'add')
        {
            $resorted = array_values($instances);
            $order = array_keys($instances);
            $order = array_flip($order);
            $pos = $order[$instanceid];

            foreach ($resorted as $sortorder => $instance) {
                if ((($action === 'delete') && ($sortorder > $pos)) ||
                    (($action === 'add') && ($sortorder >= $pos)))
                {
                    if ($action === 'delete')
                        $instance->sortorder -= 1;
                    if ($action === 'add')
                        $instance->sortorder += 1;

                    $DB->update_record('block_customlist', $instance);
                }
            }
        } else print_error('nosuchaction', 'block_customlist');
    }
}
