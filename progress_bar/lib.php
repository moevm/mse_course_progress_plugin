<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/completionlib.php');


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

    $longbars = 'squeeze';
    $content .= HTML_WRITER::start_div('barContainer');

    //$cellwidth = $numactivities > 0 ? floor(100 / $numactivities) : 1;
    $cellunit = '%';
    $celldisplay = 'table-cell';

    $title = array('class' => 'barTitle');
    $content .= HTML_WRITER::tag('div', "Задания:", $title);

    //$content .= HTML_WRITER::start_div('barRow');
    $counter = 1;
    $countComplete = 0;
    $sectionArr = array();
    $countTaskInSection = array();
    $countCompleteTaskInSection = array();
    if ($numactivities < 20)
	$cellwidth = $numactivities > 0 ? (100 / $numactivities) : 1;
    else
        $cellwidth = $numactivities > 0 ? (100 / 20) : 1;
    //oooooooooooooooooo
    $i = 0;
    $k = 0;
    do {
        $content .= HTML_WRITER::start_div('barRow');
	//$activitiesInRow = ($numactivities - $i < 20) ? ($numactivities - $i) : 20;
        //$cellwidth = $activitiesInRow > 0 ? floor(100 / $activitiesInRow) : 1;
        for ($k; $k<$i+20 && $k<$numactivities; $k++) {
	    $activity = $activities[$k];
            $complete = $completions[$activity['id']];

            if (!in_array($activity['section'], $sectionArr)){
                array_push($sectionArr, $activity['section']);
                $countTaskInSection[$activity['section']] = 1;
                $countCompleteTaskInSection[$activity['section']] = 0;
            }
            else{
		$countTaskInSection[$activity['section']]++;
	    }

            $celloptions = array(
                'class' => 'progressBarCell',
                'style' => 'display:' . $celldisplay .'; width:' . $cellwidth . $cellunit . ';background-color:');

            if ($complete == COMPLETION_COMPLETE || $complete == COMPLETION_COMPLETE_PASS || $complete == COMPLETION_COMPLETE_FAIL) {
                $celloptions['style'] .= $colours['completed_colour'].';';
                $countComplete++;
                $countCompleteTaskInSection[$activity['section']]++;


            } else {
                $celloptions['style'] .= $colours['futureNotCompleted_colour'].';';
            }


            if ($k % 20 == 0) {
                $celloptions['class'] .= ' firstProgressBarCell';
            }
            if ($k == $numactivities - 1 || $k % 20 == 19) {
                $celloptions['class'] .= ' lastProgressBarCell';
            }

	    $content .= HTML_WRITER::div(null, null, $celloptions);
        }
        $countWhite = ($k % 20 == 0) ? 0 : (20 - ($k % 20));
        $celloptions = array(
               // 'class' => 'progressBarCell',
                'style' => 'display:' . $celldisplay .'; width:' . $cellwidth . $cellunit . ';background-color: #FFFFFF;');
	for ($countWhite; $countWhite > 0; $countWhite--){
		$content .= HTML_WRITER::div(null, null, $celloptions);
	}
        $content .= HTML_WRITER::end_div();
        $i += 20;
    }
    while($k < $numactivities);

    $sectionCount = count($sectionArr);
    $cellwidth2 = $sectionCount > 0 ? (100 / $sectionCount) : 1;
    
    $title = array('class' => 'barTitle');
    $content .= HTML_WRITER::tag('div', "Темы:", $title);


        $content .= HTML_WRITER::start_div('barRow');
        for ($i=1; $i<=count($countTaskInSection); $i++){
        //$celloptions = array(
          //  'class' => 'progressBarCell',
            // 'style' => 'display:' . $celldisplay .'; width:' . $cellwidth2 . $cellunit . ';background-color:');

            if ($countTaskInSection[$i] == $countCompleteTaskInSection[$i]){
                $countGreen = $countCompleteTaskInSection[$i];
                $cellWidthInSection = $cellwidth2 / $countTaskInSection[$i];
                //$celloptions = array(
                //    'class' => 'progressBarCell',
                //    'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:');
		//$celloptions['style'] .= $colours['completed_colour'].';';
                for ($countGreen; $countGreen > 0; $countGreen--){
                   $celloptions = array(
                   'class' => 'progressBarSectionCell',
                   'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:');
                    $celloptions['style'] .= $colours['completed_colour'].';';

                    if ($countGreen == $countCompleteTaskInSection[$i])
                        $celloptions['class'] .= ' firstProgressBarCell';
                    if ($countGreen == 1 && $i == count($countTaskInSection))
                        $celloptions['class'] .= ' lastProgressBarCell';

                    $content .= HTML_WRITER::div(null, null, $celloptions);
                }
               // if ($i == 1) {
                 //   $celloptions['class'] .= ' firstProgressBarCell';
               // }
               // if ($i == count($countTaskInSection)) {
                 //   $celloptions['class'] .= ' lastProgressBarCell';
               // }
               // $content .= HTML_WRITER::div(null, null, $celloptions);
	    }
            else{
            //oooooooooooooooooooooooooooooooo   $celloptions['style'] .= $colours['futureNotCompleted_colour'].';';
                $countWhite = $countTaskInSection[$i] - $countCompleteTaskInSection[$i];
                $countYellow = $countCompleteTaskInSection[$i];
                $cellWidthInSection = $cellwidth2 / $countTaskInSection[$i];
            ///$celloptions2 = array(
              // 'class' => 'progressBarCell',
               /// 'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:#FFFF00');
                for ($countYellow; $countYellow > 0; $countYellow--){
                    $celloptions2 = array(
                        'class' => 'progressBarSectionCell',
                        'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:#FFFF00');
                    if ($countYellow == $countCompleteTaskInSection[$i])
                        $celloptions2['class'] .= ' firstProgressBarCell';
                    $content .= HTML_WRITER::div(null, null, $celloptions2);
                }

	    //$celloptions3 = array(
              // 'class' => 'progressBarCell',
              //  'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:');
            //$celloptions3['style'] .= $colours['futureNotCompleted_colour'].';';


                for ($countWhite; $countWhite > 0; $countWhite--){
                    $celloptions3 = array(
                        'class' => 'progressBarSectionCell',
                        'style' => 'display:' . $celldisplay .'; width:' . $cellWidthInSection . $cellunit . ';background-color:');
                    $celloptions3['style'] .= $colours['futureNotCompleted_colour'].';';
                    if ($countWhite == $countTaskInSection[$i])
                        $celloptions3['class'] .= ' firstProgressBarCell';
                    if ($countWhite == 1 && $i == count($countTaskInSection))
                        $celloptions3['class'] .= ' lastProgressBarCell';
                    $content .= HTML_WRITER::div(null, null, $celloptions3);
                }

	    }


        //if ($i == 1) {
          //  $celloptions['class'] .= ' firstProgressBarCell';
        //}
        //if ($i == count($countTaskInSection)) {
          //  $celloptions['class'] .= ' lastProgressBarCell';
        //}
    	//$content .= HTML_WRITER::div(null, null, $celloptions);
   } 
   $content .= HTML_WRITER::end_div();


     $progress = block_progress_bar_percentage($activities, $completions);
     $percentagecontent = 'Общий прогресс: '.$progress.'%';
     $percentageoptions = array('class' => 'progressPercentage');
     $content .= HTML_WRITER::tag('div', $percentagecontent, $percentageoptions);

    $content .= HTML_WRITER::start_div('sertificateRow');
    $counter = 1;
    $cellwidth3 = $numactivities > 0 ? (100 / $numactivities) : 1;

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
