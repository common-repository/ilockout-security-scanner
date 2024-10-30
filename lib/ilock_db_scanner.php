<?php
if(!function_exists('add_action'))
{
    exit(0);
}
class iLock_Security_DB_Scanner 
{
    var $results = array();
    var $post_patterns = array(
        'eval(' => array('level' => 'bad', 'desc' => 'Illegitimate code used by hackers to insert trojan and malicious script inside your website'),
        '<iframe' => array('level' => 'warning', 'desc' => 'No iframe script shoube in your post. It could be a redirection or hidden code script.'),
        '<noscript' => array('level' => 'warning', 'desc' => 'Could be used to hide malicious code in a post'),
        'display: none' => array('level' => 'warning', 'desc' => 'Could be used to hide malicious code in a post'),
        'visibility: hidden' => array('level' => 'warning', 'desc' => 'Could be used to hide malicious code in a post'),
        '<script' => array('level' => 'warning', 'desc' => 'There shouldn\'t be any script tag inside your post')
    );
    
    function __construct() {
        
    }
    
    function run()
    {
        global $wpdb;
        if (!is_admin() && !current_user_can('administrator'))
        {
            wp_die(__('You do not have enough admin rights to perform this action. Please contact administrator'));
        } else {
            foreach ($this->post_patterns as $pattern => $info)
            {
                $posts = $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type <> 'revision' AND post_content LIKE '%{$pattern}%'");
                if ($posts)
                {
                    foreach ($posts as $post)
                    {
                        $this->add_result($info['level'], array('loc' => '<strong>Post:</strong>   ' . esc_html($post->post_title),'post_id' => $post->ID, 'desc' => $info['desc']));
                    }
                } else {
                    $this->add_result('good','the database is clean');
                }
            }
        }
        $this->store_result();
    }
    
    function add_result($level, $info)
    {
        $this->results[$level][] = $info;
    }
    
    function store_result()
    {
        update_option('ilockout_db_scanner_result', $this->results);
    }
}