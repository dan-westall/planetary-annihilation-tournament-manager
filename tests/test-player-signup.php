<?php
/**
 * Test case for the Ajax callback to update 'some_option'.
 *
 * @group ajax
 */
class My_Some_Option_Ajax_Test extends WP_Ajax_UnitTestCase {

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


        //P2P_Storage::uninstall();

//        wp_delete_post( $this->post_id );
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