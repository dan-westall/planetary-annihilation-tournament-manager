<?php

//todo autoloader!

define( 'WPTM_PLUGIN_DIR' , dirname(__FILE__) );
define( 'WPTM_PLUGIN_URI' , plugin_dir_url(__FILE__) );


require_once( WPTM_PLUGIN_DIR.'/includes/functions.php');

require_once( WPTM_PLUGIN_DIR.'/includes/xmlrpc_function.php');
require_once( WPTM_PLUGIN_DIR.'/includes/api.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class_statistics.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-polling.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-tournament-in-progress.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class_gform_filters.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-acf-rules.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-awards.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class_shortcodes.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-wptm-signup.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-better-hipchat-addon.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-wptm-clans.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-wptm-site-status.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class-wptm-match-generation.php');


//3rd party library
require_once( WPTM_PLUGIN_DIR.'/includes/challonge.class.php');
require_once( WPTM_PLUGIN_DIR.'/includes/class_realtime_coms.php');

// public class
require_once( WPTM_PLUGIN_DIR.'/public/includes/planetCPT.php');
require_once( WPTM_PLUGIN_DIR.'/public/includes/playerCPT.php');
require_once( WPTM_PLUGIN_DIR.'/public/includes/sponsorCPT.php');
require_once( WPTM_PLUGIN_DIR.'/public/includes/tournamentCPT.php');
require_once( WPTM_PLUGIN_DIR.'/public/includes/matchCPT.php');
require_once( WPTM_PLUGIN_DIR.'/public/includes/notificationCPT.php');
require_once( WPTM_PLUGIN_DIR.'/public/includes/rulesCPT.php');

// widgets
require_once( WPTM_PLUGIN_DIR.'/widgets/widget.tournament.php');
require_once( WPTM_PLUGIN_DIR.'/widgets/widget.tournament.signup.php');
require_once( WPTM_PLUGIN_DIR.'/widgets/widget.tournament.staff.php');


require_once( WPTM_PLUGIN_DIR.'/includes/helpers/class-wptm-player-helper.php');
require_once( WPTM_PLUGIN_DIR.'/includes/helpers/class-wptm-tournament-helper.php');
require_once( WPTM_PLUGIN_DIR.'/includes/tournament-formats/round-robin.php');

