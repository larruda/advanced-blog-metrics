<?php
/*
Plugin Name: Advanced Blog Metrics
Plugin URI: http://www.atalanta.fr/advanced-blog-metrics-wordpress-plugin
Description: Advanced Blog Metrics is an analytics tool dedicated to bloggers. This plugin allows you to improve your blog performance
Version: 1.4.2
Author: Atalanta
Author URI: http://www.atalanta.fr/
License: GPL2
*/

/* @var $wpdb wpdb */
global $wpdb;

/********** OPTIONS **********/
// general options of the plugin
$options = (array)get_option('abm_options');

// Comment Registration required
$commentregistration = (bool)get_option('comment_registration');
// start of week
$startofweek = get_option('start_of_week');

// Weekdays
$days = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );

// Starting date
if (!empty($options['starting_date'])) {
    $startingdate = $options['starting_date'] . ' 00:00:00';
} else {
    $query_first_post = "SELECT post_date FROM `" . $wpdb->posts . "` WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date ASC LIMIT 1";
    $startingdate = (string)current( $wpdb->get_col( $query_first_post, 0 ) );  
}

// Elapsed days
function date_diff_days($date1, $date2) {    
    $s = strtotime( $date2 ) - strtotime( $date1 );
    $d = intval( $s / 86400 ) + 1;  
    return $d;
}
$elapseddays = date_diff_days( $startingdate, date( 'Y-m-d H:i:s' ) );

// Queries SQL
$queries = array(
    // Date of first post
    //'first_post'             => "SELECT post_date FROM `" . $wpdb->posts . "` WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date ASC LIMIT 1",
    // 5 posts which generate the most comments
    'comments_per_post'      => "SELECT ID AS id, post_title, post_date, comment_count FROM `" . $wpdb->posts . "` WHERE post_type = 'post' AND post_status = 'publish' AND comment_count > 0 AND post_date >= '" . $startingdate . "' ORDER BY comment_count DESC LIMIT 5",
    // When do your posts generate the most comments?
    'comments_per_day'       => "SELECT COUNT(c.comment_ID) AS count, DATE_FORMAT(c.comment_date, '%w') AS day, p.post_date FROM `" . $wpdb->comments . "` c LEFT JOIN `" . $wpdb->posts . "` p ON c.comment_post_ID = p.ID WHERE comment_approved = 1 AND DATE_FORMAT(c.comment_date, '%w') IS NOT NULL AND p.post_date >= '" . $startingdate . "' GROUP BY DATE_FORMAT(comment_date, '%w') ORDER BY day ASC",
    // Total comments approved
    'comments_total'         => "SELECT COUNT(c.comment_ID) AS count FROM `" . $wpdb->comments . "` c LEFT JOIN `" . $wpdb->posts . "` p ON c.comment_post_ID = p.ID WHERE comment_approved = '1' AND p.post_date >= '" . $startingdate . "'",
     // Total posts published
    'posts_total'            => "SELECT COUNT(ID), post_date FROM `" . $wpdb->posts . "` WHERE post_type = 'post' AND post_status = 'publish' AND post_date >= '" . $startingdate . "'",    
    // Total words per comments approved
    'comments_word'          => "SELECT SUM(LENGTH(c.comment_content) - LENGTH(REPLACE(c.comment_content, ' ', '')) +1) AS count, p.post_date FROM `" . $wpdb->comments . "` c LEFT JOIN `" . $wpdb->posts . "` p ON c.comment_post_ID = p.ID WHERE c.comment_approved = '1' AND p.post_date >= '" . $startingdate . "'",
    // 5 authors who comment the most
    'comments_per_author'    => "SELECT u.ID, u.user_login, u.display_name, COUNT(c.comment_ID) AS comment_count FROM `" . $wpdb->comments . "` c LEFT JOIN `" . $wpdb->users . "` u ON c.user_id = u.ID LEFT JOIN `" . $wpdb->posts . "` p ON c.comment_post_ID = p.ID WHERE c.user_id != 0 AND p.post_date >= '" . $startingdate . "' GROUP BY c.user_id ORDER BY comment_count DESC LIMIT 5",
    // Total words per posts approved
    'posts_word'             => "SELECT SUM(LENGTH(post_content) - LENGTH(REPLACE(post_content, ' ', '')) +1) AS count FROM `" . $wpdb->posts . "` WHERE post_status = 'publish' AND  post_type = 'post' AND post_date >= '" . $startingdate . "'", 
    // When do you post ?
    'posts_per_day'          => "SELECT COUNT(ID) AS count, DATE_FORMAT(post_date, '%w') AS day FROM `" . $wpdb->posts . "` WHERE post_status = 'publish' AND post_type = 'post' AND post_date >= '" . $startingdate . "' GROUP BY DATE_FORMAT(post_date, '%w') ORDER BY day ASC",
    // 5 posts which generate the most Facebook shares and likes
    'posts'                  => "SELECT ID, post_title FROM `" . $wpdb->posts . "` WHERE post_status = 'publish' AND post_type = 'post' AND post_date >= '" . $startingdate . "'"
);

/********** ACTIONS **********/
add_action( 'admin_init', 'abm_admin_init' );
add_action( 'admin_menu', 'abm_admin_menu' );
add_filter( 'plugin_action_links_'.  plugin_basename( __FILE__ ), 'abm_plugin_action_links', 10, 2 );


function abm_admin_init() {
    wp_register_style( 'advanced-blog-metrics', plugins_url( 'style.css', __FILE__ ) );
    wp_enqueue_style( 'advanced-blog-metrics' );
    
    register_setting( 'abm_options', 'abm_options', 'abm_options_validate' );
    add_settings_section( 'abm_options_general', 'Settings', 'abm_options_general_text', 'abm_options' );
    add_settings_field( 'abm_options_starting_date', '<label for="abm_options_starting_date">Starting date</label>', 'abm_options_starting_date_text', 'abm_options', 'abm_options_general' );
    
    // access only for administrator
    if (current_user_can('administrator') ) {        
        add_action( 'wp_dashboard_setup', 'abm_dashboard_init' );    
    }
}

function abm_admin_menu() {
    add_menu_page( 'Advanced Blog Metrics', 'Advanced<br />Blog Metrics', 'administrator', 'advanced-blog-metrics', 'abm_options_page' );
}

/********** SETTINGS **********/
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
    echo '<input type="text" size="12" maxlength="10" id="abm_options_starting_date" name="abm_options[starting_date]" value="' . $options['starting_date'] . '" title="Date format: YYYY-MM-DD" /><span id="local-time">Date format: YYYY-MM-DD</span>';
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



/********** INIT **********/
function abm_dashboard_init() {
    // Widget #1
    wp_add_dashboard_widget( 'dashboard_comments_per_post', '5 posts which generate the most comments', 'dashboard_comments_per_post' );    
    // Widget #2
    wp_add_dashboard_widget( 'dashboard_comments_per_day', 'When do your posts generate the most comments?', 'dashboard_comments_per_day' );   
    // Widget #3
    wp_add_dashboard_widget( 'dashboard_comments', 'Comments', 'dashboard_comments' );   
    // Widget #4
    wp_add_dashboard_widget( 'dashboard_comments_per_author', '5 authors who comment the most', 'dashboard_comments_per_author' );   
    // Widget #5
    wp_add_dashboard_widget( 'dashboard_posts', 'Posts', 'dashboard_posts' );    
    // Widget #6
    wp_add_dashboard_widget( 'dashboard_posts_per_day', 'When do you post the most?', 'dashboard_posts_per_day' );    
    // Widget #7
    wp_add_dashboard_widget( 'dashboard_posts_facebook', '5 posts which generate the most Facebook shares and likes', 'dashboard_posts_facebook' );
}

function abm_plugin_action_links( $links, $file ) {
    array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=advanced-blog-metrics' ) . '">' . __( 'Settings' ) . '</a>' );
    return $links;
}


/***** WIDGETS ON DASHBOARD *****/
// widget #1 : Posts which generate the most comments
function dashboard_comments_per_post() {
    global $queries, $wpdb;
    $posts = $wpdb->get_results( $queries['comments_per_post'] );
    $html  = '<table cellpadding="0" cellspacing="0" class="table-list">';
    $html .= '<thead><tr><th width="85%">Post</th><th class="comment_count">Comments</th></tr></thead>';
    $html .= '<tbody>';
    foreach ( $posts as $post ) {
        $html .= '<tr>';
        $html .= '<th><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></th>';
        $html .= '<td class="comment_count">' . $post->comment_count . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    echo $html;
}

// Widget #2 : When do your posts generate the most comments?
function dashboard_comments_per_day() {
    global $queries, $wpdb, $days, $startofweek;
    $posts = array();
    
    $results = $wpdb->get_results( $queries['comments_per_day'] );
    
    foreach($results as $result) {
        $posts[$result->day] = $result->count;       
    }
    
    $max = max($posts);
    
    $html = '<table cellpadding="0" cellspacing="0" class="table-cols">';
    $html .= '<tr>';
    for ($num_day = $startofweek; $num_day <= 6; $num_day++) {
        $html .= '<td class="value' . ($max == $posts[$num_day] ? ' max' : '') . '">';
        $html .= '<span class="count">' . $posts[$num_day] . '</span>';
        $html .= '<div class="pourcent" style="height: ' . round( ( $posts[$num_day] / $max ) * 150, 0 ) . 'px"></div>';
        $html .= '</td>';
    }
    for ($num_day = 0; $num_day < $startofweek; $num_day++) {
        $html .= '<td class="value' . ($max == $posts[$num_day] ? ' max' : '') . '">';
        $html .= '<span class="count">' . $posts[$num_day] . '</span>';
        $html .= '<div class="pourcent" style="height: ' . round( ( $posts[$num_day] / $max ) * 150, 0 ) . 'px"></div>';
        $html .= '</td>';
   }
    $html .= '</tr><tr>';
    for ($num_day = $startofweek; $num_day <= 6; $num_day++) {
        $html .= '<th class="day' . ( $max == $posts[$num_day] ? ' max' : '' ) . '"><span>' . strtoupper( substr( $days[$num_day], 0, 3 ) ) . '</span></th>';
    }
     for ($num_day = 0; $num_day < $startofweek; $num_day++) {
        $html .= '<th class="day' . ( $max == $posts[$num_day] ? ' max' : '' ) . '"><span>' . strtoupper( substr( $days[$num_day], 0, 3 ) ) . '</span></th>';
    }
    $html .= '</tr>';
    $html .= '</table>';
    echo $html;
}
    
// Widget #3 : Comments
function dashboard_comments() {
    global $queries, $wpdb, $elapseddays;
    $total = current( $wpdb->get_col( $queries['comments_total'], 0 ) );
    $posts = current( $wpdb->get_col( $queries['posts_total'], 0 ) );
    $words = current( $wpdb->get_col( $queries['comments_word'], 0 ) );
    $html = '<table cellpadding="0" cellspacing="0" class="table-list table-summary">';
        $html .= '<thead>';
            $html .= '<tr>';
                $html .= '<th width="25%">Approved comments</th>';
                $html .= '<th width="25%">Comments per Day</th>';
                $html .= '<th width="25%">Comments per Post</th>';
                $html .= '<th width="25%">Words per Comment</th>';
            $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
            $html .= '<tr>';
                $html .= '<td class="number"><span>' . $total . '</span></td>';
                $html .= '<td class="number"><span>' . round( $total / $elapseddays, 2 ) . '</span></td>';
                $html .= '<td class="number"><span>' . round( $total / $posts, 1 ) . '</span></td>';
                $html .= '<td class="number"><span>' . round( $words / $total ) . '</span></td>';
            $html .= '</tr>';
        $html .= '</tbody>';
    $html .= '</table>';
    echo $html;        
}
    
// Widget #4 : Authors who comment the most
function dashboard_comments_per_author() {
    global $queries, $wpdb, $commentregistration;
    if ($commentregistration) {
        $authors = $wpdb->get_results($queries['comments_per_author']);
    }
    $html  = '<table cellpadding="0" cellspacing="0" class="table-list">';
    $html .= '<thead><tr><th width="85%">Author</th><th class="comment_count">Comments</th></tr></thead><tbody>';
    if ($commentregistration) {
        foreach ($authors as $author) {
            $html .= '<tr>';
            $html .= '<th><a href="' . get_admin_url() . 'user-edit.php?user_id='.$author->ID . '" title="Modifier">' . $author->display_name . '</a></th>';
            $html .= '<td class="comment_count">' . $author->comment_count . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="2"><p>Note that you need to check "Users must be registered and logged in to comment" in the Wordpress Settings->Discussion to see data in the "5 authors who comments the most" widget.</p><p><a class="button" href="' . get_admin_url() . 'options-discussion.php">View discussion options</p></td></tr>';
    }
    $html .= '</tbody></table>';
    echo $html;
}    

// Widget #5 : Posts
function dashboard_posts() {
    global $queries, $wpdb, $elapseddays;
    $total = current( $wpdb->get_col( $queries['posts_total'], 0 ) );
    $comments = current( $wpdb->get_col( $queries['comments_total'], 0 ) );
    $words = current( $wpdb->get_col( $queries['posts_word'], 0 ) );
    $html  = '<table cellpadding="0" cellspacing="0" class="table-list table-summary">';
    $html .= '<thead><tr>';
    $html .= '<th width="25%">Posts</th>';
    $html .= '<th width="25%">Posts per Day</th>';
    $html .= '<th width="25%">Comments per Post</th>';
    $html .= '<th width="25%">Words per Post</th>';
    $html .= '</tr></thead><tbody><tr>';
    $html .= '<td class="number"><span>' . $total . '</span></td>';
    $html .= '<td class="number"><span>' . round( $total / $elapseddays, 2 ) . '</span></td>';
    $html .= '<td class="number"><span>' . round( $comments / $total, 1 ) . '</span></td>';
    $html .= '<td class="number"><span>' . round( $words / $total ) . '</span></td>';
    $html .= '</tr></tbody>';
    $html .= '</table>';
    echo $html;        
}

// Widget #6 : When do you post the most?
function dashboard_posts_per_day() {
    global $queries, $wpdb, $days, $startofweek;
    $posts = array();    
    $results = $wpdb->get_results( $queries['posts_per_day'] );    
    foreach($results as $result) {
        $posts[$result->day] = $result->count;       
    }
    
    $max = max($posts);
    $html  = '<table cellpadding="0" cellspacing="0" class="table-cols">';
    $html .= '<tr>';
    for ($num_day = $startofweek; $num_day <= 6; $num_day++) {
        $html .= '<td class="value' . ($max == $posts[$num_day] ? ' max' : '') . '">';
        $html .= '<span class="count">' . $posts[$num_day] . '</span>';
        $html .= '<div class="pourcent" style="height: ' . round( ( $posts[$num_day] / $max ) * 150, 0 ) . 'px"></div>';
        $html .= '</td>';
    }
     for ($num_day = 0; $num_day < $startofweek; $num_day++) {
        $html .= '<td class="value' . ($max == $posts[$num_day] ? ' max' : '') . '">';
        $html .= '<span class="count">' . $posts[$num_day] . '</span>';
        $html .= '<div class="pourcent" style="height: ' . round( ( $posts[$num_day] / $max ) * 150, 0 ) . 'px"></div>';
        $html .= '</td>';
    }
    $html .= '</tr><tr>';
    for ($num_day = $startofweek; $num_day <= 6; $num_day++) {
        $html .= '<th class="day' . ( $max == $posts[$num_day] ? ' max' : '' ) . '"><span>' . strtoupper( substr( $days[$num_day], 0, 3 ) ) . '</span></th>';
    }
     for ($num_day = 0; $num_day < $startofweek; $num_day++) {
        $html .= '<th class="day' . ( $max == $posts[$num_day] ? ' max' : '' ) . '"><span>' . strtoupper( substr( $days[$num_day], 0, 3 ) ) . '</span></th>';
    }
    $html .= '</tr>';
    $html .= '</table>';
    echo $html;
}

// Widget #7 : 5 posts which generate the most Facebook shares and likes
function dashboard_posts_facebook() {
    
    global $queries, $wpdb;
    
    // Add the option if it does not exist yet
    add_option('abm_data', '');
    
    // data of the plugin
    $data = get_option('abm_data');
    
    // If the admin cliks on the button, the queries will occur and it could be long depending on the number of posts
    if( isset($_POST['sub_posts_on_facebook']) && 'Get/Update data' == $_POST['sub_posts_on_facebook'] ) {
        
        $posts_likes = array();
        $posts_shares = array();
        $query_urls = array();
        $posts_permalink_title = array();
        
        $nb_url_by_query = 100;        
        
        $posts = $wpdb->get_results( $queries['posts'] );
 
        foreach( $posts as $post ) {            
            $query_urls[] = get_permalink( $post->ID );
            $posts_permalink_title[get_permalink( $post->ID )] = $post->post_title;
        }
        
        $array_permalinks = array_chunk($query_urls, $nb_url_by_query);
        
        foreach( $array_permalinks as $permalinks ){
            $q = implode("','", $permalinks);
            $fql_query_url = "https://graph.facebook.com/fql?q=SELECT+url,+share_count,+like_count+FROM+link_stat+WHERE+url+IN('".$q."')";
            $fql_query_result = file_get_contents($fql_query_url);
            $fql_query_obj = json_decode($fql_query_result, true);

            foreach($fql_query_obj[data] as $a) {
                $posts_likes[] = array('title' =>$posts_permalink_title[$a['url']], 'permalink' => $a['url'], 'like_count' => $a['like_count']);
                $posts_shares[] = array('title' =>$posts_permalink_title[$a['url']], 'permalink' => $a['url'], 'share_count' => $a['share_count']);
            }
        }
        
        foreach( $posts_likes as $k => $v ) {
            $likes[$k] = $v['like_count'];
        }
        array_multisort( $likes, SORT_DESC, $posts_likes );        
        $posts_likes_output = array_slice($posts_likes, 0, 5);

        foreach( $posts_shares as $k => $v ) {
            $shares[$k] = $v['share_count'];
        }
        array_multisort( $shares, SORT_DESC, $posts_shares );
        $posts_shares_output = array_slice( $posts_shares, 0, 5 );

        $array_shares_and_likes = array_merge( array('posts_likes' => $posts_likes_output ), array('posts_shares' => $posts_shares_output) );
       
        update_option( 'abm_data', $array_shares_and_likes );
        
         ?>
        <script type="text/javascript">
            <!--
            window.location = '/wp-admin';
            //-->
        </script>
        <?php 
        
    }
    
    // display
    $html  = '<form name="form_posts_on_facebook" id="form_posts_on_facebook" action="" method="post">';
    $html .= '<input type="submit" name="sub_posts_on_facebook" id="sub_posts_on_facebook" value="Get/Update data" />';
    $html .= '<span id="info-get-data">(It can takes several minutes depending on the number of posts)</span>';
    $html .= '</form>';
    echo $html;
    
	
    // About LIKES
    $html_likes  = '<table cellpadding="0" cellspacing="0" class="table-list">';
    $html_likes .= '<thead><tr><th width="85%">Post</th><th class="facebook_count"><img alt="like" src="http://www.atalanta.fr/advanced-blog-metrics/facebook-like.png" /></th></tr></thead><tbody>';
    if( isset($data) && !empty($data) ) {
        foreach ($data['posts_likes'] as $post) {
            $html_likes .= '<tr>';
            $html_likes .= '<th><a href="' . $post['permalink'] . '">' . $post['title'] . '</a></th>';
            $html_likes .= '<td class="facebook_count">' . $post['like_count'] . '</td>';
            $html_likes .= '</tr>';
        }       
    }
    $html_likes .= '</tbody></table>';
    echo $html_likes, "<br />";


     // About SHARES
    $html_shares  = '<table cellpadding="0" cellspacing="0" class="table-list">';
    $html_shares .= '<thead><tr><th width="85%">Post</th><th class="facebook_count"><img alt="share" src="http://www.atalanta.fr/advanced-blog-metrics/facebook-share.png" /></th></tr></thead><tbody>';
    if( isset($data) && !empty($data) ) {
        foreach ($data['posts_shares'] as $post) {
            $html_shares .= '<tr>';
            $html_shares .= '<th><a href="' . $post['permalink'] . '">' . $post['title'] . '</a></th>';
            $html_shares .= '<td class="facebook_count">' . $post['share_count'] . '</td>';
            $html_shares .= '</tr>';
        }
    }
    $html_shares .= '</tbody></table>';
    echo $html_shares, "<br />";
	
}