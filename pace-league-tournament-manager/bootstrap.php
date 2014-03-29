<?php

define( 'PACE_PLUGIN_DIR' , dirname(__FILE__) );

// public class
require_once( PACE_PLUGIN_DIR.'/public/includes/planetCPT.php');
require_once( PACE_PLUGIN_DIR.'/public/includes/playerCpt.php');
require_once( PACE_PLUGIN_DIR.'/public/includes/sponsorCPT.php');
require_once( PACE_PLUGIN_DIR.'/public/includes/tournamentCPT.php');
require_once( PACE_PLUGIN_DIR.'/public/includes/matchCPT.php');

//3rd party library
require_once( PACE_PLUGIN_DIR.'/includes/challonge.class.php');

// widgets
require_once( PACE_PLUGIN_DIR.'/widgets/widget.tournament.php');