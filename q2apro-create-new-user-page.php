<?php

	class q2apro_create_new_user 
	{
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		// for display in admin interface under admin/pages
		function suggest_requests() 
		{	
			return array(
				array(
					'title' => 'Create New User Page', // title of page
					'request' => 'createnewuser', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='createnewuser')
			{
				return true;
			}

			return false;
		}

		function process_request($request)
		{
			
			$qa_content = qa_content_prepare();
			$qa_content['custom'] = '';
	
			// return if not admin
			if(qa_get_logged_in_level() < QA_USER_LEVEL_ADMIN) 
			{
				$qa_content['error'] = 'Access not allowed';
				return $qa_content;
			}
			
			require_once QA_INCLUDE_DIR.'db/users.php';
			require_once QA_INCLUDE_DIR.'app/users.php';
			require_once QA_INCLUDE_DIR.'app/users-edit.php';

			$email_in = qa_post_text('email'); 
			$handle_in = qa_post_text('handle'); 
			$password_in = qa_post_text('password'); 
			$error = null;
			
			if(!empty($email_in))
			{
				// check if usermail exists already
				$userid_check = q2apro_get_userid_by_email($email_in);
				if(empty($userid_check))
				{
					// does not exist yet 
					$handle_check = q2apro_finduserhandle($handle_in);
					if(empty($handle_check))
					{
						$validation_errors = array_merge(
							qa_handle_email_filter($handle_in, $email_in),
							qa_password_validate($password_in)
						);
						
						if(empty($validation_errors))
						{
							// create user, last param is $confirmed 
							$userid = q2apro_create_new_user($email_in, $password_in, $handle_in, QA_USER_LEVEL_BASIC, true);
							$qa_content = qa_content_prepare();
							$qa_content['custom'] = '
							<div class="usercreated-wrap">
								<h3>
									New user created: 
								</h3>
								<p>
									Email: '.$email_in.'
								</p>
								<p>
									Userhandle: <a href="'.qa_path('user').'/'.$handle_in.'">'.$handle_in.'</a> 
								</p>
								<p>
									Password: '.$password_in.'
								</p>
								<p>
									Userid: '.$userid.'
								</p>
							</div> <!-- usercreated-wrap -->
							';
							
							// clear posts to have empty input values 
							$email_in = $password_in = $handle_in = '';
						}
						else 
						{
							$error = http_build_query($validation_errors);
						}
					}
					else 
					{
						$error = 'Userhandle exists already, see: <a href="'.qa_path('user').'/'.$handle_in.'">'.$handle_in.'</a>';
					}
				}
				else 
				{
					$userhandle = qa_userid_to_handle($userid_check);
					$error = 'Usermail exists already. Userid: '.$userid_check.' | Handle: <a href="'.qa_path('user').'/'.$userhandle.'">'.$userhandle.'</a>';
				}
			}
			
			if(!is_null($error))
			{
				$qa_content['title'] = 'Create new user';
				$qa_content['error'] = $error;
				$qa_content['custom'] = '
					<h3 class="headline-error">
						Error detected, please try again: 
					</h3>
				';
			}
			
			$qa_content['title'] = 'Create new user';
			
			$qa_content['custom'] .= '
			
			<div class="wrap-createnewuser-form">
				<form action="" method="post">
					
					<label>
						Email:
					</label>
					<input name="email" type="text" value="'.$email_in.'" required />
					
					<label>
						Handle: 
					</label>
					<input name="handle" type="text" value="'.$handle_in.'" required />
					
					<label>
						Password:
					</label>
					<input name="password" type="text" value="'.$password_in.'" required />
					
					<p>
						<button type="submit">Create</button> 
					</p>

				</form>
			</div> <!-- wrap-createnewuser-form -->
			';
			
			$qa_content['custom'] .= '
				<style type="text/css">
					.wrap-createnewuser-form label {
						display:block;
						margin-bottom:5px;
					}
					.wrap-createnewuser-form input {
						margin-bottom:20px;
					}
					.headline-error {
						color:#F00;
					}
					.usercreated-wrap {
						display:block;
						margin-bottom:50px;
						width:300px;
						background:#F90;
						padding:20px;
					}
				</style>
			';
			
			return $qa_content;
		}
		
	}; // END q2apro_create_new_user
	

/*
	Omit PHP closing tag to help avoid accidental output
*/