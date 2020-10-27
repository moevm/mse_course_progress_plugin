<?php

namespace block_completion_progress;

defined('MOODLE_INTERNAL') || die;

class checkbox_toggleall_compat implements \renderable {
    public $togglegroup;

    public $ismaster;

    public $options;

    public function __construct($togglegroup, $ismaster, $options = []) {
        $this->togglegroup = $togglegroup;
        $this->ismaster = $ismaster;
        $this->options = $options;
    }
}
