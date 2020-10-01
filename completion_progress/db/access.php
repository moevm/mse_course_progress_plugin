<?php


defined('MOODLE_INTERNAL') or die();

$capabilities = array (
    'block/completion_progress:overview' => array (
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_BLOCK,
        'archetypes'    => array (
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'manager'           => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW
        )
    ),

    'block/completion_progress:showbar' => array (
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_BLOCK,
        'archetypes'    => array (
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'student'           => CAP_ALLOW,
        )
    ),

    'block/completion_progress:addinstance' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/completion_progress:myaddinstance' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
);
