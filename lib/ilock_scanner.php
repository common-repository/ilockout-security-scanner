<?php
if(!function_exists('add_action'))
{
    exit(0);
}

class iLock_Security_Scanner
{
    
    var $results = array();
    var $scan_info = array();
    
    function __construct()
    {
        
    }
    
    function run()
    {
        if (!is_admin() && !current_user_can('administrator'))
        {
            wp_die(__('You do not have enough admin rights to perform this action. Please contact administrator'));
        }
        else
        {
            if (!file_exists(dirname(__FILE__) . '/hashes/filehashes-' . get_bloginfo('version') . '.php'))
            {
                $this->add_result('error', array('desc' => 'The iLockout scanner currently does not have the file definitions for your current Wordpress version or you might did not upgrade this plugin to latest version.'));
                
                $this->store_result();
                
            } else {
                $ver = get_bloginfo('version');
                $ilock_missing = array('index.php', 'readme.html', 'license.txt', 'wp-config-sample.php', 'wp-admin/install.php', 'wp-admin/upgrade.php');                $ilock_changed = array('wp-config.php');
                
                include_once 'hashes/filehashes-' . $ver . '.php';
            
                $scan_info['total']  = sizeof($filehashes['files']);
                
                    foreach ($filehashes['files'] as $file => $hash)
                    {
                        clearstatcache();
                        if (file_exists(ABSPATH . $file))
                        {
                            if ($hash == md5_file(ABSPATH . $file))
                            {
                                $this->add_result('ok', array('loc' => $file));
                            }
                             elseif (in_array($file, $ilock_changed))
                            {
                                $this->add_result('ilockout_changed', array('loc' => $file));
                            } else {
                                $this->add_result('warning', array('loc' => $file));
                            }
                        } else {
                            if (in_array($file, $ilock_missing))
                            {
                                $this->add_result('ilock_missing', array('loc' => $file));
                            } else 
                            {
                                $this->add_result('missing', array('loc' => $file));
                            }
                        }
                    } //end checking for modified files
                //}
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
        update_option('ilockout_scanner_result', $this->results);
    }
    
    
}