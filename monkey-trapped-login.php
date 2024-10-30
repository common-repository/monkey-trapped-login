<?php
/**
 * @package Monkey-Trapped-Login
 * @version 1.1.0
 */
/*
Plugin Name: Monkey Trapped Login
Plugin URI: http://monkeytrapped.com/
Description: Monkey Trapped Login is a WordPress Plugin created to protect your site from Brute Force Login Attacks. Monkey Trapped Login protects against automated attacks on the login form as well as Auth Cookie. Admin can configure the number of failed logins to trigger a lockout, the length of a lockout, and a message displayed to a user who exceeds the maximum allowed failed login attempts.
Author: konnun
Version: 1.1.0
Author URI: http://monkeytrapped.com/
*/

// hook to perform post install tasks
register_activation_hook( __FILE__, array('MonkeyTrappedLogin', 'installed') );

// hook to perform post uninstall tasks
register_deactivation_hook( __FILE__, array('MonkeyTrappedLogin', 'uninstalled') );

//Login hooks
add_action('wp_login_failed', array('MonkeyTrappedLogin', 'failed_login'));
add_action('wp_login', array('MonkeyTrappedLogin', 'success_login'));
add_action('login_head',array('MonkeyTrappedLogin', 'monkey_check'));
add_action ('wp_authenticate' ,array('MonkeyTrappedLogin', 'monkey_check'));

//Auth cookie hooks
add_action('auth_cookie_bad_username', array('MonkeyTrappedLogin', 'failed_login'));
add_action('auth_cookie_bad_hash', array('MonkeyTrappedLogin', 'failed_login'));

// include options page
include_once dirname( __FILE__ ) . '/options.php';

// filter to prevent login error leaking info by telling user if they guessed username right
add_filter('login_errors', create_function('$a', 'return "Login Failed";'));
       

class MonkeyTrappedLogin 
{
public static function installed() {
    $installation = get_option( 'MonkeyTrappedLogin_installed' );

	//if this is a fresh install, set up the options and whitelist installer
	if(strlen($installation) == 0){
	    /* add installers ip to whitelist */
	    $ip = self::monkey_get_ip();	
	    self::add_to_whitelist($ip);

            /* add default options */
	    add_option( 'MonkeyTrappedLogin_installed', 'true');
            add_option( 'MonkeyTrappedLogin_participating', '');
            add_option( 'MonkeyTrappedLogin_failed_attempts', array());
            add_option( 'MonkeyTrappedLogin_max_failed_attempts', 10);
            add_option( 'MonkeyTrappedLogin_lockout_length', 60*60);
            add_option( 'MonkeyTrappedLogin_blacklist' );
            add_option( 'MonkeyTrappedLogin_notify_admin', false );
            add_option( 'MonkeyTrappedLogin_total_lockouts', array());
            add_option( 'MonkeyTrappedLogin_lockoutlist', array('0.0.0.0'=>(time()+3650*24*3600)));
            add_option( 'MonkeyTrappedLogin_lockout_message','You Are Currently Locked Out');
                                
            }
}

// tasks on successful login
public static function success_login() {
    $ip=self::monkey_get_ip();
    self::clear_attempts($ip);
}

// clear the attempts count
public static function clear_attempts($ip) {
    $attempts=get_option( 'MonkeyTrappedLogin_failed_attempts' );

    //if attempts is blank, set it up as an array
    if(! is_array($attempts)){
         $attempts=array();
    }

    //set attempts to zero for the current IP
    $attempts[$ip]=0;

    //update the database with MonkeyTrappedLogin_failed_attempts
    update_option( 'MonkeyTrappedLogin_failed_attempts', $attempts );
}

// tasks on failed login
public static function failed_login() {
    //get current users ip
    $cip=self::monkey_get_ip();

    // if the ip isn't whitelisted perform the tasks
    if(! self::check_whitelist($cip)){

        //incriment attempts count & get new count
        $lockout_count=self::increment_failed_login_attempts($cip);

            //check to see if it's lockout_time
            if($lockout_count >= get_option( 'MonkeyTrappedLogin_max_failed_attempts' )){

                //check for ip already locked out
                $already_locked==self::check_lockoutlist($cip);

                //lock it out (also extends existing lockouts), increment total, and clear attempts
                self::lockout($cip);
                self::increment_total_lockouts($cip);
                self::clear_attempts($cip);

                    //report the lockout unless it was already
                    if(! $already_locked){
                        self::report_lockout($cip);
                    }
            }
   }
}

//increment failed login attempts
public static function increment_failed_login_attempts($ip) {
    //get the current number of failed login attempts
    $attempts=get_option( 'MonkeyTrappedLogin_failed_attempts' );

    //if there aren't any, set it up as an array
    if(! is_array($attempts)){
        $attempts=array();
    }

    //get the current ip number of attempts
    $num_attempts=$attempts[$ip];

    //set to one or increment if there are already attempts
    if(! $num_attempts){
        $attempts[$ip]=1;
    }else{
    $attempts[$ip]++;
    }

    //update the MonkeyTrappedLogin_failed_attempts in the database
    update_option( 'MonkeyTrappedLogin_failed_attempts', $attempts );

//return new number of attempts
return $attempts[$ip];
}

//increment total lockouts
public static function increment_total_lockouts($ip) {

    //get the current total lockouts array
    $lockouts=get_option( 'MonkeyTrappedLogin_total_lockouts' );

    //if there aren't any, set it up as an array
    if(! is_array($lockouts)){
        $lockouts=array();
    }

    //get the current ip total lockouts
    $num_lockouts=$lockouts[$ip];

    //set to one or increment if there are already lockouts
    if(! $num_lockouts){
        $lockouts[$ip]=1;
    }else{
        $lockouts[$ip]++;
    }

    //update the MonkeyTrappedLogin_total_lockouts in the database
    update_option( 'MonkeyTrappedLogin_total_lockouts', $lockouts );

//return new total lockouts for ip
return $lockouts[$ip];
}

//lock out an ip
public static function lockout($ip) {

    //get lockout list
    $lockoutlist=self::get_lockoutlist();

    //get the lockout length
    $lockout_until=get_option( 'MonkeyTrappedLogin_lockout_length' )+time();

    //add $ip to the lockoutlist with the time to lock out until
    $lockoutlist[$ip]=$lockout_until;

    // update MonkeyTrappedLogin_lockoutlist in the database
    update_option( 'MonkeyTrappedLogin_lockoutlist',$lockoutlist);
}

//report the lockout
public static function report_lockout($ip) {

    //send a notification to admin email (only if the option is selected in settings)
    if(get_option( 'MonkeyTrappedLogin_notify_admin' )){
        wp_mail( get_option( 'admin_email' ), get_option( 'blogname' ).' Lockout Notice', 'Monkey Trapped Login has locked '.$ip.' out of the admin section of '.get_option( 'blogname' ).' for exceeding the maximum number of failed login attempts.' );
    }


    //send a notification to Monkey Trapped Server (only if the option is selected in settings)
    if(get_option('MonkeyTrappedLogin_participating')){
        //build array to send
        $login_array[ip]=$ip;
        $login_array[attempts]=get_option( 'MonkeyTrappedLogin_max_failed_attempts' );
        $login_array[blog]=site_url();
    

	/* REPORT spam comment to Monkey Trapped using curl to post $login_array*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://monkeytrapped.com/login_report.php'); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $login_array);
        $sent=curl_exec($ch);
        curl_close($ch); 
    }
}

//check the whitelist for a specific ip
public static function check_whitelist($check_ip){
    if (! in_array($check_ip, self::get_whitelist())) {
        return false;
    }else{
        return true;
    }
}

//check the blacklist for a specific ip
public static function check_blacklist($check_ip){
    if (! in_array($check_ip, self::get_blacklist())) {
        return false;
    }else{
        return true;
    }
}

//check the lockout list for a specific ip
public static function check_lockoutlist($check_ip){

    //if it's in the blacklist, check no further. 
    if(self::check_blacklist($check_ip)){
        return true;
    }

    //get the lockout list
    $lockoutlist=self::get_lockoutlist();

    //if it's not in there (or it's expired) return false. Otherwise, true
    if (! in_array($check_ip, array_keys($lockoutlist))) {
        return false;
    }else if($lockoutlist[$check_ip] < time()){
        return false;
    }else{
        return true;
    }// Clunky code was done strictly for keeping it clear in my feeble mind.
}

//returns the whitelist in an array
public static function get_whitelist(){
    //get the whitelist
    $whitelist = get_option( 'MonkeyTrappedLogin_whitelist' );
    
    //if it isn't there, set it up as an array
     if(! is_array($whitelist)){
         $whitelist=array();
     }
return $whitelist;
}

//returns the blacklist in an array
public static function get_blacklist(){
    //get the blacklist
    $blacklist = get_option( 'MonkeyTrappedLogin_blacklist' );
      
    //if it isn't there, set it up as an array
    if(! is_array($blacklist)){
        $blacklist=array();
    }
return $blacklist;
}

//returns the lockout list in an array
public static function get_lockoutlist(){
    //get lockout list
    $lockout = get_option( 'MonkeyTrappedLogin_lockoutlist' );
    
    //if it isn't there, set it up as an array
    if(! is_array($lockout)){
        $lockout=array();
    }
return $lockout;
}

//add an ip to the whitelist
public static function add_to_whitelist($new_ip){
    //get the whitelist
    $new_whitelist = self::get_whitelist();
   
    //if it isn't in there, add it
    if (! in_array($new_ip, $new_whitelist)) {
        $new_whitelist[]=$new_ip;

        //and update MonkeyTrappedLogin_whitelist
        update_option('MonkeyTrappedLogin_whitelist', $new_whitelist);
    }
}

//checks done when login page accessed
public static function monkey_check() {

    // fill an array with info on current user
    $current_user=array();
    $current_user['ip']=self::monkey_get_ip();
    $current_user['is_whitelisted']=self::check_whitelist($current_user['ip']);
    $current_user['is_locked_out']=self::check_lockoutlist($current_user['ip']);

    //if the ip is whitelisted we are all good to go
    if($current_user['is_whitelisted']){
        $current_user['is_locked_out']=false;
        return;
    }

    //if the ip is blacklisted we have trouble.
    if($current_user['is_locked_out']){

        //get the lockout message
        $lockout_message=get_option( 'MonkeyTrappedLogin_lockout_message' );

        //echo the lockout message and terminate all other activity. Be done with you.
        echo "</head><body>$lockout_message</body></html>";
        exit;
    }
}

// get users ip even if it's through a load balance or proxy
// credit issue. I copied this from an unknown source a long time ago. 
// if it's yours, let me know and I will credit you
public static function monkey_get_ip() {
    //check for shared ip, then proxy ip, fall back to standard $_SERVER['REMOTE_ADDR']
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

//return filtered ip
return apply_filters( 'wpb_get_ip', $ip );
}


// clean up on deactivation
public static function uninstalled(){
    delete_option( 'MonkeyTrappedLogin_participating' );
    delete_option( 'MonkeyTrappedLogin_failed_attempts' );
    delete_option( 'MonkeyTrappedLogin_max_failed_attempts' );
    delete_option( 'MonkeyTrappedLogin_lockout_length' );
    delete_option( 'MonkeyTrappedLogin_total_lockouts' );
    delete_option( 'MonkeyTrappedLogin_lockoutlist' );
    delete_option( 'MonkeyTrappedLogin_lockout_message');
    delete_option( 'MonkeyTrappedLogin_installed' );
    delete_option( 'MonkeyTrappedLogin_whitelist' );
    delete_option( 'MonkeyTrappedLogin_blacklist' );
    delete_option( 'MonkeyTrappedLogin_notify_admin' );
}

//that's it folks
}
?>
