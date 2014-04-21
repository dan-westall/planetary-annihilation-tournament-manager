<?php

if(function_exists("register_field_group"))
{
    register_field_group(array (
        'id' => 'acf_match-meta',
        'title' => 'Match Meta',
        'fields' => array (
            array (
                'key' => 'field_53557b83039b7',
                'label' => 'Challonge Match ID',
                'name' => 'challonge_match_id',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_53557b9b039b8',
                'label' => 'Challonge Tournament ID',
                'name' => 'challonge_tournament_id',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_53557ba6039b9',
                'label' => 'PA Stats match ID',
                'name' => 'pa_stats_match_id',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_53557bca039ba',
                'label' => 'PA Stats start',
                'name' => 'pa_stats_start',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
        ),
        'location' => array (
            array (
                array (
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'match',
                    'order_no' => 0,
                    'group_no' => 0,
                ),
            ),
        ),
        'options' => array (
            'position' => 'normal',
            'layout' => 'default',
            'hide_on_screen' => array (
            ),
        ),
        'menu_order' => 0,
    ));
    register_field_group(array (
        'id' => 'acf_player-profile-admin',
        'title' => 'Player profile admin',
        'fields' => array (
            array (
                'key' => 'field_5353e2a7c221e',
                'label' => 'User profile link',
                'name' => 'user_id',
                'type' => 'user',
                'role' => array (
                    0 => 'all',
                ),
                'field_type' => 'select',
                'allow_null' => 1,
            ),
        ),
        'location' => array (
            array (
                array (
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'player',
                    'order_no' => 0,
                    'group_no' => 0,
                ),
                array (
                    'param' => 'user_type',
                    'operator' => '==',
                    'value' => 'administrator',
                    'order_no' => 1,
                    'group_no' => 0,
                ),
            ),
        ),
        'options' => array (
            'position' => 'normal',
            'layout' => 'default',
            'hide_on_screen' => array (
            ),
        ),
        'menu_order' => 0,
    ));
    register_field_group(array (
        'id' => 'acf_temp-user-profile',
        'title' => 'Temp User profile',
        'fields' => array (
            array (
                'key' => 'field_53226b3245ed1',
                'label' => 'Twitch url',
                'name' => 'twitch_url',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
        ),
        'location' => array (
            array (
                array (
                    'param' => 'ef_user',
                    'operator' => '==',
                    'value' => 'all',
                    'order_no' => 0,
                    'group_no' => 0,
                ),
            ),
        ),
        'options' => array (
            'position' => 'normal',
            'layout' => 'default',
            'hide_on_screen' => array (
            ),
        ),
        'menu_order' => 0,
    ));
    register_field_group(array (
        'id' => 'acf_tournament-meta',
        'title' => 'Tournament Meta',
        'fields' => array (
            array (
                'key' => 'field_531a3508690d8',
                'label' => 'Tournament Meta',
                'name' => '',
                'type' => 'tab',
            ),
            array (
                'key' => 'field_531a31bfb8693',
                'label' => 'Run Date',
                'name' => 'run_date',
                'type' => 'date_picker',
                'date_format' => 'yymmdd',
                'display_format' => 'dd/mm/yy',
                'first_day' => 1,
            ),
            array (
                'key' => 'field_531a31ddb8694',
                'label' => 'Run time',
                'name' => 'run_time',
                'type' => 'text',
                'instructions' => 'Please include timezone',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_531a31ffb8695',
                'label' => 'Prize Tiers',
                'name' => 'prize_tiers',
                'type' => 'repeater',
                'sub_fields' => array (
                    array (
                        'key' => 'field_531a3216b8696',
                        'label' => 'Place',
                        'name' => 'place',
                        'type' => 'text',
                        'column_width' => '',
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'formatting' => 'html',
                        'maxlength' => '',
                    ),
                    array (
                        'key' => 'field_531a3235b8697',
                        'label' => 'Prize',
                        'name' => 'prize',
                        'type' => 'text',
                        'column_width' => '',
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'formatting' => 'html',
                        'maxlength' => '',
                    ),
                ),
                'row_min' => '',
                'row_limit' => '',
                'layout' => 'table',
                'button_label' => 'Add Row',
            ),
            array (
                'key' => 'field_531a366dbeaf6',
                'label' => 'Rounds',
                'name' => 'rounds',
                'type' => 'number',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'min' => 0,
                'max' => '',
                'step' => '',
            ),
            array (
                'key' => 'field_531a377136494',
                'label' => 'Slots',
                'name' => 'slots',
                'type' => 'number',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'min' => 0,
                'max' => '',
                'step' => '',
            ),
            array (
                'key' => 'field_53226662e5c75',
                'label' => 'Tournament Type',
                'name' => 'tournament_type',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_531a32947c201',
                'label' => 'Brackets Link',
                'name' => 'brackets',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_531a34d9a5387',
                'label' => 'Forum Link',
                'name' => 'forum_link',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_531a32dd7c203',
                'label' => 'IRC',
                'name' => 'irc',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
            array (
                'key' => 'field_531a33687c204',
                'label' => 'Schedule',
                'name' => '',
                'type' => 'tab',
            ),
            array (
                'key' => 'field_531a337d7c205',
                'label' => 'Schedule',
                'name' => 'schedule',
                'type' => 'repeater',
                'sub_fields' => array (
                    array (
                        'key' => 'field_531a338e7c206',
                        'label' => 'Time',
                        'name' => 'time',
                        'type' => 'text',
                        'column_width' => 20,
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'formatting' => 'html',
                        'maxlength' => '',
                    ),
                    array (
                        'key' => 'field_531a33a27c207',
                        'label' => 'Date',
                        'name' => 'date',
                        'type' => 'date_picker',
                        'instructions' => 'Leave blank system will assume schedule is to happen on the same day unless date is entered.',
                        'column_width' => 20,
                        'date_format' => 'yymmdd',
                        'display_format' => 'dd/mm/yy',
                        'first_day' => 1,
                    ),
                    array (
                        'key' => 'field_531a33ce7c208',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                        'column_width' => '',
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'formatting' => 'html',
                        'maxlength' => '',
                    ),
                ),
                'row_min' => '',
                'row_limit' => '',
                'layout' => 'table',
                'button_label' => 'Add Row',
            ),
            array (
                'key' => 'field_531a33e5a5380',
                'label' => 'Tournament Rules & Requirements and Other',
                'name' => '',
                'type' => 'tab',
            ),
            array (
                'key' => 'field_531a3400a5381',
                'label' => 'Requirements',
                'name' => 'requirements',
                'type' => 'wysiwyg',
                'default_value' => '',
                'toolbar' => 'full',
                'media_upload' => 'yes',
            ),
            array (
                'key' => 'field_531a3436a5384',
                'label' => 'Existing Requirements',
                'name' => 'existing_requirements',
                'type' => 'post_object',
                'post_type' => array (
                    0 => 'page',
                    1 => 'tournament',
                ),
                'taxonomy' => array (
                    0 => 'all',
                ),
                'allow_null' => 1,
                'multiple' => 0,
            ),
            array (
                'key' => 'field_531a341ba5382',
                'label' => 'Rules',
                'name' => 'rules',
                'type' => 'wysiwyg',
                'default_value' => '',
                'toolbar' => 'full',
                'media_upload' => 'yes',
            ),
            array (
                'key' => 'field_531a3481a5385',
                'label' => 'Existing Rules',
                'name' => 'existing_rules',
                'type' => 'post_object',
                'post_type' => array (
                    0 => 'all',
                ),
                'taxonomy' => array (
                    0 => 'all',
                ),
                'allow_null' => 1,
                'multiple' => 0,
            ),
            array (
                'key' => 'field_531a3428a5383',
                'label' => 'Other',
                'name' => 'other',
                'type' => 'wysiwyg',
                'default_value' => '',
                'toolbar' => 'full',
                'media_upload' => 'yes',
            ),
            array (
                'key' => 'field_531a348fa5386',
                'label' => 'Existing Other',
                'name' => 'existing_rules',
                'type' => 'post_object',
                'post_type' => array (
                    0 => 'all',
                ),
                'taxonomy' => array (
                    0 => 'all',
                ),
                'allow_null' => 1,
                'multiple' => 0,
            ),
            array (
                'key' => 'field_531a3540ca568',
                'label' => 'Tournament Setup',
                'name' => '',
                'type' => 'tab',
            ),
            array (
                'key' => 'field_531e5feaf3700',
                'label' => 'Signup Intro Text',
                'name' => 'signup_intro_text',
                'type' => 'wysiwyg',
                'default_value' => '',
                'toolbar' => 'full',
                'media_upload' => 'yes',
            ),
            array (
                'key' => 'field_531a329d7c202',
                'label' => 'Signup Form',
                'name' => 'signup_form',
                'type' => 'select',
                'instructions' => 'Will default to the system default signup form, unless a new form is selected here.',
                'choices' => array (
                    2 => 'Contact',
                    1 => 'Tournament Signup',
                ),
                'default_value' => '',
                'allow_null' => 1,
                'multiple' => 0,
            ),
            array (
                'key' => 'field_532263dad2da7',
                'label' => 'Signup Closed',
                'name' => 'signup_closed',
                'type' => 'true_false',
                'instructions' => 'If you want to close the tournament signups for what ever reason tick this box',
                'message' => '',
                'default_value' => 0,
            ),
            array (
                'key' => 'field_531a363abeaf4',
                'label' => 'Planet Setup',
                'name' => 'planet_setup',
                'type' => 'repeater',
                'sub_fields' => array (
                    array (
                        'key' => 'field_531a3649beaf5',
                        'label' => 'Planet Meta',
                        'name' => 'planet_meta',
                        'type' => 'text',
                        'column_width' => '',
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'formatting' => 'html',
                        'maxlength' => '',
                    ),
                ),
                'row_min' => '',
                'row_limit' => '',
                'layout' => 'table',
                'button_label' => 'Add Row',
            ),
            array (
                'key' => 'field_533864885ce19',
                'label' => 'Challonge Tournament Link',
                'name' => 'challonge_tournament_link',
                'type' => 'select',
                'instructions' => 'Select a tournament from the challonge DB, user will be inserted into selected tournament on signup.',
                'choices' => array (
                    0 => 'Select Tournament',
                    1 => 'Custom Tournament ID',
                    880499 => 'I am test',
                ),
                'default_value' => '',
                'allow_null' => 0,
                'multiple' => 0,
            ),
            array (
                'key' => 'field_5338825672b61',
                'label' => 'Custom tournament id',
                'name' => 'custom_tournament_id',
                'type' => 'text',
                'conditional_logic' => array (
                    'status' => 1,
                    'rules' => array (
                        array (
                            'field' => 'field_533864885ce19',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                    'allorany' => 'all',
                ),
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
        ),
        'location' => array (
            array (
                array (
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'tournament',
                    'order_no' => 0,
                    'group_no' => 0,
                ),
            ),
        ),
        'options' => array (
            'position' => 'normal',
            'layout' => 'default',
            'hide_on_screen' => array (
            ),
        ),
        'menu_order' => 0,
    ));
    register_field_group(array (
        'id' => 'acf_user-profile',
        'title' => 'User Profile',
        'fields' => array (
            array (
                'key' => 'field_5353e38c75c10',
                'label' => 'Your Email',
                'name' => 'player_email',
                'type' => 'email',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ),
            array (
                'key' => 'field_5353e3a175c11',
                'label' => 'Profile text',
                'name' => 'profile_text',
                'type' => 'wysiwyg',
                'default_value' => '',
                'toolbar' => 'full',
                'media_upload' => 'yes',
            ),
            array (
                'key' => 'field_53555eb579656',
                'label' => 'Pastats player id',
                'name' => 'pastats_player_id',
                'type' => 'text',
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'formatting' => 'html',
                'maxlength' => '',
            ),
        ),
        'location' => array (
            array (
                array (
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'player',
                    'order_no' => 0,
                    'group_no' => 0,
                ),
            ),
        ),
        'options' => array (
            'position' => 'normal',
            'layout' => 'default',
            'hide_on_screen' => array (
            ),
        ),
        'menu_order' => 0,
    ));
}
