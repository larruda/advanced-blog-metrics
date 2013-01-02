<?php
/*
Plugin Name: Advanced Blog Metrics
Plugin URI: http://www.atalanta.fr/advanced-blog-metrics-wordpress-plugin
Description: Advanced Blog Metrics is an analytics tool dedicated to bloggers. This plugin allows you to improve your blog performance
Version: 1.2
Author: Atalanta
Author URI: http://www.atalanta.fr/
License: GPL2
*/

/* @var $wpdb wpdb */
global $wpdb;

// Weekdays
$days = array( 'Monday', 'Thuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

// Queries SQL
$queries = array(
    // 5 posts which generate the most comments
    'comments_per_post'      => "SELECT ID AS id, post_title, comment_count FROM `" . $wpdb->posts . "` WHERE post_type = 'post' AND post_status = 'publish' AND comment_count > 0 ORDER BY comment_count DESC LIMIT 5",
    // When do your posts generate the most comments?
    'comments_per_day'       => "SELECT COUNT(comment_ID) AS count, DATE_FORMAT(comment_date, '%w') AS day FROM `" . $wpdb->comments . "` WHERE comment_approved = 1 AND DATE_FORMAT(comment_date, '%w') IS NOT NULL GROUP BY DATE_FORMAT(comment_date, '%w') ORDER BY day ASC",
    // Total comments approved
    'comments_total'         => "SELECT COUNT(comment_ID) AS count FROM `" . $wpdb->comments . "` WHERE comment_approved = '1'",
    // Total words per comments approved
    'comments_word'          => "SELECT SUM(LENGTH(comment_content) - LENGTH(REPLACE(comment_content, ' ', ''))+1) AS count FROM `" . $wpdb->comments . "` WHERE comment_approved = '1'",
    // 5 authors who comment the most
    'comments_per_author'    => "SELECT u.ID, u.user_login, u.display_name, COUNT(c.comment_ID) AS comment_count FROM `" . $wpdb->comments . "` c LEFT JOIN `" . $wpdb->users . "` u ON c.user_id = u.ID WHERE c.user_id != 0 GROUP BY c.user_id ORDER BY comment_count DESC LIMIT 5",
    // Total posts published
    'posts_total'            => "SELECT COUNT(ID) FROM `" . $wpdb->posts . "` WHERE post_type = 'post' AND post_status = 'publish'",    
    // Total words per posts approved
    'posts_word'             => "SELECT SUM(LENGTH(post_content) - LENGTH(REPLACE(post_content, ' ', ''))+1) AS count FROM `" . $wpdb->posts . "` WHERE post_status = 'publish'",        
    // Date of first post
    'first_post'             => "SELECT post_date FROM `" . $wpdb->posts . "` WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date ASC LIMIT 1"
);

// Comment Registration required
$commentregistration = (bool)get_option('comment_registration');

// Options 
$options = (array)get_option('abm_options');

// Starting date
if (!empty($options['starting_date'])) {
    $startingdate = $options['starting_date'] . ' 00:00:00';
} else {
    $startingdate = (string)current( $wpdb->get_col( $queries['first_post'], 0 ) );  
}

// Elapsed days
$elapseddays = date_diff_days( $startingdate, date( 'Y-m-d H:i:s' ) );    

add_action( 'admin_init', 'abm_admin_init' );
add_action( 'admin_menu', 'abm_admin_menu' );
add_action( 'wp_dashboard_setup', 'abm_dashboard_init' );

add_filter( 'plugin_action_links_'.  plugin_basename( __FILE__ ), 'abm_plugin_action_links', 10, 2 );

function abm_admin_init() {
    wp_register_style( 'advanced-blog-metrics', plugins_url( 'style.css', __FILE__ ) );
    wp_enqueue_style( 'advanced-blog-metrics' );
    
    register_setting( 'abm_options', 'abm_options', 'abm_options_validate' );
    add_settings_section( 'abm_options_general', 'Settings', 'abm_options_general_text', 'abm_options' );
    add_settings_field( 'abm_options_starting_date', '<label for="abm_options_starting_date">Starting date</label>', 'abm_options_starting_date_text', 'abm_options', 'abm_options_general' );
}

function abm_admin_menu() {
    add_menu_page( 'Advanced Blog Metrics', 'Advanced<br />Blog Metrics', 'administrator', 'advanced-blog-metrics', 'abm_options_page' );
}

function abm_dashboard_init() {
    wp_add_dashboard_widget( 'dashboard_comments_per_post', '5 posts which generate the most comments', 'dashboard_comments_per_post' );
    wp_add_dashboard_widget( 'dashboard_comments_per_day', 'When do your posts generate the most comments?', 'dashboard_comments_per_day' );
    wp_add_dashboard_widget( 'dashboard_comments', 'Comments', 'dashboard_comments' );
    wp_add_dashboard_widget( 'dashboard_comments_per_author', '5 authors who comment the most', 'dashboard_comments_per_author' );
    wp_add_dashboard_widget( 'dashboard_posts', 'Posts', 'dashboard_posts' );
}

function abm_plugin_action_links( $links, $file ) {
    array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=advanced-blog-metrics' ) . '">' . __( 'Settings' ) . '</a>' );
    return $links;
}

function abm_options_page() {
    ob_start();
    echo '<div class="wrap">';
    echo '<h2>Advanced Blog Metrics</h2>';
    echo '<form action="options.php" method="post">';
    settings_errors();
    settings_fields( 'abm_options' );
    do_settings_sections( 'abm_options' );
    submit_button();
    echo '</form>';
    echo '</div>';
    ob_end_flush();
}

function abm_options_general_text() { }

function abm_options_starting_date_text() {
    $options = get_option( 'abm_options' );
    echo '<input type="text" size="12" maxlength="10" id="abm_options_starting_date" name="abm_options[starting_date]" value="'.$options['starting_date'].'" /><span id="local-time">Date format: YYYY-MM-DD</span>';
    echo '<p class="description">If you leave this field empty, Advanced Blog Metrics uses the date of your first post by default.</p>';
}

function abm_options_validate($posted) {
    $options = get_option( 'abm_options' );
    $cleaned = array();
    
    if ( !empty( $posted['starting_date'] ) && !preg_match( '`[0-9]{4}\-[0-9]{2}\-[0-9]{2}`', $posted['starting_date'] ) ) {
        add_settings_error('abm_options_starting_date', 'abm_options_starting_date_bad_format', 'You did not use the expected date format. Please, fill in the starting date with the following format: YYYY-MM-DD');        
    } elseif ( !empty( $posted['starting_date'] ) ) {
        list( $year, $month, $day ) = explode( '-', $posted['starting_date'] );
        if ( !checkdate( $month, $day, $year ) ) {
            add_settings_error('abm_options_starting_date', 'abm_options_starting_date_not_valid', 'You have entered a date which does not exist. Please, fill in the starting date with a valid date.');
        }                
    }
    
    if ( count( get_settings_errors() ) > 0 && array_key_exists( 'starting_date', $options ) ) {
        $cleaned['starting_date'] = $options['starting_date'];
    } else {
        $cleaned['starting_date'] = $posted['starting_date'];
    }
    
    return $cleaned;
}

// Posts which generate the most comments
function dashboard_comments_per_post() {
    global $queries, $wpdb;
    $posts = $wpdb->get_results( $queries['comments_per_post'] );
    $html = '<table cellpadding="0" cellspacing="0" class="table-list">';
    $html.= '<thead><tr><th width="85%">Post</th><th>Comments</th></tr></thead>';
    $html.= '<tbody>';
    foreach ( $posts as $post ) {
        $html.= '<tr>';
        $html.= '<th><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></th>';
        $html.= '<td class="comment_count">' . $post->comment_count . '</td>';
        $html.= '</tr>';
    }
    $html.= '</tbody>';
    $html.= '</table>';
    echo $html;
}

// When do your posts generate the most comments?
function dashboard_comments_per_day() {
    global $queries, $wpdb, $days;
    $posts = $wpdb->get_col( $queries['comments_per_day'], 0 );
    $max = max($posts);
    $html.= '<table cellpadding="0" cellspacing="0">';
    $html.= '<tr>';
    for ($num_day = 0; $num_day <= 6; $num_day++) {
        $html.= '<td class="value' . ($max == $posts[$num_day] ? ' max' : '') . '">';
        $html.= '<span class="count">' . $posts[$num_day] . '</span>';
        $html.= '<div class="pourcent" style="height: ' . round( ( $posts[$num_day] / $max ) * 150, 0 ) . 'px"></div>';
        $html.= '</td>';
    }
    $html.= '</tr><tr>';
    for ($num_day = 0; $num_day <= 6; $num_day++) {
        $html.= '<th class="day' . ( $max == $posts[$num_day] ? ' max' : '' ) . '"><span>' . strtoupper( substr( $days[$num_day], 0, 3 ) ) . '</span></th>';
    }
    $html.= '</tr>';
    $html.= '</table>';
    echo $html;
}
    
// Comments
function dashboard_comments() {   
    global $queries, $wpdb, $elapseddays;
    $total = current( $wpdb->get_col( $queries['comments_total'], 0 ) );
    $posts = current( $wpdb->get_col( $queries['posts_total'], 0 ) );
    $words = current( $wpdb->get_col( $queries['comments_word'], 0 ) );
    $html.= '<table cellpadding="0" cellspacing="0" class="table-list table-summary">';
        $html.= '<thead>';
            $html.= '<tr>';
                $html .= '<th width="25%">Approved comments</th>';
                $html .= '<th width="25%">Comments per Day</th>';
                $html .= '<th width="25%">Comments per Post</th>';
                $html .= '<th width="25%">Words per Comment</th>';
            $html.= '</tr>';
        $html.= '</thead>';
        $html.= '<tbody>';
            $html.= '<tr>';
                $html.= '<td class="number"><span>' . $total . '</span></td>';
                $html.= '<td class="number"><span>' . round( $total / $elapseddays, 2 ) . '</span></td>';
                $html.= '<td class="number"><span>' . round( $total / $posts, 1 ) . '</span></td>';
                $html.= '<td class="number"><span>' . round( $words / $total ) . '</span></td>';
            $html.= '</tr>';
        $html.= '</tbody>';
    $html.= '</table>';
    echo $html;        
}
    
// Authors who comment the most
function dashboard_comments_per_author() {
    global $queries, $wpdb, $commentregistration;
    if ($commentregistration) {
        $authors = $wpdb->get_results($queries['comments_per_author']);
    }
    $html = '<table cellpadding="0" cellspacing="0" class="table-list">';
    $html.= '<thead><tr><th width="85%">Author</th><th>Comments</th></tr></thead><tbody>';
    if ($commentregistration) {
        foreach ($authors as $author) {
            $html.= '<tr>';
            $html.= '<th><a href="' . get_admin_url() . 'user-edit.php?user_id='.$author->ID . '" title="Modifier">' . $author->display_name . '</a></th>';
            $html.= '<td class="comment_count">' . $author->comment_count . '</td>';
            $html.= '</tr>';
        }
    } else {
        $html.= '<tr><td colspan="2"><p>Note that you need to check "Users must be registered and logged in to comment" in the Wordpress Settings->Discussion to see data in the "5 authors who comments the most" widget.</p><p><a class="button" href="' . get_admin_url() . 'options-discussion.php">View discussion options</p></td></tr>';
    }
    $html.= '</tbody></table>';
    echo $html;
}    

// Posts
function dashboard_posts() {
    global $queries, $wpdb, $elapseddays;
    $total = current( $wpdb->get_col( $queries['posts_total'], 0 ) );
    $comments = current( $wpdb->get_col( $queries['comments_total'], 0 ) );
    $words = current( $wpdb->get_col( $queries['posts_word'], 0 ) );
    $html.= '<table cellpadding="0" cellspacing="0" class="table-list table-summary">';
    $html.= '<thead><tr>';
    $html .= '<th width="25%">Posts</th>';
    $html .= '<th width="25%">Posts per Day</th>';
    $html .= '<th width="25%">Comments per Post</th>';
    $html .= '<th width="25%">Words per Post</th>';
    $html.= '</tr></thead><tbody><tr>';
    $html.= '<td class="number"><span>' . $total . '</span></td>';
    $html.= '<td class="number"><span>' . round( $total / $elapseddays, 2 ) . '</span></td>';
    $html.= '<td class="number"><span>' . round( $comments / $total, 1 ) . '</span></td>';
    $html.= '<td class="number"><span>' . round( $words / $total ) . '</span></td>';
    $html.= '</tr></tbody>';
    $html.= '</table>';
    echo $html;        
}

function date_diff_days($date1, $date2) {    
    $s = strtotime( $date2 ) - strtotime( $date1 );
    $d = intval( $s / 86400 ) + 1;  
    return $d;
} 