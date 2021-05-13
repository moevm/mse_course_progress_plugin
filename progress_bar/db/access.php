
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/progress_bar:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/progress_bar:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(),
        
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);