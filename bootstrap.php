<?php

define( 'PLTM_PLUGIN_DIR' , dirname(__FILE__) );
define( 'PLTM_PLUGIN_URI' , plugin_dir_url(__FILE__) );

if($_SERVER['SERVER_NAME'] == 'exodusesports.com' || $_SERVER['SERVER_NAME'] ==  'dev.exodusesports.com'){
    require_once ( PLTM_PLUGIN_DIR.'/includes/acf.fields.php' );
}

require_once( PLTM_PLUGIN_DIR.'/includes/functions.php');

require_once( PLTM_PLUGIN_DIR.'/includes/xmlrpc_function.php');
require_once( PLTM_PLUGIN_DIR.'/includes/api.php');


//3rd party library
require_once( PLTM_PLUGIN_DIR.'/includes/challonge.class.php');

// public class
require_once( PLTM_PLUGIN_DIR.'/public/includes/planetCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/playerCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/sponsorCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/tournamentCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/matchCPT.php');

// widgets
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.php');
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.signup.php');
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.staff.php');