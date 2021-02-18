<?php

class block_customlist extends block_base {
    public function init() {
        $this->title = get_string('defaulttitle', 'block_customlist');
    }

    // Задаёт содержимо блока
    public function get_content()
    {
        global $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $maxlistitemnum = get_config('customlist', 'maxlistitemnum');

        if(!$maxlistitemnum || $maxlistitemnum < 1)
            $maxlistitemnum = 10;

        $listitems = array();

        if ($this->check_capability('block/customlist:view')) {
            $listitems = $DB->get_records('block_customlist', null, 'sortorder', '*' , 0, $maxlistitemnum);

            if (count($listitems)) {
                $html_str = '<ul style="list-style: none; margin-left: -15px">';

                foreach ($listitems as $listitem) {
                    $html_str .= '<li>';

                    if ($this->check_capability('block/customlist:edit'))
                    {
                        $delurlparams = array(
                            'id' => $listitem->id,
                            'action' => 'delete',
                            'returnurl' => $this->page->url,
                            'sesskey' => sesskey(),
                        );
                        $delurl = new moodle_url('/blocks/customlist/edit_listitem_view.php', $delurlparams);
                        $html_str .= html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', get_string('delete', 'block_customlist')));

                        $editurlparams = array(
                            'id' => $listitem->id,
                            'action' => 'edit',
                            'returnurl' => $this->page->url,
                        );
                        $editurl = new moodle_url('/blocks/customlist/edit_listitem_view.php', $editurlparams);
                        $html_str .= ' ' . html_writer::link($editurl, $OUTPUT->pix_icon('i/edit', get_string('edit', 'block_customlist')));
                    }

                    $listitemurlparams = array(
                        'id' => $listitem->id,
                        'mode' => 'item',
                        'returnurl' => $this->page->url,
                    );
                    $listitemurl = new moodle_url('/blocks/customlist/listitem_view.php', $listitemurlparams);
                    $iconurl = new moodle_url('/blocks/customlist/list-icon.svg');
                    $html_str .= html_writer::link($listitemurl,
                        ((!$this->check_capability('block/customlist:edit'))?
                             '<img width="10" src="'.$iconurl.'">' . $OUTPUT->spacer() : '')
                          . $listitem->title
                    );
                    $html_str .= '</li>';
                }

                $html_str .= '</ul>';

                $this->content->text = $html_str;
            }
        }

        if (count($listitems) || $this->check_capability('block/customlist:edit')) {

            $html_str = '<ul style="list-style: none; margin-left: -15px;">';

            if ($this->check_capability('block/customlist:edit')) {
                $html_str .= '<li>';

                $addurlparams = array(
                    'action' => 'add',
                    'returnurl' => $this->page->url,
                );
                $addurl = new moodle_url('/blocks/customlist/edit_listitem_view.php', $addurlparams);
                $html_str .= html_writer::link($addurl, get_string('add', 'block_customlist'));

                $html_str .= '</li>';
            }

            if (count($listitems)) {
                $html_str .= '<li>';
                $listitemsurlparams = array(
                    'mode' => 'full',
                    'returnurl' => $this->page->url,
                );
                $listitemsurl = new moodle_url('/blocks/customlist/listitem_view.php', $listitemsurlparams);
                $html_str .= html_writer::link($listitemsurl, get_string('listitemsview', 'block_customlist'));
                $html_str .= '</li>';
            }

            $html_str .= '</ul>';

            $this->content->footer = $html_str;
        }

        return $this->content;
    }

    // Глобальная конфигурация
    function has_config() {
        return true;
    }

    /**
     * Проверяет есть ли право $capability у текущего пользователя
     * @return bool да/нет
     * */
    private function check_capability($capability)
    {
        $context = context_system::instance();
        return has_capability($capability, $context);
    }

    // Позволяет добавлять несколько таких блоков в один курс
    public function instance_allow_multiple() {
        return false;
    }

    public function applicable_formats() {
        return array(
            'all' => false,
            'site' => true,
            'site-index' => true,
            'my' => true
        );
    }
}