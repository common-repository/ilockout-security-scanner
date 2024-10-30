<?php
/*
  Plugin Name: iLockout Security Scanner
  Plugin URI: http://www.ilockout.com
  Description: #1 Most Up-to-date Security Scanner for Wordpress. Check Wordpress files integrity, check database for malware and hacks, perform 1-click hardening on your site.

  Author: iLockout, INC
  Version: 1.0.3
  Author URI: http://www.ilockout.com
 */

/* No direct access. */
if (!function_exists('add_action')) {
    exit(0);
}

@set_time_limit(0);
@ini_set('max_execution_time', 0);

define('ILOCK_SCANNER_VERSION', '1.0');

/*
 * Initialize essential files
 */

    add_action('admin_menu', 'ilockout_scanner_menu');
    add_action('admin_enqueue_scripts', 'ilockout_scripts');
    add_action('wp_ajax_ilockout_scan', 'ilockout_scanner_run');
    add_action('wp_ajax_ilockout_db_scan', 'ilockout_db_scanner_run');
    add_action('wp_ajax_ilockout_get_file_source', 'ilockout_view_source');
    add_action('wp_ajax_ilockout_fix_readme', 'ilock_fix_readme_license');
    add_action('wp_ajax_ilockout_fix_username_admin', 'ilock_fix_username_admin');
    add_action('wp_ajax_ilockout_fix_expose_php', 'ilock_fix_expose_php');
    add_action('wp_ajax_ilockout_fix_user_id', 'ilock_fix_user_id');


/*
 * Set up the main menu for iLockout Scanner Plugin
 */

function ilockout_scanner_menu() {
    add_menu_page('iLockout Security', 'iLockout Security', 'activate_plugins', 'ilockoutscan', 'ilockout_scanner');
    add_submenu_page('ilockoutscan', 'Malware Scanner', 'Malware Scanner', 'manage_options', 'ilockoutscan', 'ilockout_scanner');
    add_submenu_page('ilockoutscan', '1-Click Hardening', '1-Click Hardening', 'manage_options', 'ilockoutsitelockdown', 'ilockout_site_lock_down');
}

function ilockout_scripts() {
    $plugin_url = plugin_dir_url(__FILE__);

    wp_enqueue_style('ilockout-css', $plugin_url . 'css/ilockout-suite-style.css', array(), ILOCK_SCANNER_VERSION);

    wp_enqueue_script('ilockout-jquery-js', $plugin_url . 'js/ilockout-jquery.js', array(), ILOCK_SCANNER_VERSION, true);
    wp_enqueue_script('ilockout-chat-js', $plugin_url . 'js/ilockout-chat.js', array(), ILOCK_SCANNER_VERSION, true);
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script('postbox');
    wp_enqueue_script('dashboard');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_script('ilockout-main-js', $plugin_url . 'js/ilockout-common.js', array(), ILOCK_SCANNER_VERSION, true);
}

function ilockout_scanner() {
    $plugin_url = plugin_dir_url(__FILE__);
    if (!is_admin()) {
        wp_die(__('You do not have admin rights to perform this action. Please contact administrator'));
    }

    echo '<div class = "wrap">';
    echo '<img src="' . $plugin_url . '/assets/img/ilockout-plugin-logo.png" alt="" style="display: inline;"/><h2>iLockout File Scanner</h2>';

    $tabs = array();
    $tabs[] = array('id' => 'ilockout_scanner', 'class' => '', 'label' => 'File Scanner', 'callback' => 'ilockout_scanner_main');
    $tabs[] = array('id' => 'ilockout_db_scanner', 'class' => '', 'label' => 'Database Scanner', 'callback' => 'ilockout_database_scan');
  
    $tabs = apply_filters('ilockout_tabs', $tabs);

    echo '<div id = "ilocktabs">';
    echo '<ul>';
    foreach ($tabs as $tab) {
        echo '<li><a href = "#' . $tab['id'] . '" class="' . $tab['class'] . '">' . $tab['label'] . '</a></li>';
    }
    echo '</ul>';

    foreach ($tabs as $tab) {
        echo '<div id = "' . $tab['id'] . '">';
        call_user_func($tab['callback']);
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

function ilockout_db_scanner_run() {
    include_once 'lib/ilock_db_scanner.php';
    $scanner = new iLock_Security_DB_Scanner;
    $scanner->run();
    die('1');
}

function ilockout_scanner_run() {
    include_once 'lib/ilock_scanner.php';
    $scanner = new iLock_Security_Scanner();
    $scanner->run();
    $scanner->store_result();
    die('1');
}

function ilockout_scanner_main() {
    $results = get_option('ilockout_scanner_result');
    ?>
    <div class="postbox-container" style="width:65%; margin-right: 10px;">
        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div id="adminform" class="postbox">
                    <div class="handlediv" title="Click to Toogle">
                        <br/>
                    </div>
                    <h3 class="hndle"><span>Main Scanning Interface</h3>
                    <div class="inside">
                        <div style="width: 250px; float: left;"><input type="submit" value="Start Scan" id="ilockout_scan_button" class="button-primary" /></div>                          
                        <div class="clear"></div>
                        <div class="ilockout-result">
                            <p><strong style='font-size: 14px;'><?php _e('Important Note!', 'ilockout-scanner'); ?></strong></p>
                            <p>
    <?php _e('Files are scanned and compared with the original Wordpress core files available from Wordpress.org', 'ilockout-scanner'); ?><br/>
    <?php _e('If there are changes to the files in your Wordpress site, this plugin will alert you.', 'ilockout-scanner'); ?><br/>
                            </p>

                                <?php
                                if ($results['error']) {
                                    ?>
                                <div class="ilockout-error">
                                    <h4><?php _e('Error!', 'ilockout-scanner'); ?></h4>
                                <?php echo list_results($results['error']); ?>
                                </div>
        <?php
    }
    if (!isset($results['warning']) && !isset($results['missing'])) {
        ?>
                                <div class="ilockout-success">
                                <?php _e('Scan is complete! Your files are clean', 'ilockout-scanner'); ?>
                                </div>
                                <?php
                            }
                            if ($results['warning']) {
                                ?>
                                <div class="ilockout-warning">
                                    <h4><?php _e('The following core files have been modified and they <strong>WERE NOT</strong> supposed to be!', 'ilockout-scanner'); ?></h4>
                                <?php echo list_results($results['warning'], true, false); ?>
                                </div>
                                <?php
                            }
                            if ($results['missing']) {
                                ?>
                                <div class="ilock-missing">
                                    <h4><?php _e('The following core files are missing from your installation. We recommend you download the missing files promptly from Wordpress.org', 'ilockout-scanner'); ?></h4>
                                <?php echo list_results($results['missing'], false, false); ?>
                                </div>
                            <?php } ?>
                            <div id='source-dialog' style='display: none;' title='File Source'><p><?php _e('Please wait...', 'ilockout-scanner'); ?></p></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php
    ilockout_sidebar();
}

function ilockout_database_scan() {
    $results = get_option('ilockout_db_scanner_result');
    ?>
    <div class="postbox-container" style="width:65%; margin-right: 10px;">
        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div id="adminform" class="postbox">
                    <div class="handlediv" title="Click to Toogle">
                        <br/>
                    </div>
                    <h3 class="hndle"><span>Database Scanning</h3>
                    <div class="inside">
                        <div style="width: 250px; float: left;"><input type="submit" value="Start Scan" id="ilockout_db_scan_button" class="button-primary" /></div>
                        <div class="clear"></div>
                        <div class="ilockout-result">
                            <p><strong style='font-size: 14px;'><?php _e('Important Note!', 'ilockout-scanner'); ?></strong></p>
                            <p><?php _e('The database scan provides you insight on whether your database has been compromised by hackers. Sometimes they inject malicious code into your post to avoid a file scanner like iLockout Security Suite.', 'ilockout-scanner'); ?>
                                <br/>
    <?php _e('Click Start Scan to begin scanning.', 'ilockout-scanner'); ?></p>

    <?php
    if ($results['good']) {
        ?>
                                <div class="ilockout-success">
                                <?php _e('Scan is complete! Your database is clean', 'ilockout-scanner'); ?>
                                </div>

                                <?php
                            }
                            if ($results['warning']) {
                                ?>
                                <div class="ilockout-warning">
                                    <h4><?php _e('Warning!', 'ilockout-scanner'); ?></h4>
                                <?php echo list_results_db($results['warning'], true, false); ?>
                                </div>
                                <?php
                            }
                            if ($results['bad']) {
                                ?>
                                <div class="ilockout-bad">
                                    <h4><?php _e('Infected!', 'ilockout-scanner'); ?></h4>
                                <?php echo list_results_db($results['bad'], true, false); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php
    ilockout_sidebar();
}

function ilock_fix_wp_header() {
    remove_action('wp_head', 'wp_generator');
}

function ilock_fix_readme_license() {
    $file = ABSPATH . '/readme.html';
    if (file_exists($file)) {
        unlink($file);
        $file_license = ABSPATH . '/license.txt';
        if (file_exists($file_license)) {
            unlink($file_license);
        }
        die('1');
    }
}

function ilock_fix_username_admin() {
    global $wpdb;

    $new_user = sanitize_text_field($_POST['new_user']);

    if (strlen($new_user) < 1) {
        die(__('Username is not valid. Please try again.', 'ilockout-scanner'));
    } else {
        if (validate_username($new_user)) {
            if (username_exists($new_user)) {
                die(__('Username already exists. Please try again.', 'ilockout-scanner'));
            } else {
                $wpdb->query("UPDATE `" . $wpdb->users . "` SET user_login = '" . esc_sql($new_user) . "' WHERE user_login = 'admin'");
                ilock_clear_cache();
                die(1);
            }
        } else {
            die(__('Username is not valid. Please try again.', 'ilockout-scanner'));
        }
    }
}

function ilock_show_message($errors) {
    global $error_message;

    $error_message = '';

    if (function_exists('apc_store')) {
        apc_clear_cache();
    }

    if (is_wp_error($errors)) {
        $errors = $errors->get_error_messages();

        foreach ($errors as $error => $description) {
            $error_message .= '<div id="message" class="error"><p>' . $description . '</p></div>';
        }
    }
    add_action('admin_notices', 'ilock_dispmessage');
    add_action('network_admin_notices', 'ilock_dispmessage');
}

function ilock_dispmessage() {
    global $error_message;

    echo $error_message;

    unset($error_message);
}

function ilock_clear_cache() {
    if (function_exists('apc_store')) {
        apc_clear_cache();
    }

    if (function_exists('w3tc_pgcache_flush')) {
        w3tc_pgcache_flush();
        w3tc_dbcache_flush();
        w3tc_objectcache_flush();
        w3tc_minify_flush();
    } else if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
}

function ilock_fix_user_id() {
    global $wpdb;

    $user = get_user_by('id', '1');
    if ($user === false) {
        die(__('No user with ID 1 exists', 'ilockout-scanner'));
    }
    $wpdb->query("DELETE FROM `" . $wpdb->users . "` WHERE ID = 1;");
    $wpdb->insert($wpdb->users, array(
        'user_login' => $user->user_login,
        'user_pass' => $user->user_pass,
        'user_nicename' => $user->user_nicename,
        'user_email' => $user->user_email,
        'user_url' => $user->user_url,
        'user_registered' => $user->user_registered,
    ));

    $new_user = $wpdb->insert_id;
    $wpdb->query("UPDATE `" . $wpdb->posts . "` SET post_author = '" . $new_user . "' WHERE post_author = 1;");
    $wpdb->query("UPDATE `" . $wpdb->usermeta . "` SET user_id = '" . $new_user . "' WHERE user_id = 1;");
    $wpdb->query("UPDATE `" . $wpdb->comments . "` SET user_id = '" . $new_user . "' WHERE user_id = 1;");
    $wpdb->query("UPDATE `" . $wpdb->links . "` SET link_owner = '" . $new_user . "' WHERE link_owner = 1;");
    ilock_clear_cache();
    die('1');
}

function ilockout_site_lock_down() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have admin rights to perform this action. Please contact administrator'));
    }
    ?>
    <div class="wrap">
        <h2>iLockOut 1-Click Protect Your Site</h2>
        <div style="width: 65%; margin: 10px 10px 0 0; float: left;">
            <table class="wp-list-table widefat" cellspacing="0" id="site-lock-down">
                <tbody>
                    <tr>
                        <td>
                            <img src="<?php echo plugins_url('assets/img/lock.png', __FILE__) ?>" alt="iLockout Lock" style="float: left; margin-right: 10px;" />
    <?php _e('Use this information to check the overall security of your site. These are the most vulnerable attack points of hackers and if you follow these security practices. Your site will be locked out from hackers in no time!', 'ilockout-scanner'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>&nbsp;</p>
            <table class="wp-list-table widefat" cellspacing="0" id="site-lock-down">
                <thead><tr>
                        <th class="ilock-status" width="15%">Status</th>
                        <th class="ilock-vul-title" width="20%">Vulnerable Title</th>
                        <th class="ilock-vul-desc" width="50%">Vulnerable Description</th>
                        <th class="ilock-action" width="15%">Action</th>
                    </tr>
                </thead>
                <tbody>
    <?php
    echo check_wordpress_version();
    echo check_plugin();
    echo check_theme();
    echo check_wordpress_info();
    echo check_readme_file();
    echo check_username_admin();
    echo check_user_id_1();
    ?>
                </tbody>
            </table>
        </div>
    <?php ilockout_sidebar(); ?>
        <div class="clear"></div>
    </div> 
    <?php
}

function check_plugin() {
    $out = '';
    $out .= '<tr>';

    $current = get_site_transient('update_plugins');

    if (!is_object($current)) {
        $current = new stdClass();
    }

    set_site_transient('update_plugins', $current);
    wp_update_plugins();

    $current = get_site_transient('update_plugins');

    if (isset($current->response) && is_array($current->response)) {
        $update_count = count($current->response);
        if ($update_count > 0) {
            $out .= '<td><span class="ilock-bad-badge">' . __('Bad', 'ilockout-scanner') . '</span></td>';
            $out .= '<td>' . __('Outdated plugins', 'ilockout-scanner') . '</td>';
            $out .= '<td>There are <strong>' . sizeof($current->response) . '</strong> plugis that need(s) to be updated</td>';
            $out .= '<td><a class="button-primary" href="update-core.php">Update Now!</a></td>';
        }
    } else {
        $out .= '<td><span class="ilock-good-badge">Ok</span></td>';
        $out .= '<td>All plugins are up to date</td>';
        $out .= '<td>All of your plugins are up to date!</td>';
        $out .= '<td></td>';
    }
    $out .= '</tr>';
    return $out;
}

function check_theme() {
    $out = '';
    $current = get_site_transient('update_themes');

    if (!is_object($current)) {
        $current = new stdClass;
    }

    set_site_transient('update_themes', $current);
    wp_update_themes();

    $current = get_site_transient('update_themes');

    if (isset($current->response) && is_array($current->response)) {
        $update_count = count($current->response);
        if ($update_count > 0) {
            $out .= '<td><span class="ilock-bad-badge">Bad</span></td>';
            $out .= '<td>Outdated themes</td>';
            $out .= '<td>There are <strong>' . sizeof($current->response) . '</strong> themes that need to be updated</td>';
            $out .= '<td><a class="button-primary" href="update-core.php">Update Now!</a></td>';
        }
    } else {
        $out .= '<td><span class="ilock-good-badge">Ok</span></td>';
        $out .= '<td>All themes  are up to date</td>';
        $out .= '<td>All of your plugins are up to date!</td>';
        $out .= '<td></td>';
    }
    $out .= '</tr>';
    return $out;
}

function check_wordpress_info() {
    $out = '';

    if (!function_exists('get_preferred_from_update_core')) {
        require_once(ABSPATH . 'wp-admin/includes/update.php');
    }

    wp_version_check();
    $latest_core_update = get_preferred_from_update_core();

    $out .= '<tr>';
    if (isset($latest_core_update->response) && ($latest_core_update->response == 'upgrade')) {
        $out .= '<td><span class="ilock-bad-badge">Bad</span></td>';
        $out .= '<td>Wordpress Update</td>';
        $out .= '<td>You need to upgrade your Wordpress to the latest version. The outdated version poses a security risk because of unpatched security vulnerabilities.</td>';
        $out .= '<td><a class="button-primary" href="update-core.php">Upgrade Now!</a></td>';
    } else {
        $out .= '<td><span class="ilock-good-badge">Ok</span>';
        $out .= '<td>Wordpress Update</td>';
        $out .= '<td>Your Wordpress is up to date</td>';
        $out .= '<td></td>';
    }

    $out .= '</tr>';
    return $out;
}

function check_readme_file() {
    $url = get_bloginfo('wpurl') . '/readme.html';
    $response = wp_remote_get($url);
    $out = '';
    $out .= '<tr>';
    if ($response['response']['code'] == 200) {
        $out .= '<td><span class="ilock-bad-badge">Bad</span></td>';
        $out .= '<td>Readme file</td>';
        $out .= '<td>Readme.html is vulnerable because it shows the version of Wordpress software you are running and makes it easy to find security vulnerablilities that have not been patched.</td>';
        $out .= '<td><a class="button-primary" id="fix_readme">Fix It!</a></td>';
    } else {
        $out .= '<td><span class="ilock-good-badge">Ok</span></td>';
        $out .= '<td>Readme file</td>';
        $out .= '<td>It looks like you have deleted readme.html file. That\'s good.</td>';
        $out .= '<td></td>';
    }
    $out .= '</tr>';
    return $out;
}

function write_row($title, $mess, $good = true, $call_back = array()) {
    $out = '';
    $out .= '<tr>';
    $out .= '<td><span class="' . ($good ? 'ilock-good-badge' : 'ilock-bad-badge') . '">' . ($good ? 'Ok' : 'Bad') . '</span></td>';
    $out .= '<td>' . $title . '</td>';
    $out .= '<td>' . $mess . '</td>';
    $out .= '<td>' . ($good ? '' : '<a class = "button-primary" id = "' . $call_back['id'] . '">' . $call_back['value'] . '</a>') . '</td>';
    $out .= '</tr>';
    return $out;
}

function check_username_admin() {
    require_once(ABSPATH . WPINC . '/registration.php');

    if (username_exists('admin')) {
        $call_back = array('id' => 'fix_username_admin', 'value' => 'Change Username');
        return write_row('Username "admin" Exists', 'Using the default username allows hackers to use brute force on the admin login page to find the password. ', false, $call_back);
    } else {
        return write_row('Default Username "admin"', 'No default username is found', true, '');
    }
}

function check_user_id_1() {
    if (get_userdata(1)) {
        $call_back = array('id' => 'fix_user_id', 'value' => 'Change User ID');
        return write_row('User ID 1 Exists', 'Using the default user ID allows hackers to use brute force on the admin login page to find the password. Change the User ID if it is currently 1.', false, $call_back);
    } else {
        return write_row('User ID 1 Exists', 'Default user ID 1 does not exist', true, '');
    }
}

//check for wp version in the header
function check_wordpress_version() {
    if (!class_exists('WP_Http')) {
        require( ABSPATH . WPINC . '/class-http.php');
    }

    $http = new WP_Http();
    $response = (array) $http->request(get_bloginfo('wpurl'));
    $html = $response['body'];

    if ($html) {
        preg_match_all("#\<head\>[^)]*\<\/head>#i", $html, $matches);
        $html = $matches[0];
        preg_match_all("#\<meta([^>]+)#si", $html[0], $matches);
        $meta_tags = $matches[0];

        foreach ($meta_tags as $meta_tag) {
            if (stripos($meta_tag, 'generator') !== false && stripos($meta_tag, get_bloginfo('version')) !== false) {
                $call_back = array('id' => 'fix_wp_header', 'value' => 'Fix It!');
                return write_row('WP Tag in Header', 'Some information should be hidden to avoid being attacked by hackers. Click Fix It if you haven\'t hidden this information yet.', false, $call_back);
            } else {
                return write_row('WP Tag in Header', 'Your header is secured now', true, '');
            }
        }
    }
}

function ilockout_live_chat() {
    ?>
    <div class="wrap">
        <h2>Live Chat with Our Security Expert</h2>

        <div class="postbox-container" style="width:65%; margin-right: 10px;">
            <div class="metabox-holder">
                <div class="meta-box-sortables">
                    <div id="adminform" class="postbox">
                        <div class="handlediv" title="Click to Toogle">
                            <br/>
                        </div>
                        <h3 class="hndle"><span>Live Chat</span></h3>
                        <div class="inside">
                            <iframe src="//app.helponclick.com/help?lang=en&ava=1&a=52cdb95590ec4923b3a72df3a2d1033f&nr=1" width="100%" height="600"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function ilockout_deactivate() {
    delete_option('ilockout_scanner_result');
}

function list_results_db($results, $view = false, $restore = false) {
    $out = '';
    $out .= '<ul class="ilock-list-result">';
    foreach ($results as $result) {
        $out .= '<li>';
        $out .= '<span class="ilock-desc">' . $result['desc'] . '</span><br/>';
        $out .= '<span class="ilock-result">' . $result['loc'] . '</span>';
        if ($view) {
            $out .= ' <a href="post.php?post=' . $result['post_id'] . '&action=edit" target="_blank">edit this post</a>';
        }
        $out .= '</li>';
    }
    $out .= '</ul>';
    return $out;
}

function list_results($results, $view = false, $restore = false) {
    $out = '';
    $out .= '<ul class="ilock-list-result">';
    //print_r($results);
    foreach ($results as $result) {
        if (isset($result['desc'])) {
            $out .= '<li>';
            $out .= '<span>' . $result['desc'] . '</span>';
            $out .= '</li>';
        } else {
            $out .= '<li>';
            $out .= '<span class="ilock-result">' . ABSPATH . $result['loc'] . '</span>';
            if ($view) {
                $out .= ' <a data-hash="' . md5('ilockstore' . ABSPATH . $result['loc']) . '" data-file="' . ABSPATH . $result['loc'] . '" href="#source-dialog" class="ilockout-show-source">view file source</a>';
            }
            $out .= '</li>';
        }
    }
    $out .= '</ul>';
    return $out;
}

function ilockout_sidebar() {
    ?>
    <div class="postbox-container side" style="width: 25%">
        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div id="email-signup" class="postbox">
                    <div class="handlediv" title="Click to Toogle">
                        <br/>
                    </div>
                    <h3 class="hndle"><span><?php _e('Get Security & Traffic News', 'ilockout-scanner'); ?></span></h3>
                    <div class="inside">
                        <p>
    <?php _e('Subscribe to our newsletter to receive latest security tips to secure your website.', 'ilockout-scanner'); ?>
                        </p>
                        <!-- Begin MailChimp Signup Form -->
                        <div id="mc_embed_signup">
                            <form action="http://ilockout.us3.list-manage.com/subscribe/post?u=0dfa25110bfb9287f8450eef7&amp;id=03d7cd2285" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>

                                <div class="mc-field-group">
                                    <label for="mce-EMAIL">Email Address</label>
                                    <input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
                                </div>
                                <div id="mce-responses" class="clear">
                                    <div class="response" id="mce-error-response" style="display:none"></div>
                                    <div class="response" id="mce-success-response" style="display:none"></div>
                                </div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                                <div style="position: absolute; left: -5000px;"><input type="text" name="b_0dfa25110bfb9287f8450eef7_03d7cd2285" value=""></div>
                                <div class="clear" style="margin: 10px auto; text-align: center;"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button-primary"></div>
                            </form>
                        </div><!--End mc_embed_signup-->
                    </div>
                </div>
            </div>
        </div>

        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div id="ilock-security-fix" class="postbox">
                    <div class="handlediv" title="Click to Toogle">
                        <br/>
                    </div>
                    <h3 class="hndle">iLockout Fixes Hacked Websites
                    </h3>
                    <div class="inside">
                        <p>Is your website blacklisted because of hackers and spammers? Call iLockout now to get your website fully cleaned and un-blacklisted in less than 48 hours.
                        </p>
                        <p style="margin: 0 auto; text-align: center;">
                            <img src="https://www.ilockout.com/images/fix-hack.png" alt="" /><br/>
                            <a href="https://www.ilockout.com" class="button-primary">Learn More</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div id="ilock-social-media" class="postbox">
                    <div class="handlediv" title="Click to Toogle">
                        <br/>
                    </div>
                    <h3 class="hndle">Connect with Us
                    </h3>
                    <div class="inside">
                        <div class="ilock-box-facebook">
                            <div class="ilock-box-head">
                                <h3 class="ilock-box-darkblue">
                                    <i class="ilock-facebook-ico"></i>
                                    <a href="http://www.facebook.com/ilockoutinc" style="color: #fff; text-decoration: none; font-size: 18px;">Like Us on Facebook</a>
                                </h3>
                            </div>
                        </div>
                        <div class="ilock-box-facebook">
                            <div class="ilock-box-head">
                                <h3 class="ilock-box-twitter">
                                    <i class="ilock-twitter-ico"></i>
                                    <a href="http://www.twitter.com/ilockoutinc" style="color: #fff; text-decoration: none; font-size: 18px;">Follow Us on Twitter</a>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div id="ilock-live-chat" class="postbox">
                    <div class="handlediv" title="Click to Toogle">
                        <br/>
                    </div>
                    <h3 class="hndle">Live Chat with Our Security Expert</h3>
                    <p style="margin: 0 auto; text-align: center;">
                        <a href="#" class="LiveHelpButton" ><img src="http://www.ilockout.com/livehelp/include/status.php" id="LiveHelpStatus" name="LiveHelpStatus" class="LiveHelpStatus" border="0" alt="Live Help" /></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function ilockout_view_source() {
    $out = array();

    if (!is_admin && !current_user_scan('administrator')) {
        wp_die(__('You do not have enough admin rights to view this pae. Please contact site adminisrator'));
    }

    $out['ext'] = pathinfo(@$_POST['filename'], PATHINFO_EXTENSION);
    $out['source'] = '';
    $file_name = $_POST['filename'];
    if (is_readable($file_name)) {
        $content = file_get_contents($file_name);
        if ($content !== FALSE) {
            $out['error'] = 0;
            $out['source'] = $content;
        } else {
            $out['error'] = 'File is empty';
        }
    } else {
        $out['error'] = 'File does not exist or not readable.';
    }
    die(json_encode($out));
}
