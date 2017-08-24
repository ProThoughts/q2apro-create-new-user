<?php
/*
	Plugin Name: Create New Users 
	Plugin URI: https://github.com/q2apro/q2apro-create-new-users
	Plugin Description: Create new users manuallz from a seperate page for admins 
	Plugin Version: 0.1
	Plugin Date: 2017-08-24
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: 

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html
	
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}

// page
qa_register_plugin_module('page', 'q2apro-create-new-user-page.php', 'q2apro_create_new_user', 'Create New User Page');


function q2apro_get_userid_by_email($usermail)
{
	return qa_db_read_one_value(
			qa_db_query_sub('
					SELECT userid 
					FROM `^users`
					WHERE email = # 
					', 
					$usermail
				), 
			true);
}

function q2apro_generate_userhandle($name)
{ 
	// remove all whitespaces
	$inhandle = preg_replace('/\s+/', '', $name);
	// transliterate (in case we want an email system later on where the username is the email name or a subdomain for each)
	$inhandle = transliterate_string($inhandle);
	// remove special characters
	$inhandle = preg_replace("/[^a-zA-Z0-9]+/", "", $inhandle);
	// maximal length is 18 chars, see db qa_users
	$inhandle = substr($inhandle, 0, 8);
	// all small letters
	$inhandle = strtolower($inhandle);
	// check if username does exist already
	$getusername = mr_finduserhandle($inhandle);
	
	// if exists then change last letter to number and check again
	if(!is_null($getusername))
	{
		$replacenr = 2;
		$isunique = false;
		while(!$isunique)
		{
			// replace last char by number
			$inhandle = substr($inhandle, 0, 17).$replacenr;
			// check again if does exist
			$getusername = mr_finduserhandle($inhandle);
			$isunique = is_null($getusername);
			$replacenr++;
		}
	}		
	return $inhandle;
}

function q2apro_finduserhandle($inhandle)
{ 
	return qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT handle FROM ^users WHERE handle=#',
							$inhandle
						), 
					true); 
}

// modified qa_create_new_user
function q2apro_create_new_user($email, $password, $handle, $level=QA_USER_LEVEL_BASIC, $confirmed=false)
{

	require_once QA_INCLUDE_DIR.'db/users.php';
	require_once QA_INCLUDE_DIR.'db/points.php';
	require_once QA_INCLUDE_DIR.'app/options.php';
	require_once QA_INCLUDE_DIR.'app/emails.php';
	require_once QA_INCLUDE_DIR.'app/cookies.php';

	$userid=qa_db_user_create($email, $password, $handle, $level, qa_remote_ip_address());
	qa_db_points_update_ifuser($userid, null);
	qa_db_uapprovecount_update();

	if ($confirmed)
		qa_db_user_set_flag($userid, QA_USER_FLAGS_EMAIL_CONFIRMED, true);

	if (qa_opt('show_notice_welcome'))
		qa_db_user_set_flag($userid, QA_USER_FLAGS_WELCOME_NOTICE, true);

	$custom=qa_opt('show_custom_welcome') ? trim(qa_opt('custom_welcome')) : '';
	
	// q2apro extra: set flag for no newsletter
	qa_db_user_set_flag($userid, QA_USER_FLAGS_NO_MAILINGS, true);
	
	/*
	if (qa_opt('confirm_user_emails') && ($level<QA_USER_LEVEL_EXPERT) && !$confirmed) {
		$confirm=strtr(qa_lang('emails/welcome_confirm'), array(
			'^url' => qa_get_new_confirm_url($userid, $handle)
		));

		if (qa_opt('confirm_user_required'))
			qa_db_user_set_flag($userid, QA_USER_FLAGS_MUST_CONFIRM, true);

	} else
		$confirm='';
	*/
	
	/*
	// no approval needed 
	if (qa_opt('moderate_users') && qa_opt('approve_user_required') && ($level<QA_USER_LEVEL_EXPERT))
		qa_db_user_set_flag($userid, QA_USER_FLAGS_MUST_APPROVE, true);
	*/
	
	/*
	// DO NOT inform by email 
	qa_send_notification($userid, $email, $handle, qa_lang('emails/welcome_subject'), qa_lang('emails/welcome_body'), array(
		'^password' => isset($password) ? qa_lang('main/hidden') : qa_lang('users/password_to_set'), // v 1.6.3: no longer email out passwords
		'^url' => qa_opt('site_url'),
		'^custom' => strlen($custom) ? ($custom."\n\n") : '',
		'^confirm' => $confirm,
	));
	*/

	qa_report_event('u_register', $userid, $handle, qa_cookie_get(), array(
		'email' => $email,
		'level' => $level,
	));

	return $userid;
}

/*
	Omit PHP closing tag to help avoid accidental output
*/