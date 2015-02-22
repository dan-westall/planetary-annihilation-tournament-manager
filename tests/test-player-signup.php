<?php
/**
 * Test case for the Ajax callback to signup players
 *
 * @group ajax
 */
class Player_Signup_Ajax_Test extends WP_Ajax_UnitTestCase {

    /**
     * to understand setUp, view Setup_Teardown_Example.php
     */
    function setUp() {
        P2P_Storage::install(); // call before $this->start_transaction()
        parent::setUp();

        $this->factory = new PATM_UnitTest_Factory;

    }

    /**
     * to understand tearDown, view Setup_Teardown_Example.php
     */
    function tearDown() {
        parent::tearDown();

    }

    public function test_blank_signup(){

        $tournament_id = $this->factory->tournament->create();
        $player_id = $this->factory->player->create();

        $_POST['security']      = wp_create_nonce('security-' . date('dmy'));
        $_POST['tournament_id'] = $tournament_id;
        $_POST['player_id']     = $player_id;
        $_POST['signup_data']   = [];

        try {
            $this->_handleAjax( 'player_signup' );
        } catch ( WPAjaxDieContinueException $e ) {
            unset( $e );
        }

        $this->assertEquals( 'Plesse make sure all fields have been filled in.', json_decode($this->_last_response)->data->message );

    }

    public function test_tournament_closed(){

        $tournament_id = $this->factory->tournament->create();
        $player_id = $this->factory->player->create();

        $_POST['security'] = wp_create_nonce( 'security-' . date('dmy') );
        $_POST['tournament_id'] = $tournament_id;
        $_POST['player_id'] = $player_id;
        $_POST['signup_data'] = [
            'inGameName' => 'bsport',
            'email' => 'dan.westall@googlemail.com'
        ];

        try {
            $this->_handleAjax( 'player_signup' );
        } catch ( WPAjaxDieContinueException $e ) {
            unset( $e );
        }

        $this->assertEquals( 'Tournament sign ups are closed.', json_decode($this->_last_response)->data->message );

    }
}