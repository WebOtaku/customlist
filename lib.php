<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');

/**
 * Serves the userportfolio attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function block_customlist_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $DB, $USER;


    if ($filearea !== 'listitem') {
        return false;
    }

    require_login();

    $listitemid = (int)array_shift($args);

    if ($filearea === 'listitem'){
        if (!$listitem = $DB->get_record('block_customlist', array('id' => $listitemid))) {
            return false;
        }
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);

    if ($filearea === 'listitem') {
        $fullpath = "/$context->id/block_customlist/listitem/$listitemid/$relativepath";
    }

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 360, 0, false);
}