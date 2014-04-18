<?php

add_filter( 'xmlrpc_methods', 'add_xml_rpc_methods' );

function add_xml_rpc_methods( $methods ) {

    $methods['frs.helloWorld'] = 'hello_world';

    return $methods;
}

function hello_world( $params ) {

    global $wp_xmlrpc_server;

    $blog_id  = (int) $params[0]; // not used, but follow in the form of the wordpress built in XML-RPC actions
    $username = $params[1];
    $password = $params[2];
    $args     = $params[3];

    // verify credentials
    if ( ! $wp_xmlrpc_server->login( $username, $password ) ) {
        return $wp_xmlrpc_server->error;
    }

    // check for edit_posts capability (requires contributor role)
    // (obviously not required for this simple example, but just for demonstration purposes)
    if ( ! current_user_can( 'edit_posts' ) )
        return new IXR_Error( 403, __( 'You are not allowed access to details about orders.' ) );

    do_action( 'xmlrpc_call', 'frs.helloWorld' ); // patterned on the core XML-RPC actions

    // required parameter
    if ( ! isset( $args['name'] ) ) return new IXR_Error( 500, __( "Missing parameter 'name'" ) );

    // return success
    return "Hello " . $args['name'] . "!";
}





