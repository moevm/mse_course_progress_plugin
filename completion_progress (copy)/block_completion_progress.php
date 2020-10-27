<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/completion_progress/lib.php');

class block_completion_progress extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_completion_progress');
    }


    public function has_config() {
        return true;
    }


    public function specialization() {
        if (isset($this->config->progressTitle) && trim($this->config->progressTitle) != '') {
            $this->title = format_string($this->config->progressTitle);
        }
    }


    public function instance_allow_multiple() {
        return false;
    }


    public function instance_allow_config() {
        return false;
    }

   
    public function applicable_formats() {
        return array(
            'course-view'    => true,
            'site'           => true,
            'mod'            => false,
           'my'             => false
        );
    }

    

    public function get_content() {
        global $USER, $COURSE, $CFG, $OUTPUT, $DB;


        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $blockinstancesonpage = array();


        if (!isloggedin() or isguestuser()) {
           return $this->content;
        }

 
       $completion = new completion_info($COURSE);
       $activities = block_completion_progress_get_activities($COURSE->id, $this->config);
       $completions = block_completion_progress_completions($activities, $USER->id, $COURSE);
       $this->content->text .= block_completion_progress_bar(
            $activities,
            $completions,
            $this->config,
            $USER->id,
            $COURSE->id,
            $this->instance->id
       );


        return $this->content;
    }
}
