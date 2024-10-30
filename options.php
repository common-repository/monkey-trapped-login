<?php
// create custom plugin settings menu
add_action('admin_menu', 'monkey_login_create_menu');


function monkey_login_create_menu() {

	//create new 2nd-level menu
	add_options_page('Monkey Login Settings', 'Monkey Login', 'administrator', __FILE__, 'monkey_login_settings_page');

	//call register settings function
	add_action( 'admin_init', 'register_monkey_login_settings' );
}

function register_monkey_login_settings() {
	//register our settings
	register_setting( 'monkey-login-settings-group', 'MonkeyTrappedLogin_participating' );
	register_setting( 'monkey-login-settings-group', 'MonkeyTrappedLogin_lockout_length' );
	register_setting( 'monkey-login-settings-group', 'MonkeyTrappedLogin_lockout_message' );
	register_setting( 'monkey-login-settings-group', 'MonkeyTrappedLogin_whitelist' );
	register_setting( 'monkey-login-settings-group', 'MonkeyTrappedLogin_blacklist' );
	register_setting( 'monkey-login-settings-group', 'MonkeyTrappedLogin_max_failed_attempts' );
	register_setting( 'monkey-login-settings-group', 'MonkeyTrappedLogin_notify_admin' );

}

function monkey_login_settings_page() {
    //runs every options page access
 
    //check whitelist and convert it to an array if needed
    $whitelist=get_option('MonkeyTrappedLogin_whitelist');
    if(! is_array($whitelist)){
        $whitelist_array = explode("\n", $whitelist);
        update_option('MonkeyTrappedLogin_whitelist', $whitelist_array);
    }

    //check blacklist and convert it to an array if needed
    $blacklist=get_option('MonkeyTrappedLogin_blacklist');
    if(! is_array($blacklist)){
        $blacklist_array = explode("\n", $blacklist);
        update_option('MonkeyTrappedLogin_blacklist', $blacklist_array);
    }     
?>
<div class="wrap">
<h2>Monkey Trapped Login</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'monkey-login-settings-group' ); ?>
    <?php do_settings_sections( 'monkey-login-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Participate</th>
        <td><input type="checkbox" name="MonkeyTrappedLogin_participating" value="true" <?php if(esc_attr( get_option('MonkeyTrappedLogin_participating') )){echo 'checked';}; ?> /></td>
        <td>By checking participate you will be sending information about lockouts to the Monkey Trapped server as they happen. We rely on participation from WordPress site admins to build more accurate and comprehensive block lists.</td>
        </tr>
         <tr valign="top">
        <th scope="row">Notify Admin</th>
        <td><input type="checkbox" name="MonkeyTrappedLogin_notify_admin" value="true" <?php if(esc_attr( get_option('MonkeyTrappedLogin_notify_admin') )){echo 'checked';}; ?> /></td>
        <td>By checking Admin Notify an email will sent to the admin when a lockout occurs.</td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Max Attempts</th>
        <td><input type="text" size="3" name="MonkeyTrappedLogin_max_failed_attempts" value="<?php echo esc_attr( get_option('MonkeyTrappedLogin_max_failed_attempts') ); ?>"/></td>
        <td>The number of failed login attempts that will trigger a lockout.</td>
        </tr>
<tr valign="top">
        <th scope="row">Lockout Duration</th>
        <td><input type="text" size="3" name="MonkeyTrappedLogin_lockout_length" value="<?php echo esc_attr( get_option('MonkeyTrappedLogin_lockout_length') ); ?>"/>seconds</td>
        <td>The length of time (in seconds) that a lockout will last. Example: 3600 would be a one hour lockout.</td>
        </tr>
<tr valign="top">
        <th scope="row">Lockout Message</th>
        <td colspan="2"><input type="text" size="45" name="MonkeyTrappedLogin_lockout_message" value="<?php echo esc_attr( get_option('MonkeyTrappedLogin_lockout_message') ); ?>"/></td>
        
        </tr>

<tr valign="top">
        <th scope="row">White List<br>(never blocked)</th>
        <td colspan="2">
<textarea id="MonkeyTrappedLogin_whitelist" name="MonkeyTrappedLogin_whitelist" rows="5" cols="36"><?php 
    //get the whitelist
    $whitelist = get_option('MonkeyTrappedLogin_whitelist');

    //put it in the text area
    if(is_array($whitelist)){
       foreach($whitelist as $value){
       echo $value.'
';//cheesy
       }
    }else{
echo $whitelist;
}
?></textarea>

        
        </tr>
<tr valign="top">
        <th scope="row">Black List<br>(always blocked unless on whitelist)</th>
        <td colspan="2">
<textarea id="MonkeyTrappedLogin_blacklist" name="MonkeyTrappedLogin_blacklist" rows="5" cols="36"><?php 
    //get the blacklist
    $blacklist = get_option('MonkeyTrappedLogin_blacklist');

    //put it in the textarea
    if(is_array($blacklist)){
        foreach($blacklist as $value){
        echo $value.'
';//cheesy again
        }
    }else{
echo $blacklist;
}
?></textarea>

        
        </tr>

        
    </table>
    
    <?php submit_button(); ?>

</form>
<hr />
<h3>Lock Out History:</h3>
<span style="color: red;font-weight: bold;">Bold Red = Active Lockout.</span><br />
<?php

    //get info on lockouts(active and historical)
    $lockedout = get_option(MonkeyTrappedLogin_total_lockouts);

    //echo it out bold and red if active, normal if past
    if(is_array($lockedout)){
        foreach($lockedout as $key => $value){
            $font='';
            if(MonkeyTrappedLogin::check_lockoutlist($key)){
                $font=' style="color: red;font-weight: bold;"';
            }
        echo '<span '.$font.'>'.$key.'&nbsp;&nbsp; total lockouts - '.$value.'</span><br />';
        }
    }
?>
</div>
<?php } ?>
