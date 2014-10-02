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
        $vars[] = '__signature';
        $vars[] = '__site-status';
        $vars[] = 'tournament';
        $vars[] = 'tournaments';
        $vars[] = 'return';
        $vars[] = 'match';
        $vars[] = 'player';
        $vars[] = 'playercid';
        $vars[] = 'id_type';
        $vars[] = 'videos';
        $vars[] = 'test';
        $vars[] = 'tournament_players';
        $vars[] = 'clan';
        $vars[] = 'match_players';
        $vars[] = 'match_statuss';
        return $vars;
    }

    /** Add API Endpoint
     *	This is where the magic happens - brush up on your regex skillz
     *	@return void
     */
    public function add_endpoint(){


        add_rewrite_rule('^site-status/?([^/]*)?/?','index.php?__site-status=1','top');

        //add_rewrite_rule('^api/tournament-matches/?([0-9]+)?/?','index.php?__api=1&tournament-matches=$matches[1]','top');

        //add_rewrite_rule('^signature/tournament/?([^/]*)?/?([^/]*)?/?','index.php?__api=1&tournament=$matches[1]&return=$matches[2]','top');
        add_rewrite_rule('^signature/?([^/]*)?/?([^/]*)?/?','index.php?__signature=$matches[1]&return=$matches[2]','top');

        // /api/tournament/345333/matches || /api/tournament/345333/players
        add_rewrite_rule('^api/tournaments/?([^/]*)?/?','index.php?__api=1&tournaments=$matches[1]','top');
        add_rewrite_rule('^api/tournament/?([^/]*)?/?([^/]*)?/?','index.php?__api=1&tournament=$matches[1]&return=$matches[2]','top');

        add_rewrite_rule('^api/match/?([0-9]+)?/?([^/]*)?/?','index.php?__api=1&match_id=$matches[1]&id_type=$matches[2]','top');
        add_rewrite_rule('^api/playercid/?([0-9]+)?/?','index.php?__api=1&playercid=$matches[1]','top');





        add_rewrite_tag('%tournaments%','([^&]+)');
        add_rewrite_tag('%id_type%','([^&]+)');

        //add_rewrite_rule('^api/videos/?([^/]*)?/?','index.php?__api=1&videos=$videos[1]','top');


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
        if(isset($wp->query_vars['__api']) || isset($wp->query_vars['__signature']) || isset($wp->query_vars['__site-status'])){
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

        if(isset($wp->query_vars['tournament'])){

            $tournament_id = $wp->query_vars['tournament'];

            if(!$tournament_id)
                $this->send_response('Tournament id is missing');

            if(isset($wp->query_vars['return']) && !empty($wp->query_vars['return'])){

                switch($wp->query_vars['return']){

                    case "matches":

                        matchCPT::get_match_results(array('tournament_id' => $tournament_id, 'output' => 'json'));

                        break;

                    case "players":

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

        } else if(isset($wp->query_vars['tournaments'])){

            $tournament_status = $wp->query_vars['tournaments'];

            tournamentCPT::get_tournaments(array('output' => 'json', 'status' => $tournament_status));

        } else if(isset($wp->query_vars['match'])){

            $match_id = $wp->query_vars['match'];
            $id_type  = $wp->query_vars['id_type'];

            if(!$match_id)
                $this->send_response('Match id is missing');

            matchCPT::get_match_results(array('match_id' => $match_id, 'by' => $id_type, 'output' => 'json'));

        } else if(isset($wp->query_vars['player'])){

            $player_id = $wp->query_vars['player'];

            if(!$player_id)
                $this->send_response('player id is missing');

            playerCPT::get_player(array('player_id' => $player_id, 'output' => 'json'));

        } else if(isset($wp->query_vars['playercid'])){

            $player_cid = $wp->query_vars['playercid'];

            if(!$player_cid)
                $this->send_response('player challongeid is missing');

            playerCPT::get_player(array('player_id' => $player_cid, 'by' => 'challonge_participant_id', 'output' => 'json'));

        } else if(isset($wp->query_vars['__signature'])){


            if(get_field('site_signatures', 'options')):

                while(has_sub_field('site_signatures', 'options')):

                    $keyword = get_sub_field('keyword');

                    $image[$keyword] = get_sub_field('signature_image');

                endwhile;

            endif;

            if(is_tournament_in_progress()) {

                $image_url = $image['in-progress']['url'];

                //no in progress keyword set
                if(empty($image_url)){

                    //random
                    $key = array_rand($image, 1);

                    $image_url = $image[$key]['url'];

                }

            } else if(isset($image['tournament-promotion'])){

                $image_url = $image['tournament-promotion']['url'];

            } else if($wp->query_vars['__signature'] != 'random'){

                $image_url = $image[$wp->query_vars['__signature']]['url'];

            } else if($wp->query_vars['__signature'] == 'random'){

                unset($image['in-progress']);
                $key = array_rand($image, 1);

                $image_url = $image[$key]['url'];

            }

            $sig_image = imagecreatefrompng($image_url);

            header('Content-Type: image/png');

            imagepng($sig_image);
            imagedestroy($sig_image);

        } elseif(isset($wp->query_vars['__site-status'])){
            if(is_tournament_in_progress()){

                $post_id       = get_option('page_on_front');
                $tournament_id = get_post_meta($post_id, 'tournament', true);

                $response['site_status'] = ['tournament_in_progress' => true, 'front_page' => true, 'tournament_name' => get_the_title($tournament_id)];

                header('content-type: application/json; charset=utf-8');
                echo json_encode($response)."\n";
                exit;

            } else {

                $response['site_status'] = ['tournament_in_progress' => false];

                header('content-type: application/json; charset=utf-8');
                echo json_encode($response)."\n";
                exit;
            }
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