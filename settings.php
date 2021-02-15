<?php
$settings->add(new admin_setting_heading(
    'headerconfig',
    get_string('headerconfig', 'block_customlist'),
    ''
));

$settings->add(new admin_setting_configtext(
    'customlist/maxlistitemnum',
    get_string('maxlistitemnum', 'block_customlist'),
    '', 10, PARAM_INT)
);