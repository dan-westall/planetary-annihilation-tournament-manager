<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Tournament_Rest_API {


    /**
     * WPTM_Tournament_Rest_API constructor.
     */
    public function __construct() {


        add_action( 'rest_prepare_tournament', [ $this, 'tournament_json_extend_v2' ], 10, 3 );
        add_action( 'rest_api_init', [ $this, 'tournament_match_endpoint' ], 10, 3 );


    }

    public function tournament_json_extend_v2($data, $post, $request) {

        global $wpdb;

        if ( $request['context'] !== 'view' || is_wp_error( $data ) ) {

            return $data;

        }

        if ( $post->post_type != tournamentCPT::$post_type ) {

            return $data;

        }

        $response_data = $data->get_data();

        $remove_fields     = array('author', 'parent', 'format', 'slug', 'guid', 'menu_order', 'ping_status', 'sticky', 'content', 'meta' => 'links');

        $tournament_status = tournamentCPT::$tournament_status[ get_post_meta( $post->ID, 'tournament_status', true) ];
        $tournament_signup = new WPTM_Tournament_Signup();
        $tournament_result = [];
        $tournament_id     = $post->ID;

        //dont need author
        foreach ($remove_fields as $key => $field) {
            if (is_string($key)) {
                unset($response_data[$key][$field]);
            } else {
                unset($response_data[$field]);
            }
        }

        //3962 clanwars

        $player_query = $wpdb->prepare(
            "
            SELECT
            p2p_id,
            $wpdb->posts.ID,
            $wpdb->posts.post_title,
            (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'pastats_player_id' AND $wpdb->postmeta.post_id = $wpdb->p2p.p2p_to) AS pastats_player_id,
            (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'pastats_player_id' AND $wpdb->postmeta.post_id = $wpdb->p2p.p2p_to) AS player_clan,
            (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'status' AND p2p_id = $wpdb->p2p.p2p_id) AS player_tournament_status,
            (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'result' AND p2p_id = $wpdb->p2p.p2p_id) AS player_finish,
            (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'team_name' AND p2p_id = $wpdb->p2p.p2p_id) AS team_name,
            (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'clan' AND $wpdb->postmeta.post_id = $wpdb->p2p.p2p_to) AS clan_name
                FROM $wpdb->p2p
                    LEFT JOIN $wpdb->posts ON p2p_to = $wpdb->posts.ID
                        WHERE p2p_from = %s && p2p_type = 'tournament_players'
            ",
            $tournament_id
        );

        $players = $wpdb->get_results($player_query);

        $match_query = $wpdb->prepare(
            "
            SELECT
            p2p_id,
            $wpdb->posts.ID,
            $wpdb->posts.post_title,
            (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'match_fixture' AND p2p_id = $wpdb->p2p.p2p_id) AS match_fixture
                FROM $wpdb->p2p
                    LEFT JOIN $wpdb->posts ON p2p_to = $wpdb->posts.ID
                        WHERE p2p_from = %s && p2p_type = 'tournament_matches'
            ",
            $tournament_id
        );

        $matches = $wpdb->get_results($match_query);


        foreach ($players as $player) {

            $result = [];

            $player_details = array(
                'wp_player_id'       => $player->ID,
                'player_name'        => $player->post_title,
                'pa_stats_player_id' => $player->pastats_player_id,
                'url'                => get_permalink($player->ID),
                'status'             => $player->player_tournament_status
            );

            //tournament finished
            if ($tournament_status == tournamentCPT::$tournament_status[3]) {

                $no_rank = null;

                $player_finish = $player->player_finish;

                if (!empty($player_finish)) {
                    $tournament_result[$player_finish] = $player_details;
                }

                $player_details = array_merge($player_details, [
                    'finish' => ($player_finish ? $player_finish : $no_rank)
                ]);

            }

            if (get_tournament_type($tournament_id) == 'teamarmies') {

                $player_details = array_merge($player_details, [
                    'team_name' => $player->team_name
                ]);
            }

            if (get_tournament_type($tournament_id) == 'clanwars') {

                $player_details = array_merge($player_details, [
                    'team_name' => $player->clan_name
                ]);
            }

            $match_players[] = $player_details;

        }


        $date = get_post_meta($tournament_id, 'run_date', true);
        $time = get_post_meta($tournament_id, 'run_time', true);

        $currentTime = DateTime::createFromFormat('U', $timestamp);

        $date = new DateTime($date);

        $timeArray = explode(':', $time);

        $date->setTime($timeArray[0], $timeArray[1]);

        $response_data['status']                       = $tournament_status;
        $response_data['meta']['total_players']        = count($match_players);
        $response_data['meta']['total_matches']        = count($matches);
        $response_data['meta']['players']              = $match_players;
        $response_data['meta']['tournament_date']      = $date;
        $response_data['meta']['tournament_starttime'] = $time;
        $response_data['meta']['tournament_datetime']  = $date->getTimestamp();

        if (($challonge_id = get_post_meta($tournament_id, 'challonge_tournament_link', true)) > 0) {

            $response_data['meta']['challonge_id'] = $challonge_id;

        }

        $response_data['meta']['tournament_prizes'] = tournamentCPT::get_tournament_prize_tiers_v2($tournament_id);
        $response_data['meta']['signup_open']       = $tournament_signup->is_tournament_signup_open($tournament_id);

        $tournament_fixtures = tournamentCPT::get_tournament_fixtures($tournament_id);

        if ($tournament_fixtures) {

            $fixture_match_count = [];

            foreach ($matches as $match) {

                $fixture_match_count[ $match->match_fixture ]++;

            }

            // loop through the rows of data
            foreach ($tournament_fixtures as $fixture) {

                //if (!empty($date_time)) {

                    $fixtures[] = [
                        'date'    => $fixture->fixture_date,
                        'name'    => $fixture->fixture_name,
                        'status'  => tournamentCPT::$tournament_status[$fixture->fixture_status],
                        'matches' => $fixture_match_count[ strtotime( $fixture->fixture_date ) ]
                    ];

               // }

            }

            if (count($fixtures) > 0) {

                $response_data['meta']['fixtures'] = $fixtures;

            }

        }

        //tournament finished add winner and other information
        if ($tournament_status == tournamentCPT::$tournament_status[3] ) {

            ksort($tournament_result);
            $response_data['meta']['result'] = $tournament_result;
            $response_data['meta']['awards'] = '';

        }

        return $response_data;
    }

    public function tournament_match_endpoint(){

        register_rest_route( 'wp/v2', '/' . tournamentCPT::$post_type . '/(?P<id>[\d]+)/matches', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'api_get_tournament_matches' ),
//                'permission_callback' => array( 'WP_REST_Posts_Controller', 'get_item_permissions_check' ),
                'args'            => array(
                    'context'          => array(
                        'default'      => 'view',
                    ),
                ),
            ),
        ) );


    }

    public function api_get_tournament_matches( $request ) {
        $args = (array) $request->get_params();
        $args['post_type'] = $this->post_type;
        $args['paged'] = $args['page'];
        $args['posts_per_page'] = $args['per_page'];
        unset( $args['page'] );


        return $response;

    }


}