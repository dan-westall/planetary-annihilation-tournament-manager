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
        $vars[] = 'tournament-matches';
        return $vars;
    }

    /** Add API Endpoint
     *	This is where the magic happens - brush up on your regex skillz
     *	@return void
     */
    public function add_endpoint(){
        add_rewrite_rule('^api/tournament-matches/?([0-9]+)?/?','index.php?__api=1&tournament-matches=$matches[1]','top');
    }

    /**	Sniff Requests
     *	This is where we hijack all API requests
     * 	If $_GET['__api'] is set, we kill WP and serve up pug bomb awesomeness
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
     *	This is where we send off for an intense pug bomb package
     *	@return void
     */
    protected function handle_request(){
        global $wp;
        $tournament_id = $wp->query_vars['tournament-matches'];
        if(!$tournament_id)
            $this->send_response('Tournament id is missing');


        matchCPT::get_match_results(array('tournament_id' => $tournament_id, 'output' => 'json'));

    }

}

new PLTM_API_Endpoint();