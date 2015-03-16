<?php

/**
 * Test case for the Ajax callback to signup players
 *
 * @group challonge
 */
class Challonge_Intergration_Tests extends WP_UnitTestCase {

    /**
     * to understand setUp, view Setup_Teardown_Example.php
     */
    function setUp() {
        parent::setUp();

        $this->factory = new PATM_UnitTest_Factory;

    }

    /**
     * to understand tearDown, view Setup_Teardown_Example.php
     */
    function tearDown() {
        parent::tearDown();

    }

    function test_create_challonge_signup(){


        add_site_option( 'options_challonge_api', '' );

        $tournament_id = $this->factory->tournament->create();

        update_post_meta($tournament_id, 'challonge_tournament_link', 877198);

        $player_id = $this->factory->player->create(['post_title' => rand(5, 10)]);

        $result = tournamentSignup::challonge_add_player_to_tournament($player_id, $tournament_id);

        $this->assertSame( 'true', $result['active'] );


    }
}