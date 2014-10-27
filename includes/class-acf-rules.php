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
        add_filter('acf/location/rule_values/user', [$plugin, 'acf_location_rules_values_user']);

        add_filter('acf/location/rule_match/user', [$plugin, 'acf_location_rules_match_user'], 10, 3);

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

    function acf_location_rules_values_user($choices) {
        $users = get_users();

        if ($users) {
            foreach ($users as $user) {
                $choices[$user->data->ID] = $user->data->display_name;
            }
        }

        return $choices;
    }

    function acf_location_rules_match_user($match, $rule, $options) {
        $current_user  = wp_get_current_user();
        $selected_user = (int)$rule['value'];

        if ($rule['operator'] == "==") {
            $match = ($current_user->ID == $selected_user);
        } elseif ($rule['operator'] == "!=") {
            $match = ($current_user->ID != $selected_user);
        }

        return $match;
    }
}