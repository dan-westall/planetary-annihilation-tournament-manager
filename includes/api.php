<?php

class PLTM_API_Endpoint{


    /** Hook WordPress
     *	@return void
     */
    public function __construct(){
        add_filter('query_vars', array($this, 'add_query_vars'), 0);
        add_action('parse_request', array($this, 'sniff_requests'), 0);
        add_action('init', array($this, 'add_endpoint'), 0);
    }

    /** Add public query vars
     *	@param array $vars List of current public query vars
     *	@return array $vars
     */
    public function add_query_vars($vars){
        $vars[] = '__api';
        $vars[] = 'tournament';
        $vars[] = 'return';
        $vars[] = 'match';
        $vars[] = 'player';
        $vars[] = 'tournaments';
        return $vars;
    }

    /** Add API Endpoint
     *	This is where the magic happens - brush up on your regex skillz
     *	@return void
     */
    public function add_endpoint(){
        //add_rewrite_rule('^api/tournament-matches/?([0-9]+)?/?','index.php?__api=1&tournament-matches=$matches[1]','top');

        // /api/tournament/345333/matches || /api/tournament/345333/players
        add_rewrite_rule('^api/tournament/([^/]*)/([^/]*)/?','index.php?__api=1&tournament_id=$matches[1]&return=$matches[2]','top');



        add_rewrite_rule('^api/match/?([0-9]+)?/?','index.php?__api=1&match_id=$matches[1]','top');
        add_rewrite_rule('^api/player/?([0-9]+)?/?','index.php?__api=1&player=$matches[1]','top');
        add_rewrite_rule('^api/tournaments/?([0-9]+)?/?','index.php?__api=1&status=$matches[1]','top');
        add_rewrite_rule('^api/tournament/?([0-9]+)?/?','index.php?__api=1&tournament=$matches[1]','top');




        //add_rewrite_rule('^nutrition/([^/]*)/([^/]*)/?','index.php?page_id=12&food=$matches[1]&variety=$matches[2]','top');
//        This example would match a requested URL like this:
//example.com/nutrition/milkshakes/strawberry/
//...and interpret it to actually mean...
//example.com/index.php?page_id=12&food=milkshake&variety=strawberry
    }

    /**	Sniff Requests
     *	This is where we hijack all API requests
     * 	If $_GET['__api'] is set, we kill WP
     *	@return die if API request
     */
    public function sniff_requests(){
        global $wp;
        if(isset($wp->query_vars['__api'])){
            $this->handle_request();
            exit;
        }
    }

    /** Handle Requests
     *
     *	@return void
     */
    protected function handle_request(){

        global $wp;

        if(isset($wp->query_vars['tournament-matches'])){

            $tournament_id = $wp->query_vars['tournament-matches'];

            if(!$tournament_id)
                $this->send_response('Tournament id is missing');

            matchCPT::get_match_results(array('tournament_id' => $tournament_id, 'output' => 'json'));

        } else

        if(isset($wp->query_vars['tournament'])){

            $tournament_id = $wp->query_vars['tournament'];

            if(!$tournament_id)
                $this->send_response('Tournament id is missing');

            if(isset($wp->query_vars['return'])){

                switch($wp->query_vars['return']){

                    case "matches":

                        matchCPT::get_match_results(array('tournament_id' => $tournament_id, 'output' => 'json'));

                        break;

                    case "player":

                        tournamentCPT::get_tournament_players(array('tournament_id' => $tournament_id, 'output' => 'json'));

                        break;

                }

            } else {

                $tournament_id = $wp->query_vars['tournament'];

                if(!$tournament_id)
                    $this->send_response('Tournament id is missing');

                //return tournament info
                tournamentCPT::get_tournament(array('tournament_id' => $tournament_id, 'output' => 'json'));

            }

        } else if(isset($wp->query_vars['match'])){

            $match_id = $wp->query_vars['match'];

            if(!$match_id)
                $this->send_response('Match id is missing');

            matchCPT::get_match_results(array('match_id' => $match_id, 'output' => 'json'));

        }

    }

    protected function send_response($msg){
        $response['message'] = $msg;
        header('content-type: application/json; charset=utf-8');
        echo json_encode($response)."\n";
        exit;
    }

}

new PLTM_API_Endpoint();