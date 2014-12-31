<?php

class awards {


    /**
     *
     */
    public static function register() {

        $plugin = new self();

        add_filter( 'patm_p2p_args', [ $plugin, 'awards'], 10, 2);

    }

    /**
     *
     */
    function __construct() {


    }

    public static function awards($args, $object_id){

        $awards = [
            'tournament_matches' => [
                'fields' => [
                    'award' => [
                        'title'  => 'Award',
                        'type'   => 'select',
                        'values' => [
                            'showcase' => 'Showcase Match',
                            'match-of-tournament' => 'Match of the tournament',
                            'best-team' => 'Best Team (Coming Soon)',
                        ]
                    ]
                ]
            ],
            'tournament_players' => [
                'fields' => [
                    'awards' => [
                        'title'  => 'Award',
                        'type'   => 'select',
                        'values' => [
                            'player-of-tournament' => 'Player of tournament'
                        ]
                    ]
                ]
            ],
            'match_players' => [
                'fields' => [
                    'awards' => [
                        'title'  => 'Award',
                        'type'   => 'select',
                        'values' => [
                            'player-of-match' => 'Player of match'
                        ]
                    ]
                ]
            ]
        ];

        return array_merge_recursive($args, $awards[$args['name']]);

    }


}