<?php
/*
Plugin Name: Signup Breach Checker
Plugin URI: https://convexcode.com
Description: Checks user e-mails and optionally passwords against breach lists from haveibeenpwned.com on signup.
Version: 1.0
Author: Dan Dulaney
Author URI: https://codeable.io/developers/dan-dulaney/
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/****************************************************************
* Settings page information
****************************************************************/

//creates a menu page with the following settings
function signup_breach_checker_plugin_menu() {
	add_submenu_page('tools.php', 'Signup Breach Checker', 'Signup Breach Checker', 'manage_options', 'signup-breach-checker', 'signup_breach_checker_display_settings');
}
add_action('admin_menu', 'signup_breach_checker_plugin_menu');

//on-load, sets up the following settings for the plugin
function signup_breach_checker_settings() {
	register_setting( 'signup_breach_checker_settings_group', 'breach_check_passwords' ); //api key
}
add_action( 'admin_init', 'signup_breach_checker_settings' );

/*************************************************************************
* Function to display settings page
**************************************************************************/

//displays the settings page
function signup_breach_checker_display_settings() {
	//form to save api key and calendar settings
	echo "<form method=\"post\" action=\"options.php\">";
	settings_fields( 'signup_breach_checker_settings_group' );
	do_settings_sections( 'signup_breach_checker_settings_group' );


    echo "<div><h1>Signup Breach Checker</h1><h4>Powered by <a href='https://haveibeenpwned.com' target='_blank'>HaveIBeenPwned.com</a>'s API v2</h4>
<p>Welcome! This plugin is meant to provide a service to your site members by doing the following: <ul style=\"list-style-type:square\">
<li>On user registration, check the haveibeenpwned API to see if their e-mail has been in any known breaches</li>
<li>Stores (in user_meta) any breaches found, and if the user has been notified (by your site)</li>
<li>If welcome e-mails are enabled, adds a section sharing information about the breaches, and the suggestion to use a strong password with a link to help. If not, it also lets them know they are clean.</li>
<li>Optional: Enable password checking against the API's list of known passwords on password reset / new user password set. This only triggers if the user also has had their e-mail leaked in a known breach, and e-mails the user with additional information.</li> 
</ul>
</p>";

echo "<table id=\"gcal-settings\" class=\"form-table\">
	<tr><td colspan=\"2\"><h2>Should we also check passwords? (off by default)</h2></td></tr> 
	<tr><td colspan=\"2\">Send sha1 hashed PW's to haveibeenpwned.com if the e-mail is flagged as being leaked<br>Sends the user a notification email suggesting a stronger password</td></tr> 
       <tr valign=\"top\">
        <th scope=\"row\">Send Passwords for Checking<br></th>
        <td><select name='breach_check_passwords'>";

	$breach_check_passwords  = get_option('breach_check_passwords','no');
	if ($breach_check_passwords == 'no') {
		echo "<option value='no' selected='selected'>No, don't check passwords on pw reset.</option>
		<option value='yes'>Yes, please check passwords on pw reset.</option>";
	} else {
		echo "<option value='no'>No, don't check passwords on pw reset.</option>
		<option value='yes' selected='selected'>Yes, please check passwords on pw reset.</option>";

	}

	echo "</select>
	</td></tr></table>";

	submit_button();
	echo "</form>";

}

/**************************************************************************
* Function to check e-mail against API and return a result as an array (built to be expanded on)
***************************************************************************/

function signup_breach_check_email($email) {

	if (empty($email)) { return array('No e-mail was entered.'); }

	//Set and escape API call url
	$base_api_email_url = esc_url("https://haveibeenpwned.com/api/v2/breachedaccount/".$email.'?truncateResponse=true');

	//Call the API and get the response
	$api_email_response = wp_remote_get($base_api_email_url);
	
	//Get the response code
	$response_code = wp_remote_retrieve_response_code( $api_email_response );

	$checked = false;
	$count = 0;		
		
	while(!$checked && $count < 6) {

		$return_array = array();
	
		switch($response_code) {

			//Account found in a verified breach
			case 200:  

				$response_body_array = json_decode(wp_remote_retrieve_body( $api_email_response ));

				foreach ($response_body_array as $breached_site) {

					$return_array[] = $breached_site->Name;
					
				}
				$checked = true;
				break;

			//Account / parameter was blank
			case 400:

				$return_array[] = 'No e-mail was entered.';
				$checked = true;
				break;

			//Account was not found in a breach
			case 404:
			
				$return_array[] = 'No breach was found.';
				$checked = true;
				break;

			//Rate limited, wait then try again
			case 429: 

				$checked = false;
				break;

			default:

				$return_array[] = 'Something else went wrong';
				$checked = true;

		}

		$count++;
			
		if (!$checked) {

			if ($count = 6) { 
				$return_array[] = 'Our registration process is under heavy load. Please try again later.';
			}
			else {
				sleep(1.51);
			}	
		}

	}


	return $return_array;

}


/***********************************************************************
* Function to check password against API and return a string response (built to be expanded on later)
************************************************************************/
function signup_breach_check_password($password) {

	if (empty($password)) { return array('No password was entered'); }

	//Hash password before sending
	$hashed_password = sha1($password);

	//Set and escape API call url
	$base_api_password_url = esc_url("https://haveibeenpwned.com/api/v2/pwnedpassword/".$hashed_password);

	//echo $base_api_password_url;

	//Call the API and get the response
	$api_password_response = wp_remote_get($base_api_password_url);
	
	//Get the response code
	$response_code = wp_remote_retrieve_response_code( $api_password_response );

	$checked = false;
	$count = 0;		
		
	while(!$checked && $count < 6) {

		$return_code = '';
	
		switch($response_code) {

			//Password found in a verified breach
			case 200:  

				$return_code = 'Your password was found in password dumps.';
				$checked = true;
				break;

			//Account / parameter was blank
			case 400:

				$return_code = 'No password was entered.';
				$checked = true;
				break;

			//Account was not found in a breach
			case 404:
			
				$return_code = 'Password was not found in breach dumps.';
				$checked = true;
				break;

			//Rate limited, wait then try again
			case 429: 

				$checked = false;
				break;

			default:

				$return_code = 'Something else went wrong.';
				$checked = true;
		}

		$count++;
			
		if (!$checked) {

			if ($count = 6) { 
				$return_code = 'Rate limit exceded 5 times. Try again later';
			}
			else {
				sleep(1.51);
			}	
		}

	}

	return $return_code;
}

/**********************************************************************************
* Uses user_meta from e-mail breach check to notify users in the welcome e-mail
* If clean, tells them that
* If not, shows list of breaches and suggests picking a strong password.
**********************************************************************************/

function signup_checker_email_message_filter($wp_new_user_notification_email, $user, $blogname) {

    $message = "\r\n".$wp_new_user_notification_email['message'];

    $user_id = $user->ID;
    $sites = get_user_meta($user_id, 'sites_found_in_breach', true);

    if(is_array($sites)) {

	$message .= "\r\n".__('NOTICE: Your e-mail address was found in a list of known password breaches:') . "\r\n";

	foreach($sites as $site) {
		$message .= __('* ')."$site\r\n";
	}
	$message .= "\r\n".__('Be sure to pick a password that you have not used before!') . "\r\n";
	$message .= "\r\n".__('For help picking a strong password, try < https://passwordsgenerator.net >') . "\r\n\r\n";
	update_user_meta( $user_id, 'was_user_notified_of_breach', 'yes');
    } else {
       
	$message .= "\r\n".__('Congrats! Your e-mail address was NOT found in any list known password breaches.') . "\r\n";

    }
    $message .= __('Breach Data Courtesy of: HaveIBeenPwned.com') . "\r\n";
    $wp_new_user_notification_email['message'] = $message;
    return $wp_new_user_notification_email;
}

add_filter ( 'wp_new_user_notification_email', 'signup_checker_email_message_filter', 10, 2 );


/*********************************************************************************
* Checks if e-mail used to register user has been in a data breach on user_register
* Saves relevant results in user_meta
***********************************************************************************/
function signup_checker_registration_save( $user_id ) {

    $user_info = get_userdata($user_id);
    $user_email = $user_info->user_email;

    $email_breached = signup_breach_check_email($user_email);

    switch ($email_breached[0]) {
	case 'No e-mail was entered.':
		break;
	case 'Something else went wrong.':
		break;
	case 'No breach was found.':
		break;
	case 'Our registration process is under heavy load. Please try again later.':
		add_user_meta( $user_id, 'email_check_timed_out', 'yes');			
		break;
	default:

		add_user_meta( $user_id, 'sites_found_in_breach', $email_breached);
		add_user_meta( $user_id, 'was_user_notified_of_breach', 'no');			
    }
    
}
add_action( 'user_register', 'signup_checker_registration_save', 10, 1 );


/***************************************************************************************
* Checks password (hashed) against HaveIBeenPwned.com's api. Toggleable from plugin options
****************************************************************************************/

function signup_breach_checker_password_reset_check( $user, $new_pass ) {

    $breach_check_passwords  = get_option('breach_check_passwords','no');

    if ($breach_check_passwords != 'no') {
	    $user_id = $user->ID;
	    $sites = get_user_meta($user_id, 'sites_found_in_breach', true);

	    if(is_array($sites)) {

	        $check_password = signup_breach_check_password($password);
	        if ($check_password = 'Your password was found in password dumps.') {

			$blog_name = get_bloginfo('name');
			$message = "Extra Security Notification from $blog_name"."\r\n\r\n"."This is just a friendly alert that the password you just chose matches one found in large password lists from past data breaches on other websites, as does your e-mail address.\r\n\r\nWe suggest that you consider choosing a stronger or more unique password, but do not require it.\r\n\r\nData Breach Info Courtesy of: HaveIBeenPwned.com";


			wp_mail( $user->user_email, 'Extra Security Notification: '.$blog_name, $message );
		}
	}
    }
}
add_action( 'password_reset', 'signup_breach_checker_password_reset_check', 10, 2 );
