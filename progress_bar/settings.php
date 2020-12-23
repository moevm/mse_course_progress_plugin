<?php

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/blocks/progress_bar/lib.php');

if ($ADMIN->fulltree){
    $fonts = array(10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15, 16 => 16);
    $settings->add(new admin_setting_configselect('block_progress_bar/font', 'Размер шрифта', '',  DEFAULT_PROGRESSBAR_FONT, $fonts));

    $countTaskInRow = array(10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15, 16 => 16, 17 => 17, 18 => 18, 19 => 19, 20 => 20);
    $settings->add(new admin_setting_configselect('block_progress_bar/countTaskInRow', 'Число ячеек с заданиями в строке прогрессбара', '',  DEFAULT_PROGRESSBAR_COUNTTASKINROW, $countTaskInRow));

    $countSectionInRow = array(3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10);
    $settings->add(new admin_setting_configselect('block_progress_bar/countSectionInRow', 'Число ячеек с темами в строке прогрессбара', '',  DEFAULT_PROGRESSBAR_COUNTSECTIONINROW, $countSectionInRow));

}
