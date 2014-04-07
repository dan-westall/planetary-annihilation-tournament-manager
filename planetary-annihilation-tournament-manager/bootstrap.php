<?php

define( 'PLTM_PLUGIN_DIR' , dirname(__FILE__) );

require_once( PLTM_PLUGIN_DIR.'/includes/functions.php');

//3rd party library
require_once( PLTM_PLUGIN_DIR.'/includes/challonge.class.php');

// public class
require_once( PLTM_PLUGIN_DIR.'/public/includes/planetCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/playerCpt.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/sponsorCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/tournamentCPT.php');
require_once( PLTM_PLUGIN_DIR.'/public/includes/matchCPT.php');

// widgets
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.php');
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.signup.php');
require_once( PLTM_PLUGIN_DIR.'/widgets/widget.tournament.staff.php');