<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class edit_listitem_form extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        list($listitem, $maxsortorder, $action, $pagetitle, $descriptionoptions) = $this->_customdata;

        $mform->addElement('header', 'header', $pagetitle);

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'block_customlist') . ' (1, 2, 3, ...)', array('size' => 1, 'pattern' => '[0-9\-]+'));
        $mform->setDefault('sortorder', $maxsortorder);
        $mform->setType('sortorder', PARAM_INT);

        $mform->addElement('text', 'title', get_string('title', 'block_customlist'), array('size' => 45));
        $mform->addRule('title', get_string('required'), 'required', null, 'client');
        $mform->setDefault('title', '');
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('editor', 'description_editor',
            get_string('description', 'block_customlist'), null, $descriptionoptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('text', 'link', get_string('link', 'block_customlist'), array('size' => 45));
        $mform->setDefault('link', '');
        $mform->setType('link', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if ($action === 'edit')
            $btnname = get_string('save', 'block_customlist');

        if ($action === 'add')
            $btnname = get_string($action, 'block_customlist');

        $this->add_action_buttons(true, $btnname);

        $this->set_data($listitem);
    }

}
