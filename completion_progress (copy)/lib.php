<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/completionlib.php');


function block_completion_progress_get_activities($courseid, $config = null) {
    $modinfo = get_fast_modinfo($courseid, -1);
    $sections = $modinfo->get_sections();
    $activities = array();
    foreach ($modinfo->instances as $module => $instances) {
        foreach ($instances as $index => $cm) {
            if ($cm->completion != COMPLETION_TRACKING_NONE)
                 {
                $activities[] = array (
                    'id'         => $cm->id,
                    'section'    => $cm->sectionnum,
                    'position'   => array_search($cm->id, $sections[$cm->sectionnum]),
                );
            }
        }
    }

    usort($activities, 'block_completion_progress_compare_events');

    return $activities;
}

function block_completion_progress_compare_events($a, $b) {
    if ($a['section'] != $b['section']) {
        return $a['section'] - $b['section'];
    } else {
        return $a['position'] - $b['position'];
    }
}


function block_completion_progress_completions($activities, $userid, $course) {
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

function block_completion_progress_bar($activities, $completions, $config, $userid, $courseid, $instance, $simple = false) {
    global $OUTPUT, $CFG, $USER;
    $content = '';
    $numactivities = count($activities);

    $colours = array();

    $colours['completed_colour'] = '#47FF00';
    $colours['futureNotCompleted_colour'] = '#D6DFD3';

    $longbars = 'squeeze';
    $content .= HTML_WRITER::start_div('barContainer');

    $cellwidth = $numactivities > 0 ? floor(100 / $numactivities) : 1;
    $cellunit = '%';
    $celldisplay = 'table-cell';

    $content .= HTML_WRITER::start_div('barRow');
    $counter = 1;
    $countComplete = 0;
    foreach ($activities as $activity) {
        $complete = $completions[$activity['id']];
        $celloptions = array(
            'class' => 'progressBarCell',
             'style' => 'display:' . $celldisplay .'; width:' . $cellwidth . $cellunit . ';background-color:');

        if ($complete == COMPLETION_COMPLETE || $complete == COMPLETION_COMPLETE_PASS || $complete == COMPLETION_COMPLETE_FAIL) {
            $celloptions['style'] .= $colours['completed_colour'].';';
            $countComplete++;

        } else {
            $celloptions['style'] .= $colours['futureNotCompleted_colour'].';';
        }


        if ($counter == 1) {
            $celloptions['class'] .= ' firstProgressBarCell';
        }
        if ($counter == $numactivities) {
            $celloptions['class'] .= ' lastProgressBarCell';
        }

        $counter++;
	$content .= HTML_WRITER::div(null, null, $celloptions);
    }

    $content .= HTML_WRITER::end_div();

     $progress = block_completion_progress_percentage($activities, $completions);
     $percentagecontent = get_string('progress', 'block_completion_progress').': '.$progress.'%';
     $percentageoptions = array('class' => 'progressPercentage');
     $content .= HTML_WRITER::tag('div', $percentagecontent, $percentageoptions);

    $content .= HTML_WRITER::start_div('sertificateRow');
    $counter = 1;
    foreach ($activities as $activity) {
        $complete = $completions[$activity['id']];

        $celloptions = array(
            'class' => 'progressSertificateCell',
             'style' => 'display:' . $celldisplay .'; width:' . $cellwidth . $cellunit . ';background-color:');


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


function block_completion_progress_percentage($activities, $completions) {
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
