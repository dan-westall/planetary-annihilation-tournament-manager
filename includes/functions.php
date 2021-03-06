<?php

//moved to tournament helper/
function get_tournament_players($tournament_id, $status = array('active'), $args = []){

    $arg = array_merge(array(
        'connected_type'   => 'tournament_players',
        'connected_items'  => $tournament_id,
        'connected_meta' => array(
            array(
                'key' => 'status',
                'value' => $status,
                'compare' => 'IN'
            )
        ),
        'nopaging'         => true,
        'suppress_filters' => false
    ), $args);

    $players = get_posts($arg);

    return $players;

}

function realtime_subscription_id($subscription_id){

    $subscription_id = strtolower($subscription_id);

    if($subscription_id{0} === 't'){

        $tournamnet_id = '';

    }

}

function user_has_voted($object_id){

    global $current_user; get_currentuserinfo();

    $votes = new userPolling();

    return $votes->setUserId($current_user->ID)->setObjectId($object_id)->has_voted();


}

//moved to tourmant helper
function get_tournament_matches($tournament_id){
    $matches = get_posts(array(
        'connected_type'   => 'tournament_matches',
        'connected_items'  => $tournament_id,
        'nopaging'         => true,
        'suppress_filters' => false
    ));

    return $matches;

}

function get_current_user_clan(){


        global $current_user;

        $player_id = $current_user->player_id;

        return get_post_meta($player_id, 'clan', true);

}

function get_the_tournament_endpoint(){

    global $wp_query, $post, $current_user;

    get_currentuserinfo();

    foreach(WP_Tournament_Manager::$tournament_endpoints as $endpoint){

        if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] ) ) {

            if($endpoint == WP_Tournament_Manager::$tournament_endpoints[0] && is_player_in_tournament($post->ID, $current_user->player_id) == true){

                //return tournamentCPT::$post_type;
                return "$post->post_type-$endpoint";
            } else {

                return "$post->post_type-$endpoint";
            }

        }

    }

    return tournamentCPT::$post_type;
}

function is_tournament_signup_open($tournament_id){

    $tournament_signup = new WPTM_Tournament_Signup();

    return $tournament_signup->is_tournament_signup_open($tournament_id);

}

function get_match_player_cards($match_id, $polling = false, $small = false){

    return matchCPT::match_up(array( 'match_id' => $match_id, 'polling' => $polling, 'small' => $small ));

}

function get_match_format($match_id){

    return matchCPT::match_format($match_id);

}

//moved to tournament helper
function is_tournament_in_progress(){

    global $wpdb;

    $page_id = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'page_on_front'" );

    if(get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress-2.php'  || get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress.php')
        return true;

    return false;
}

function get_player_avatar($player_id, $size = 100){

     return playerCPT::get_player_avatar($player_id, $size);

}


function is_player_in_tournament($tournament_id, $player_id, $status = array()){

    $p2p_id = p2p_type( 'tournament_players' )->get_p2p_id( $tournament_id, $player_id );

    //if status provided check aginst those
    if( in_array( p2p_get_meta($p2p_id, 'status', true), $status ) ){
        return true;
    }

    //no status provide just in tournament
    if( count( $status ) == 0 && $p2p_id != '' ) {
        return true;
    }

    return false;

}

//moved to helper

function is_tournament_clan_contact($tournament_id, $clan_tag){

    global $current_user;

    $player_id = $current_user->player_id;

    $p2p_id = p2p_type( 'tournament_players' )->get_p2p_id( $tournament_id, $player_id );

    $clan_contact = p2p_get_meta( $p2p_id, 'clan_contact', true );
    $current_clan = get_current_user_clan();

    if ( $clan_contact == 1 && $current_clan === $clan_tag)
        return true;

    return false;

}

//moved to clan
function is_clan_leader($clan_tag){

    global $current_user;

    $player_id = $current_user->player_id;

    if(get_post_meta($player_id, 'clan', true) === $clan_tag && get_post_meta($player_id, 'clan_leader', true))
        return true;

    return false;

}

function can_edit_match_roster($tournament_id, $clan_tag){

    if(is_tournament_clan_contact($tournament_id, $clan_tag) || is_clan_leader($clan_tag))
        return true;

    return false;

}

//moved to tournament helper
function get_tournament_type($tournament_id){

    return get_post_meta($tournament_id, 'tournament_format', true);

}


function get_match_commentators($match_id){

    $commentators = get_users( array(
        'connected_type' => 'match_commentators',
        'connected_items' => $match_id
    ) );

    if(count($commentators) == 0)
        return '';

    foreach($commentators as $commentator){

        if($player_id = get_user_meta($commentator->ID, 'player_id', true)){
            $commentators_str[] = sprintf('<a href="%s">%s</a>', get_permalink($player_id), $commentator->display_name);
        } else {
            $commentators_str[] = $commentator->display_name;
        }

    }



    return implode(', ', $commentators_str);

}

function get_tournament_badges(){

    ?><a href="http://dev.exodusesports.com/tournament/king-of-the-planet-5/"><img src="<?php echo get_template_directory_uri();?>/public/img/icon_kotp.png" alt="King of the planet!" /></a><?php

}

function do_offset($level){
    $offset = "";             // offset for subarry
    for ($i=1; $i<$level;$i++){
        $offset = $offset . "<td></td>";
    }
    return $offset;
}

function show_array($array, $level, $sub, $html = ''){
    if (is_array($array) == 1){          // check if input is an array
        foreach($array as $key_val => $value) {
            $offset = "";
            if (is_array($value) == 1){   // array is multidimensional
                $html .= "<tr>";
                $offset = do_offset($level);
                $html .= $offset . "<td>" . $key_val . "</td>";
                $html .= show_array($value, $level+1, 1);
            }
            else{                        // (sub)array is not multidim
                if ($sub != 1){          // first entry for subarray
                    $html .= "<tr nosub>";
                    $offset = do_offset($level);
                }
                $sub = 0;
                $html .= $offset . "<td main ".$sub." width=\"120\">" . $key_val .
                    "</td><td width=\"120\">" . $value . "</td>";
                $html .= "</tr>\n";
            }
        } //foreach $array
    }
    else{ // argument $array is not an array
        return;
    }

    return $html;
}

function html_show_array($array, $html = ''){
    $html .= "<table cellspacing=\"0\" border=\"0\" cellpadding=\"0\">\n";
    $html .= show_array($array, 1, 0);
    $html .= "</table>\n";

    return $html;
}

function wptm_get_post_type($post = null, $status = false){

    if ( $post = get_post( $post ) ) {

        switch ($post->post_type) {

            case "tournamentCPT::$post_type" :

                if ($status)
                    return $post->post_type . '-' .$post->tournament_status;


            break;

            default :

                return $post->post_type;

        }

    }

    return false;

}


class DW_Helper {

    public static function get_post_by_meta($meta_key, $meta_value){

        global $wpdb;

        $statment = $wpdb->prepare(
            "
                SELECT post_id
                FROM $wpdb->postmeta
                WHERE meta_value = %s AND meta_key = '%s' LIMIT 1
            ",
            $meta_value,
            $meta_key
        );

        $post_id = $wpdb->get_var($statment);

        if($post_id)
            return get_post($post_id);

        return false;

    }

    public static function is_site_administrator(){

        global $current_user;

        if (!empty($current_user->roles)) {
            foreach ($current_user->roles as $key => $value) {
                if ($value == 'administrator') {
                    return true;
                }
            }
        }

        return false;

    }

    public static function generate_post_select($select_id, $post_type, $selected = 0) {
        $post_type_object = get_post_type_object($post_type);
        $label = $post_type_object->label;
        $posts = get_posts(array('post_type'=> $post_type, 'post_status'=> 'publish', 'suppress_filters' => false, 'posts_per_page'=>-1));
        echo '<select name="'. $select_id .'" id="'.$select_id.'">';
        echo '<option value = "" >All '.$label.' </option>';
        foreach ($posts as $post) {
            echo '<option value="', $post->ID, '"', $selected == $post->ID ? ' selected="selected"' : '', '>', $post->post_title, '</option>';
        }
        echo '</select>';
    }

    public static function generate_select($select_id, $value_array, $selected = 0) {

        echo '<select name="'. $select_id .'" id="'.$select_id.'">';

        echo '<option value = "" > </option>';

        foreach ( $value_array as $key => $value ) {

            echo '<option value="', $key, '"', $selected == $key ? ' selected="selected"' : '', '>', $value, '</option>';

        }

        echo '</select>';

    }

    public static function countryListing(){
        $_countries = array(
            "GB" => "United Kingdom",
            "US" => "United States",
            "AF" => "Afghanistan",
            "AL" => "Albania",
            "DZ" => "Algeria",
            "AS" => "American Samoa",
            "AD" => "Andorra",
            "AO" => "Angola",
            "AI" => "Anguilla",
            "AQ" => "Antarctica",
            "AG" => "Antigua And Barbuda",
            "AR" => "Argentina",
            "AM" => "Armenia",
            "AW" => "Aruba",
            "AU" => "Australia",
            "AT" => "Austria",
            "AZ" => "Azerbaijan",
            "BS" => "Bahamas",
            "BH" => "Bahrain",
            "BD" => "Bangladesh",
            "BB" => "Barbados",
            "BY" => "Belarus",
            "BE" => "Belgium",
            "BZ" => "Belize",
            "BJ" => "Benin",
            "BM" => "Bermuda",
            "BT" => "Bhutan",
            "BO" => "Bolivia",
            "BA" => "Bosnia And Herzegowina",
            "BW" => "Botswana",
            "BV" => "Bouvet Island",
            "BR" => "Brazil",
            "IO" => "British Indian Ocean Territory",
            "BN" => "Brunei Darussalam",
            "BG" => "Bulgaria",
            "BF" => "Burkina Faso",
            "BI" => "Burundi",
            "KH" => "Cambodia",
            "CM" => "Cameroon",
            "CA" => "Canada",
            "CV" => "Cape Verde",
            "KY" => "Cayman Islands",
            "CF" => "Central African Republic",
            "TD" => "Chad",
            "CL" => "Chile",
            "CN" => "China",
            "CX" => "Christmas Island",
            "CC" => "Cocos (Keeling) Islands",
            "CO" => "Colombia",
            "KM" => "Comoros",
            "CG" => "Congo",
            "CD" => "Congo, The Democratic Republic Of The",
            "CK" => "Cook Islands",
            "CR" => "Costa Rica",
            "CI" => "Cote D'Ivoire",
            "HR" => "Croatia (Local Name: Hrvatska)",
            "CU" => "Cuba",
            "CY" => "Cyprus",
            "CZ" => "Czech Republic",
            "DK" => "Denmark",
            "DJ" => "Djibouti",
            "DM" => "Dominica",
            "DO" => "Dominican Republic",
            "TP" => "East Timor",
            "EC" => "Ecuador",
            "EG" => "Egypt",
            "SV" => "El Salvador",
            "GQ" => "Equatorial Guinea",
            "ER" => "Eritrea",
            "EE" => "Estonia",
            "ET" => "Ethiopia",
            "FK" => "Falkland Islands (Malvinas)",
            "FO" => "Faroe Islands",
            "FJ" => "Fiji",
            "FI" => "Finland",
            "FR" => "France",
            "FX" => "France, Metropolitan",
            "GF" => "French Guiana",
            "PF" => "French Polynesia",
            "TF" => "French Southern Territories",
            "GA" => "Gabon",
            "GM" => "Gambia",
            "GE" => "Georgia",
            "DE" => "Germany",
            "GH" => "Ghana",
            "GI" => "Gibraltar",
            "GR" => "Greece",
            "GL" => "Greenland",
            "GD" => "Grenada",
            "GP" => "Guadeloupe",
            "GU" => "Guam",
            "GT" => "Guatemala",
            "GN" => "Guinea",
            "GW" => "Guinea-Bissau",
            "GY" => "Guyana",
            "HT" => "Haiti",
            "HM" => "Heard And Mc Donald Islands",
            "VA" => "Holy See (Vatican City State)",
            "HN" => "Honduras",
            "HK" => "Hong Kong",
            "HU" => "Hungary",
            "IS" => "Iceland",
            "IN" => "India",
            "ID" => "Indonesia",
            "IR" => "Iran (Islamic Republic Of)",
            "IQ" => "Iraq",
            "IE" => "Ireland",
            "IL" => "Israel",
            "IT" => "Italy",
            "JM" => "Jamaica",
            "JP" => "Japan",
            "JO" => "Jordan",
            "KZ" => "Kazakhstan",
            "KE" => "Kenya",
            "KI" => "Kiribati",
            "KP" => "Korea, Democratic People's Republic Of",
            "KR" => "Korea, Republic Of",
            "KW" => "Kuwait",
            "KG" => "Kyrgyzstan",
            "LA" => "Lao People's Democratic Republic",
            "LV" => "Latvia",
            "LB" => "Lebanon",
            "LS" => "Lesotho",
            "LR" => "Liberia",
            "LY" => "Libyan Arab Jamahiriya",
            "LI" => "Liechtenstein",
            "LT" => "Lithuania",
            "LU" => "Luxembourg",
            "MO" => "Macau",
            "MK" => "Macedonia, Former Yugoslav Republic Of",
            "MG" => "Madagascar",
            "MW" => "Malawi",
            "MY" => "Malaysia",
            "MV" => "Maldives",
            "ML" => "Mali",
            "MT" => "Malta",
            "MH" => "Marshall Islands",
            "MQ" => "Martinique",
            "MR" => "Mauritania",
            "MU" => "Mauritius",
            "YT" => "Mayotte",
            "MX" => "Mexico",
            "FM" => "Micronesia, Federated States Of",
            "MD" => "Moldova, Republic Of",
            "MC" => "Monaco",
            "MN" => "Mongolia",
            "MS" => "Montserrat",
            "MA" => "Morocco",
            "MZ" => "Mozambique",
            "MM" => "Myanmar",
            "NA" => "Namibia",
            "NR" => "Nauru",
            "NP" => "Nepal",
            "NL" => "Netherlands",
            "AN" => "Netherlands Antilles",
            "NC" => "New Caledonia",
            "NZ" => "New Zealand",
            "NI" => "Nicaragua",
            "NE" => "Niger",
            "NG" => "Nigeria",
            "NU" => "Niue",
            "NF" => "Norfolk Island",
            "MP" => "Northern Mariana Islands",
            "NO" => "Norway",
            "OM" => "Oman",
            "PK" => "Pakistan",
            "PW" => "Palau",
            "PA" => "Panama",
            "PG" => "Papua New Guinea",
            "PY" => "Paraguay",
            "PE" => "Peru",
            "PH" => "Philippines",
            "PN" => "Pitcairn",
            "PL" => "Poland",
            "PT" => "Portugal",
            "PR" => "Puerto Rico",
            "QA" => "Qatar",
            "RE" => "Reunion",
            "RO" => "Romania",
            "RU" => "Russian Federation",
            "RW" => "Rwanda",
            "KN" => "Saint Kitts And Nevis",
            "LC" => "Saint Lucia",
            "VC" => "Saint Vincent And The Grenadines",
            "WS" => "Samoa",
            "SM" => "San Marino",
            "ST" => "Sao Tome And Principe",
            "SA" => "Saudi Arabia",
            "SN" => "Senegal",
            "SC" => "Seychelles",
            "SL" => "Sierra Leone",
            "SG" => "Singapore",
            "SK" => "Slovakia (Slovak Republic)",
            "SI" => "Slovenia",
            "SB" => "Solomon Islands",
            "SO" => "Somalia",
            "ZA" => "South Africa",
            "GS" => "South Georgia, South Sandwich Islands",
            "ES" => "Spain",
            "LK" => "Sri Lanka",
            "SH" => "St. Helena",
            "PM" => "St. Pierre And Miquelon",
            "SD" => "Sudan",
            "SR" => "Suriname",
            "SJ" => "Svalbard And Jan Mayen Islands",
            "SZ" => "Swaziland",
            "SE" => "Sweden",
            "CH" => "Switzerland",
            "SY" => "Syrian Arab Republic",
            "TW" => "Taiwan",
            "TJ" => "Tajikistan",
            "TZ" => "Tanzania, United Republic Of",
            "TH" => "Thailand",
            "TG" => "Togo",
            "TK" => "Tokelau",
            "TO" => "Tonga",
            "TT" => "Trinidad And Tobago",
            "TN" => "Tunisia",
            "TR" => "Turkey",
            "TM" => "Turkmenistan",
            "TC" => "Turks And Caicos Islands",
            "TV" => "Tuvalu",
            "UG" => "Uganda",
            "UA" => "Ukraine",
            "AE" => "United Arab Emirates",
            "UM" => "United States Minor Outlying Islands",
            "UY" => "Uruguay",
            "UZ" => "Uzbekistan",
            "VU" => "Vanuatu",
            "VE" => "Venezuela",
            "VN" => "Viet Nam",
            "VG" => "Virgin Islands (British)",
            "VI" => "Virgin Islands (U.S.)",
            "WF" => "Wallis And Futuna Islands",
            "EH" => "Western Sahara",
            "YE" => "Yemen",
            "YU" => "Yugoslavia",
            "ZM" => "Zambia",
            "ZW" => "Zimbabwe"
        );

        return $_countries;

    }

}

$DW_helper = new DW_Helper();