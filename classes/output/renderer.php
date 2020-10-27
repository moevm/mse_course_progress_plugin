<?php

namespace block_completion_progress\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use html_writer;
use block_completion_progress\checkbox_toggleall_compat;

class renderer extends plugin_renderer_base {
    public function render_checkbox_toggleall_compat(checkbox_toggleall_compat $renderable) {
        $inputattribs = [
            'id' => $renderable->options['id'],
            'name' => $renderable->options['name'],
            'type' => 'checkbox',
            'class' => $renderable->options['classes'] ?? '',
            'data-action' => 'toggle',
            'data-toggle' => $renderable->ismaster ? 'master' : 'slave',
            'data-togglegroup' => $renderable->togglegroup,
        ];
        if (!empty($renderable->options['checked'])) {
            $inputattribs['checked'] = 'checked';
        }
        if ($renderable->ismaster) {
            $inputattribs += [
                'data-toggle-selectall' => get_string('selectall'),
                'data-toggle-deselectall' => get_string('deselectall'),
            ];
        }
        $labelattribs = [
            'for' => $renderable->options['id'],
            'class' => $renderable->options['labelclasses'] ?? '',
        ];
        return html_writer::empty_tag('input', $inputattribs) .
            html_writer::tag('label', $renderable->options['label'], $labelattribs);
    }
}
