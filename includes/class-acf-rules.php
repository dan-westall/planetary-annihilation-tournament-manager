<?php

/**
 * Class ACF Custom rules
 */
class acfCustomRules {

    /**
     *
     */
    public static function register() {

        $plugin = new self();

        add_action('p2p_init', [$plugin, 'register_p2p_connections']);


        add_filter('acf/location/rule_types', [$plugin, 'acf_location_rules_types']);

        add_filter('acf/location/rule_operators', [$plugin, 'acf_location_rules_operators']);
        add_filter('acf/location/rule_values/fixture', [$plugin, 'acf_location_fixture_values']);

        add_filter('acf/location/rule_match/fixture', [$plugin, 'acf_location_rules_match_fixture'], 10, 3);

    }

    /**
     *
     */
    function __construct() {


    }


    function acf_location_rules_types($choices) {
        $choices['PATM']['fixture'] = 'Fixture';

        return $choices;
    }

    function acf_location_rules_operators($choices) {
        $choices['<'] = 'is less than';
        $choices['>'] = 'is greater than';
        $choices['>'] = 'is greater than';

        return $choices;
    }

    function acf_location_fixture_values($choices) {

        $choices['true'] = 'True';
        $choices['false'] = 'False';

        return $choices;
    }

    function acf_location_rules_match_fixture($match, $rule, $options) {

        global $post;


        //convert
        $is_fixture = $rule['value'] === 'true'? true: false;

        if ($rule['operator'] == "==") {
            $match = (empty($post->post_parent) != $is_fixture);
        } elseif ($rule['operator'] == "!=") {
            $match = (empty($post->post_parent) == $is_fixture);
        }

        return $match;
    }
}