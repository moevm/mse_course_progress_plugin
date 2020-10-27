<?php


defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/completionlib.php');

const DEFAULT_COMPLETIONPROGRESS_WRAPAFTER = 16;

const DEFAULT_COMPLETIONPROGRESS_LONGBARS = 'squeeze';

const DEFAULT_COMPLETIONPROGRESS_SCROLLCELLWIDTH = 25;

const DEFAULT_COMPLETIONPROGRESS_COURSENAMETOSHOW = 'shortname';

const DEFAULT_COMPLETIONPROGRESS_SHOWINACTIVE = 0;

const DEFAULT_COMPLETIONPROGRESS_SHOWLASTINCOURSE = 1;

const DEFAULT_COMPLETIONPROGRESS_FORCEICONSINBAR = 0;

const DEFAULT_COMPLETIONPROGRESS_PROGRESSBARICONS = 0;

const DEFAULT_COMPLETIONPROGRESS_ORDERBY = 'orderbytime';

const DEFAULT_COMPLETIONPROGRESS_SHOWPERCENTAGE = 0;

const DEFAULT_COMPLETIONPROGRESS_ACTIVITIESINCLUDED = 'activitycompletion';

function block_completion_progress_student_submissions($courseid, $userid) {
    global $DB;

    $submissions = array();
    $params = array('courseid' => $courseid, 'userid' => $userid);

    // Queries to deliver instance IDs of activities with submissions by user.
    $queries = array (
        'assign' => "SELECT c.id
                       FROM {assign_submission} s, {assign} a, {modules} m, {course_modules} c
                      WHERE s.userid = :userid
                        AND s.latest = 1
                        AND s.status = 'submitted'
                        AND s.assignment = a.id
                        AND a.course = :courseid
                        AND m.name = 'assign'
                        AND m.id = c.module
                        AND c.instance = a.id",
        'workshop' => "SELECT DISTINCT c.id
                         FROM {workshop_submissions} s, {workshop} w, {modules} m, {course_modules} c
                        WHERE s.authorid = :userid
                          AND s.workshopid = w.id
                          AND w.course = :courseid
                          AND m.name = 'workshop'
                          AND m.id = c.module
                          AND c.instance = w.id",
    );

    foreach ($queries as $moduletype => $query) {
        $results = $DB->get_records_sql($query, $params);
        foreach ($results as $cmid => $obj) {
            $submissions[] = $cmid;
        }
    }

    return $submissions;
}

function block_completion_progress_course_submissions($courseid) {
    global $DB;

    $submissions = array();
    $params = array('courseid' => $courseid);

    // Queries to deliver instance IDs of activities with submissions by user.
    $queries = array (
        'assign' => "SELECT ". $DB->sql_concat('s.userid', "'-'", 'c.id') ."
                       FROM {assign_submission} s, {assign} a, {modules} m, {course_modules} c
                      WHERE s.latest = 1
                        AND s.status = 'submitted'
                        AND s.assignment = a.id
                        AND a.course = :courseid
                        AND m.name = 'assign'
                        AND m.id = c.module
                        AND c.instance = a.id",
        'workshop' => "SELECT ". $DB->sql_concat('s.authorid', "'-'", 'c.id') ."
                         FROM {workshop_submissions} s, {workshop} w, {modules} m, {course_modules} c
                        WHERE s.workshopid = w.id
                          AND w.course = :courseid
                          AND m.name = 'workshop'
                          AND m.id = c.module
                          AND c.instance = w.id",
    );

    foreach ($queries as $moduletype => $query) {
        $results = $DB->get_records_sql($query, $params);
        foreach ($results as $mapping => $obj) {
            $submissions[] = $mapping;
        }
    }

    return $submissions;
}

function block_completion_progress_modules_with_alternate_links() {
    global $CFG;

    $alternatelinks = array(
        'assign' => array(
            'url' => '/mod/assign/view.php?id=:cmid&action=grading',
            'capability' => 'mod/assign:grade',
        ),
        'feedback' => array(
            // Breaks if anonymous feedback is collected.
            'url' => '/mod/feedback/show_entries.php?id=:cmid&do_show=showoneentry&userid=:userid',
            'capability' => 'mod/feedback:viewreports',
        ),
        'lesson' => array(
            'url' => '/mod/lesson/report.php?id=:cmid&action=reportdetail&userid=:userid',
            'capability' => 'mod/lesson:viewreports',
        ),
        'quiz' => array(
            'url' => '/mod/quiz/report.php?id=:cmid&mode=overview',
            'capability' => 'mod/quiz:viewreports',
        ),
    );

    if ($CFG->version > 2015111604) {
        $alternatelinks['assign']['url'] = '/mod/assign/view.php?id=:cmid&action=grade&userid=:userid';
    }

    return $alternatelinks;
}

function block_completion_progress_get_activities($courseid, $config = null, $forceorder = null) {
    $modinfo = get_fast_modinfo($courseid, -1);
    $sections = $modinfo->get_sections();
    $activities = array();
    foreach ($modinfo->instances as $module => $instances) {
        $modulename = get_string('pluginname', $module);
        foreach ($instances as $index => $cm) {
            if (
                $cm->completion != COMPLETION_TRACKING_NONE && (
                    $config == null || (
                        !isset($config->activitiesincluded) || (
                            $config->activitiesincluded != 'selectedactivities' ||
                                !empty($config->selectactivities) &&
                                in_array($module.'-'.$cm->instance, $config->selectactivities))))
            ) {
                $activities[] = array (
                    'type'       => $module,
                    'modulename' => $modulename,
                    'id'         => $cm->id,
                    'instance'   => $cm->instance,
                    'name'       => format_string($cm->name),
                    'expected'   => $cm->completionexpected,
                    'section'    => $cm->sectionnum,
                    'position'   => array_search($cm->id, $sections[$cm->sectionnum]),
                    'context'    => $cm->context,
                    'icon'       => $cm->get_icon_url(),
                    'available'  => $cm->available,
                );
            }
        }
    }

    // Sort by first value in each element, which is time due.oooooooo
    if ($forceorder == 'orderbycourse' || ($config && $config->orderby == 'orderbycourse')) {
        usort($activities, 'block_completion_progress_compare_events');
    } else {
        usort($activities, 'block_completion_progress_compare_times');
    }

    return $activities;
}

function block_completion_progress_compare_events($a, $b) {
    if ($a['section'] != $b['section']) {
        return $a['section'] - $b['section'];
    } else {
        return $a['position'] - $b['position'];
    }
}

function block_completion_progress_compare_times($a, $b) {
    if (
        $a['expected'] != 0 &&
        $b['expected'] != 0 &&
        $a['expected'] != $b['expected']
    ) {
        return $a['expected'] - $b['expected'];
    } else if ($a['expected'] != 0 && $b['expected'] == 0) {
        return -1;
    } else if ($a['expected'] == 0 && $b['expected'] != 0) {
        return 1;
    } else {
        return block_completion_progress_compare_events($a, $b);
    }
}


function block_completion_progress_filter_visibility($activities, $userid, $courseid, $exclusions) {
    global $CFG;
    $filteredactivities = array();
    $modinfo = get_fast_modinfo($courseid, $userid);
    $coursecontext = CONTEXT_COURSE::instance($courseid);

    // Keep only activities that are visible.
    foreach ($activities as $index => $activity) {

        $coursemodule = $modinfo->cms[$activity['id']];

        // Check visibility in course.
        if (!$coursemodule->visible && !has_capability('moodle/course:viewhiddenactivities', $coursecontext, $userid)) {
            continue;
        }

        // Check availability, allowing for visible, but not accessible items.
        if (!empty($CFG->enableavailability)) {
            if (has_capability('moodle/course:viewhiddenactivities', $coursecontext, $userid)) {
                $activity['available'] = true;
            } else {
                if (isset($coursemodule->available) && !$coursemodule->available && empty($coursemodule->availableinfo)) {
                    continue;
                }
                $activity['available'] = $coursemodule->available;
            }
        }

        // Check visibility by grouping constraints (includes capabiliooty check).
        if (!empty($CFG->enablegroupmembersonly)) {
            if (isset($coursemodule->uservisible)) {
                if ($coursemodule->uservisible != 1 && empty($coursemodule->availableinfo)) {
                    continue;
                }
            } else if (!groups_course_module_visible($coursemodule, $userid)) {
                continue;
            }
        }

        // Check for exclusions.
        if (in_array($activity['type'].'-'.$activity['instance'].'-'.$userid, $exclusions)) {
            continue;
        }

        // Save the visible event.
        $filteredactivities[] = $activity;
    }
    return $filteredactivities;
}


function block_completion_progress_completions($activities, $userid, $course, $submissions) {
    $completions = array();
    $completion = new completion_info($course);
    $cm = new stdClass();

    foreach ($activities as $activity) {
        $cm->id = $activity['id'];
        $activitycompletion = $completion->get_data($cm, true, $userid);
        $completions[$activity['id']] = $activitycompletion->completionstate;
        if ($completions[$activity['id']] === COMPLETION_INCOMPLETE && in_array($activity['id'], $submissions)) {
            $completions[$activity['id']] = 'submitted';
        }
    }

    return $completions;
}


function block_completion_progress_bar($activities, $completions, $config, $userid, $courseid, $instance, $simple = false) {
    global $OUTPUT, $CFG, $USER;
    $content = '';
    $now = time();
    $usingrtl = right_to_left();
    $numactivities = count($activities);
    $dateformat = get_string('strftimedate', 'langconfig');
    $alternatelinks = block_completion_progress_modules_with_alternate_links();

    // Get colours and use defaults if they are not set in global settings.
    $colournames = array(
        'completed_colour' => 'completed_colour',
        'submittednotcomplete_colour' => 'submittednotcomplete_colour',
        'notCompleted_colour' => 'notCompleted_colour',
        'futureNotCompleted_colour' => 'futureNotCompleted_colour'
    );
    $colours = array();

    $colours['completed_colour'] = '#73A839';
    $colours['submittednotcomplete_colour'] = '#FFCC00';
    $colours['notCompleted_colour'] = '#C71C22';
    $colours['futureNotCompleted_colour'] = '#025187';

    // Get relevant block instance settings or use defaults.

    $useicons = isset($config->progressBarIcons) ? $config->progressBarIcons : DEFAULT_COMPLETIONPROGRESS_PROGRESSBARICONS;

    $orderby = isset($config->orderby) ? $config->orderby : DEFAULT_COMPLETIONPROGRESS_ORDERBY;
    $defaultlongbars = 'squeeze';
    $longbars = isset($config->longbars) ? $config->longbars : $defaultlongbars;
    $displaynow = $orderby == 'orderbytime';
    $showpercentage = isset($config->showpercentage) ? $config->showpercentage : DEFAULT_COMPLETIONPROGRESS_SHOWPERCENTAGE;
    $rowoptions = array();
    $rowoptions['style'] = '';
    $content .= HTML_WRITER::start_div('barContainer');

    // Determine the segment width.
    $wrapafter = 16;
    if ($wrapafter <= 1) {
        $wrapafter = 1;
    }
    if ($numactivities <= $wrapafter) {
        $longbars = 'squeeze';
    }
    if ($longbars == 'wrap') {
        $rows = ceil($numactivities / $wrapafter);
        if ($rows <= 1) {
            $rows = 1;
        }
        $cellwidth = floor(100 / ceil($numactivities / $rows));
        $cellunit = '%';
        $celldisplay = 'inline-block';
        $displaynow = false;
    }
    if ($longbars == 'scroll') {
        $cellwidth = DEFAULT_COMPLETIONPROGRESS_SCROLLCELLWIDTH;
        $cellunit = 'px';
        $celldisplay = 'inline-block';
        $rowoptions['style'] .= 'white-space: nowrap;';
        $leftpoly = HTML_WRITER::tag('polygon', '', array('points' => '30,0 0,15 30,30', 'class' => 'triangle-polygon'));
        $rightpoly = HTML_WRITER::tag('polygon', '', array('points' => '0,0 30,15 0,30', 'class' => 'triangle-polygon'));
        $content .= HTML_WRITER::tag('svg', $leftpoly, array('class' => 'left-arrow-svg', 'height' => '30', 'width' => '30'));
        $content .= HTML_WRITER::tag('svg', $rightpoly, array('class' => 'right-arrow-svg', 'height' => '30', 'width' => '30'));
    }
    if ($longbars == 'squeeze') {
        $cellwidth = $numactivities > 0 ? floor(100 / $numactivities) : 1;
        $cellunit = '%';
        $celldisplay = 'table-cell';
    }

    
    // Determine links to activities.
    for ($i = 0; $i < $numactivities; $i++) {
        if ($userid != $USER->id &&
            array_key_exists($activities[$i]['type'], $alternatelinks) &&
            has_capability($alternatelinks[$activities[$i]['type']]['capability'], $activities[$i]['context'])
        ) {
            $substitutions = array(
                '/:courseid/' => $courseid,
                '/:eventid/'  => $activities[$i]['instance'],
                '/:cmid/'     => $activities[$i]['id'],
                '/:userid/'   => $userid,
            );
            $link = $alternatelinks[$activities[$i]['type']]['url'];
            $link = preg_replace(array_keys($substitutions), array_values($substitutions), $link);
            $activities[$i]['link'] = $CFG->wwwroot.$link;
        } else {
            $activities[$i]['link'] = $activities[$i]['url'];
        }
    }

    // Start progress bar.
    $content .= HTML_WRITER::start_div('barRow', $rowoptions);
    $counter = 1;
    foreach ($activities as $activity) {
        $complete = $completions[$activity['id']];

        // A cell in the progress bar.
        $showinfojs = 'M.block_completion_progress.showInfo('.$instance.','.$userid.','.$activity['id'].');';
        $celloptions = array(
            'class' => 'progressBarCell',
            'ontouchstart' => $showinfojs . ' return false;',
            'onmouseover' => $showinfojs,
             'style' => 'display:' . $celldisplay .'; width:' . $cellwidth . $cellunit . ';background-color:');
        if ($complete === 'submitted') {
            $celloptions['style'] .= $colours['submittednotcomplete_colour'].';';
            $cellcontent = $OUTPUT->pix_icon('blank', '', 'block_completion_progress');

        } else if ($complete == COMPLETION_COMPLETE || $complete == COMPLETION_COMPLETE_PASS) {
            $celloptions['style'] .= $colours['completed_colour'].';';
            $cellcontent = $OUTPUT->pix_icon($useicons == 1 ? 'tick' : 'blank', '', 'block_completion_progress');

        } else if (
            $complete == COMPLETION_COMPLETE_FAIL ||
            (!isset($config->orderby) || $config->orderby == 'orderbytime') &&
            (isset($activity['expected']) && $activity['expected'] > 0 && $activity['expected'] < $now)
        ) {
            $celloptions['style'] .= $colours['notCompleted_colour'].';';
            $cellcontent = $OUTPUT->pix_icon($useicons == 1 ? 'cross' : 'blank', '', 'block_completion_progress');

        } else {
            $celloptions['style'] .= $colours['futureNotCompleted_colour'].';';
            $cellcontent = $OUTPUT->pix_icon('blank', '', 'block_completion_progress');
        }
        if (empty($activity['link'])) {
            $celloptions['style'] .= 'cursor: unset;';
        } else if (!empty($activity['available']) || $simple) {
            $celloptions['onclick'] = 'document.location=\''.$activity['link'].'\';';
        } else if (!empty($activity['link'])) {
            $celloptions['style'] .= 'cursor: not-allowed;';
        }
        if ($longbars != 'wrap' && $counter == 1) {
            $celloptions['class'] .= ' firstProgressBarCell';
        }
        if ($longbars != 'wrap' && $counter == $numactivities) {
            $celloptions['class'] .= ' lastProgressBarCell';
        }

        $counter++;
        $content .= HTML_WRITER::div($cellcontent, null, $celloptions);
    }
    $content .= HTML_WRITER::end_div();
    $content .= HTML_WRITER::end_div();

    // Add the percentage below the progress bar.
    if ($showpercentage == 1 && !$simple) {
        $progress = block_completion_progress_percentage($activities, $completions);
        $percentagecontent = get_string('progress', 'block_completion_progress').': '.$progress.'%';
        $percentageoptions = array('class' => 'progressPercentage');
        $content .= HTML_WRITER::tag('div', $percentagecontent, $percentageoptions);
    }

    // Add the info box below the table.
    $divoptions = array('class' => 'progressEventInfo',
                        'id' => 'progressBarInfo'.$instance.'-'.$userid.'-info');

    // Add hxxxxxxidden divs for activity information.
    $stringincomplete = get_string('completion-n', 'completion');
    $stringcomplete = get_string('completed', 'completion');
    $stringpassed = get_string('completion-pass', 'completion');
    $stringfailed = get_string('completion-fail', 'completion');
    $stringsubmitted = get_string('submitted', 'block_completion_progress');
    foreach ($activities as $activity) {
        $completed = $completions[$activity['id']];
        $divoptions = array('class' => 'progressEventInfo',
                            'id' => 'progressBarInfo'.$instance.'-'.$userid.'-'.$activity['id'],
                            'style' => 'display: none;');
        $content .= HTML_WRITER::start_tag('div', $divoptions);

        $text = '';
        $text .= html_writer::empty_tag('img',
                array('src' => $activity['icon'], 'class' => 'moduleIcon', 'alt' => '', 'role' => 'presentation'));
        $text .= s(format_string($activity['name']));
        if (!empty($activity['link']) && (!empty($activity['available']) || $simple)) {
            $content .= $OUTPUT->action_link($activity['link'], $text);
        } else {
            $content .= $text;
        }
        $content .= HTML_WRITER::empty_tag('br');
        $altattribute = '';
        if ($completed == COMPLETION_COMPLETE) {
            $content .= $stringcomplete.'&nbsp;';
            $icon = 'tick';
            $altattribute = $stringcomplete;
        } else if ($completed == COMPLETION_COMPLETE_PASS) {
            $content .= $stringpassed.'&nbsp;';
            $icon = 'tick';
            $altattribute = $stringpassed;
        } else if ($completed == COMPLETION_COMPLETE_FAIL) {
            $content .= $stringfailed.'&nbsp;';
            $icon = 'cross';
            $altattribute = $stringfailed;
        } else {
            $content .= $stringincomplete .'&nbsp;';
            $icon = 'cross';
            $altattribute = $stringincomplete;
            if ($completed === 'submitted') {
                $content .= '(' . $stringsubmitted . ')&nbsp;';
                $altattribute .= '(' . $stringsubmitted . ')';
            }
        }
        $content .= $OUTPUT->pix_icon($icon, $altattribute, 'block_completion_progress', array('class' => 'iconInInfo'));
        $content .= HTML_WRITER::empty_tag('br');
        if ($activity['expected'] != 0) {
            $content .= HTML_WRITER::start_tag('div', array('class' => 'expectedBy'));
            $content .= get_string('time_expected', 'block_completion_progress').': ';
            $content .= userdate($activity['expected'], $dateformat, $CFG->timezone);
            $content .= HTML_WRITER::end_tag('div');
        }
        $content .= HTML_WRITER::end_tag('div');
    }

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


function block_completion_progress_on_site_page() {
    global $SCRIPT, $COURSE;

    return $SCRIPT === '/my/index.php' || $COURSE->id == 1;
}


function block_completion_progress_exclusions ($courseid, $userid = null) {
    global $DB;

    $query = "SELECT g.id, ". $DB->sql_concat('i.itemmodule', "'-'", 'i.iteminstance', "'-'", 'g.userid') ." as exclusion
               FROM {grade_grades} g, {grade_items} i
              WHERE i.courseid = :courseid
                AND i.id = g.itemid
                AND g.excluded <> 0";

    $params = array('courseid' => $courseid);
    if (!is_null($userid)) {
        $query .= " AND g.userid = :userid";
        $params['userid'] = $userid;
    }
    $results = $DB->get_records_sql($query, $params);
    $exclusions = array();
    foreach ($results as $key => $value) {
        $exclusions[] = $value->exclusion;
    }
    return $exclusions;
}


function block_completion_progress_group_membership ($group, $courseid, $userid) {
    if ($group === '0') {
        return true;
    } else if ((substr($group, 0, 6) == 'group-') && ($groupid = intval(substr($group, 6)))) {
        return groups_is_member($groupid, $userid);
    } else if ((substr($group, 0, 9) == 'grouping-') && ($groupingid = intval(substr($group, 9)))) {
        return array_key_exists($groupingid, groups_get_user_groups($courseid, $userid));
    }

    return false;
}
