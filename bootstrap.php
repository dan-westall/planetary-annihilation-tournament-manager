<?php

define( 'PLTM_PLUGIN_DIR' , dirname(__FILE__) );
define( 'PLTM_PLUGIN_URI' , plugin_dir_url(__FILE__) );


if($_SERVER['SERVER_NAME'] == 'exodusesports.com' || $_SERVER['SERVER_NAME'] ==  'dev.exodusesports.com'){
    require_once ( PLTM_PLUGIN_DIR.'/includes/acf.fields.php' );
}

require_once( PLTM_PLUGIN_DIR.'/includes/functions.php');

require_once( PLTM_PLUGIN_DIR.'/includes/xmlrpc_function.php');
require_once( PLTM_PLUGIN_DIR.'/includes/api.php');
require_once( PLTM_PLUGIN_DIR.'/includes/class_statistics.php');
require_once( PLTM_PLUGIN_DIR.'/includes/class-polling.php');
require_once( PLTM_PLUGIN_DIR.'/includes/class_gform_filters.php');


//3rd party library
require_once( PLTM_PLUGIN_DIR.'/includes/challonge.class.php');
require_once( PLTM_PLUGIN_DIR.'/includes/class_realtime_coms.php');

// public class
require_once( PLTM_PLUGIN_DIR.'/public/includes/planetCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/playerCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/sponsorCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/tournamentCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/matchCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/notificationCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/class_shortcodes.php');

// widgets
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.php');
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.signup.php');
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.staff.php');
