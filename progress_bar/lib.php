<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/completionlib.php');
const DEFAULT_PROGRESSBAR_FONT = 14;
const DEFAULT_PROGRESSBAR_COUNTTASKINROW = 15;
const DEFAULT_PROGRESSBAR_COUNTSECTIONINROW = 5;

function block_progress_bar_get_activities($courseid, $config = null) {
    $modinfo = get_fast_modinfo($courseid, -1);
    $sections = $modinfo->get_sections();
    $activities = array();
    foreach ($modinfo->instances as $module => $instances) {
        foreach ($instances as $index => $cm) {
            if ($cm->completion != COMPLETION_TRACKING_NONE && !$cm->deletioninprogress)
                 {
                $activities[] = array (
                    'id'         => $cm->id,
                    'section'    => $cm->sectionnum,
                    'position'   => array_search($cm->id, $sections[$cm->sectionnum]),
                );
            }
        }
    }

    usort($activities, 'block_progress_bar_compare_events');

    return $activities;
}

function block_progress_bar_compare_events($a, $b) {
    if ($a['section'] != $b['section']) {
        return $a['section'] - $b['section'];
    } else {
        return $a['position'] - $b['position'];
    }
}


function block_progress_bar_completions($activities, $userid, $course) {
    $completions = array();
    $completion = new completion_info($course);
    $cm = new stdClass();

    foreach ($activities as $activity) {
        $cm->id = $activity['id'];
        $activitycompletion = $completion->get_data($cm, true, $userid);
        $completions[$activity['id']] = $activitycompletion->completionstate;
    }

    return $completions;
}

function block_progress_bar_bar($activities, $completions, $config, $userid, $courseid, $instance, $simple = false) {
    global $OUTPUT, $CFG, $USER;
    $content = '';
    $numactivities = count($activities);

    $colours = array();

    $colours['completed_colour'] = '#47FF00';
    $colours['futureNotCompleted_colour'] = '#D6DFD3';
    $colours['failed_colour'] = '#FC898A';

    $longbars = 'squeeze';
    $content .= HTML_WRITER::start_div('barContainer');

    $cellunit = '%';
    $celldisplay = 'table-cell';

    $fonts = get_config('block_progress_bar', 'font') ? : DEFAULT_PROGRESSBAR_FONT;
    $countTaskInRow = get_config('block_progress_bar', 'countTaskInRow') ? : DEFAULT_PROGRESSBAR_COUNTTASKINROW;
    $countSectionInRow = get_config('block_progress_bar', 'countSectionInRow') ? : DEFAULT_PROGRESSBAR_COUNTSECTIONINROW;

    $title = array('class' => 'barTitle',
                   'style' => 'font-size: ' . $fonts .'pt; margin-bottom: ' . ($fonts-4) .'px;');

    $content .= HTML_WRITER::tag('div', "Задания:", $title);

    $counter = 1;
    $countComplete = 0;
    $sectionArr = array();
    $countTaskInSection = array();
    $countCompleteTaskInSection = array();
    $countFailedTaskInSection = array();
    if ($numactivities < $countTaskInRow)
	$cellwidth = $numactivities > 0 ? (100 / $numactivities) : 1;
    else
        $cellwidth = $numactivities > 0 ? (100 / $countTaskInRow) : 1;

    $i = 0;
    $k = 0;
    do {
        $content .= HTML_WRITER::start_div('barRow');

        for ($k; $k<$i+$countTaskInRow && $k<$numactivities; $k++) {
	    $activity = $activities[$k];
            $complete = $completions[$activity['id']];

            if (!in_array($activity['section'], $sectionArr)){
                array_push($sectionArr, $activity['section']);
                $countTaskInSection[$activity['section']] = 1;
                $countCompleteTaskInSection[$activity['section']] = 0;
                $countFailedTaskInSection[$activity['section']] = 0;
            }
            else{
		$countTaskInSection[$activity['section']]++;
	    }

            $celloptions = array(
                'class' => 'progressBarCell',
                'style' => 'display:' . $celldisplay .'; width:' . $cellwidth . $cellunit . ';background-color:');

            if ($complete == COMPLETION_COMPLETE || $complete == COMPLETION_COMPLETE_PASS) {
                $celloptions['style'] .= $colours['completed_colour'].';';
                $countComplete++;
                $countCompleteTaskInSection[$activity['section']]++;
            }

            else if ($complete == COMPLETION_COMPLETE_FAIL){
                $celloptions['style'] .= $colours['failed_colour'].';';
                $countFailedTaskInSection[$activity['section']]++;
	    }

            else {
                $celloptions['style'] .= $colours['futureNotCompleted_colour'].';';
            }


            if ($k % $countTaskInRow == 0) {
                $celloptions['class'] .= ' firstProgressBarCell';
            }
            if ($k == $numactivities - 1 || $k % $countTaskInRow == $countTaskInRow - 1) {
                $celloptions['class'] .= ' lastProgressBarCell';
            }

	    $content .= HTML_WRITER::div(null, null, $celloptions);
        }
        $countWhite = ($k % $countTaskInRow == 0) ? 0 : ($countTaskInRow - ($k % $countTaskInRow));
        $celloptions = array(

                'style' => 'display:' . $celldisplay .'; width:' . $cellwidth . $cellunit . ';background-color: #FFFFFF;');
	for ($countWhite; $countWhite > 0; $countWhite--){
		$content .= HTML_WRITER::div(null, null, $celloptions);
	}
        $content .= HTML_WRITER::end_div();
        $i += $countTaskInRow;
    }
    while($k < $numactivities);

    $sectionCount = count($sectionArr);

    if ($sectionCount < $countSectionInRow)
        $cellwidth2 = $sectionCount > 0 ? (100 / $sectionCount) : 1;
    else
        $cellwidth2 = $sectionCount > 0 ? (100 / $countSectionInRow) : 1;
    

    $content .= HTML_WRITER::tag('div', "Темы:", $title);

    $i = 1;
    $k = 1;
    do {
        $content .= HTML_WRITER::start_div('barRow');
        for ($k; $k < $i + $countSectionInRow && $k <= count($countTaskInSection); $k++){

            if ($countTaskInSection[$sectionArr[$k-1]] == $countCompleteTaskInSection[$sectionArr[$k-1]]){
                $countGreen = $countCompleteTaskInSection[$sectionArr[$k-1]];
                $cellWidthInSection = $cellwidth2 / $countTaskInSection[$sectionArr[$k-1]];

                for ($countGreen; $countGreen > 0; $countGreen--){
                   $celloptions = array(
                   'class' => 'progressBarSectionCell',
                   'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:');
                    $celloptions['style'] .= $colours['completed_colour'].';';

                    if ($countGreen == $countCompleteTaskInSection[$sectionArr[$k-1]])
                        $celloptions['class'] .= ' firstProgressBarCell';
                    if (($countGreen == 1 && ($k % $countSectionInRow == 0)) || ($k == count($countTaskInSection) && $countGreen == 1))
                        $celloptions['class'] .= ' lastProgressBarCell';

                    $content .= HTML_WRITER::div(null, null, $celloptions);
                }

	    }
            else{
                $countWhite = $countTaskInSection[$sectionArr[$k-1]] - $countCompleteTaskInSection[$sectionArr[$k-1]] - $countFailedTaskInSection[$sectionArr[$k-1]];
                $countYellow = $countCompleteTaskInSection[$sectionArr[$k-1]];
                $countRed = $countFailedTaskInSection[$sectionArr[$k-1]];
                $cellWidthInSection = $cellwidth2 / $countTaskInSection[$sectionArr[$k-1]];

                for ($countYellow; $countYellow > 0; $countYellow--){
                    $celloptions2 = array(
                        'class' => 'progressBarSectionCell',
                        'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:#FFFF00');
                    if ($countYellow == $countCompleteTaskInSection[$sectionArr[$k-1]])
                        $celloptions2['class'] .= ' firstProgressBarCell';
                    $content .= HTML_WRITER::div(null, null, $celloptions2);
                }

		for ($countRed; $countRed > 0; $countRed--){
                    $celloptions4 = array(
                        'class' => 'progressBarSectionCell',
                        'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:#FC898A');
                    if ($countRed == $countFailedTaskInSection[$sectionArr[$k-1]] && $countCompleteTaskInSection[$sectionArr[$k-1]] == 0)
                        $celloptions4['class'] .= ' firstProgressBarCell';
                    if (($countRed == 1 && ($k % $countSectionInRow == 0) && $countWhite == 0) || ($k == count($countTaskInSection) && $countRed==1 && $countWhite == 0))
                        $celloptions4['class'] .= ' lastProgressBarCell';
                    $content .= HTML_WRITER::div(null, null, $celloptions4);
                }


                for ($countWhite; $countWhite > 0; $countWhite--){
                    $celloptions3 = array(
                        'class' => 'progressBarSectionCell',
                        'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:');
                    $celloptions3['style'] .= $colours['futureNotCompleted_colour'].';';
                    if ($countWhite == $countTaskInSection[$sectionArr[$k-1]])
                        $celloptions3['class'] .= ' firstProgressBarCell';
                    if (($countWhite == 1 && ($k % $countSectionInRow == 0)) || ($k == count($countTaskInSection) && $countWhite==1))
                        $celloptions3['class'] .= ' lastProgressBarCell';
                    $content .= HTML_WRITER::div(null, null, $celloptions3);
                }
	    } 
        }
        $countWhite = (($k-1) % $countSectionInRow == 0) ? 0 : $countSectionInRow - (($k-1) % $countSectionInRow);
        $celloptions3 = array(
            'style' => 'display:' . $celldisplay .'; width:' . $cellwidth2 . $cellunit . ';background-color:#FFFFFF');
        for ($countWhite; $countWhite > 0; $countWhite --)
            $content .= HTML_WRITER::div(null, null, $celloptions3);
        $i += $countSectionInRow;
        $content .= HTML_WRITER::end_div();

   }
   while($k <= count($countTaskInSection));


     $progress = block_progress_bar_percentage($activities, $completions);
     $percentagecontent = 'Общий прогресс: '.$progress.'%';
     $percentageoptions = array('class' => 'progressPercentage',
                                'style' => 'font-size: ' . $fonts .'pt; margin-bottom: ' . ($fonts-4) .'px;');
     $content .= HTML_WRITER::tag('div', $percentagecontent, $percentageoptions);

    $content .= HTML_WRITER::start_div('sertificateRow');
    $counter = 1;
    $cellwidth3 = $numactivities > 0 ? (100 / $numactivities) : 1;
    $countCompleteSert = $countComplete;

    foreach ($activities as $activity) {
        $complete = $completions[$activity['id']];


        $celloptions = array(
            'class' => 'progressSertificateCell',
             'style' => 'display:' . $celldisplay .'; width:' . $cellwidth3 . $cellunit . ';background-color:');


        if ($countComplete > 0) {
            $celloptions['style'] .= $colours['completed_colour'].';';
	    $countComplete--;
        }

	else{
            $celloptions['style'] .= $colours['futureNotCompleted_colour'].';';
        }

        if ($counter == 1) {
            $celloptions['class'] .= ' firstSertificateBarCell';
        }

        if ($counter == $numactivities) {
          $celloptions['class'] .= ' lastSertificateBarCell';
        }

        $counter++;
        $content .= HTML_WRITER::div(null, null, $celloptions);
    }


    $content .= HTML_WRITER::end_div();


    $progress = ceil(block_progress_bar_percentage($activities, $completions)/0.85);
    if ($progress > 100)
       $progress = 100;
    $percentagecontent = 'Получение сертификата: '.$progress.'%';
    $percentageoptions = array('class' => 'progressPercentage',
                               'style' => 'font-size: ' . $fonts .'pt; margin-bottom: ' . ($fonts-4) .'px;');
    $content .= HTML_WRITER::tag('div', $percentagecontent, $percentageoptions);

    $content .= HTML_WRITER::start_div('sertificateRow');
    $counter = 1;
    $numactivities = ($numactivities * 0.85);
    $cellwidth3 = $numactivities > 0 ? (100 / $numactivities) : 1;

    foreach ($activities as $activity) {
        $complete = $completions[$activity['id']];


        $celloptions = array(
            'class' => 'progressSertificateCell',
             'style' => 'display:' . $celldisplay .'; width:' . $cellwidth3 . $cellunit . ';background-color:');


        if ($countCompleteSert > 0) {
            $celloptions['style'] .= $colours['completed_colour'].';';
            $countCompleteSert--;
        }

        else{
            $celloptions['style'] .= $colours['futureNotCompleted_colour'].';';
        }

        if ($counter == 1) {
            $celloptions['class'] .= ' firstSertificateBarCell';
        }
        if ($counter == ceil($numactivities)) {
            $celloptions['class'] .= ' lastSertificateBarCell';
        }

        $counter++;
        $content .= HTML_WRITER::div(null, null, $celloptions);
    }


    $content .= HTML_WRITER::end_div();



    return $content;
}


function block_progress_bar_percentage($activities, $completions) {
    $completecount = 0;

    foreach ($activities as $activity) {
        if (
            $completions[$activity['id']] == COMPLETION_COMPLETE ||
            $completions[$activity['id']] == COMPLETION_COMPLETE_PASS
        ) {
            $completecount++;
        }
    }

    $progressvalue = $completecount == 0 ? 0 : $completecount / count($activities);

    return (int)round($progressvalue * 100);
}
