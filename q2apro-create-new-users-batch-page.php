<?php

	class q2apro_create_new_users_batch 
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
					'title' => 'Create New Users Batch Page', // title of page
					'request' => 'createnewusers', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='createnewusers')
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
			
			
			$userdata_textarea = qa_post_text('userdata_textarea'); 
			
			if(!empty($userdata_textarea))
			{
				// get each line separate into the array 
				$userdata_Array = explode("\n", $userdata_textarea);
				// remove all empty entries 
				$userdata_Array = array_filter($userdata_Array);
				// remove any extra \r characters left behind
				$userdata_Array = array_filter($userdata_Array, 'trim');
				
				$output = '';
				$errors = '';
				
				// css 
				$output .= '
				<style type="text/css">
					.create-users-warning p {
						color:#F00;
					}
					.usercreated-wrap {
						display:block;
						margin-bottom:20px;
						width:90%;
						background:#F90;
						padding:10px;
					}
				</style>
				';
			
				foreach($userdata_Array as $userdata_line)
				{
					// $userdata_line is string "email;password;handle"
					$userdata = explode(";", $userdata_line);
					if(empty($userdata[0]) || empty($userdata[1]) || empty($userdata[2]))
					{
						$errors .= '
						<p>
							Missing userdata!
						</p>
						';
						continue;
					}
					$email_in = $userdata[0];
					$password_in = $userdata[1];
					$handle_in = $userdata[2];
					
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
								$output .= '
								<div class="usercreated-wrap">
									<p>
										New user created: <br>
										Userid: '.$userid.' | Email: '.$email_in.' | Handle: <a href="'.qa_path('user').'/'.$handle_in.'">'.$handle_in.'</a> | 
										Password: '.$password_in.' 
									</p>
								</div> <!-- usercreated-wrap -->
								';
							}
							else 
							{
								$errors .= '
								<p>
									'.http_build_query($validation_errors).'
								</p>
								';
							}
						}
						else 
						{
							$errors .= '
							<p>
								Handle exists already, see user page: <a target="_blank" href="'.qa_path('user').'/'.$handle_in.'">'.$handle_in.'</a>
							</p>
							';
						}
					}
					else 
					{
						$userhandle = qa_userid_to_handle($userid_check);
						$errors .= '
						<p>
							Usermail exists already. Userid: '.$userid_check.' | Handle: <a href="'.qa_path('user').'/'.$userhandle.'">'.$userhandle.'</a>
						</p>
						';
					}
				} // END foreach 
				
				if(!empty($errors))
				{
					$output = '
						<h3>
							Some Errors detected:
						</h3>
						<div class="create-users-warning">
							'.$errors.'
						</div>
					'.$output;
				}
				
				$qa_content['title'] = 'Results for User creation';
				$qa_content['custom'] = '
					<div class="create-success-wrap">
						'.$output.'
					</div>
					
					<a href="/createnewusers" class="qa-form-tall-button">back</a>
				';
				return $qa_content;
			} // END if(!empty($userdata_textarea))
			
			$qa_content['title'] = 'Batch-create new users';
			
			$qa_content['custom'] .= '
			<p>
				Enter the userdata semicolon-separated in this order (one user per line): <b>email;password;handle</b>
			</p>
			<div class="wrap-createnewusers-form">
				<form action="" method="post">
					
					<textarea name="userdata_textarea" id="userdata_textarea" class="qa-form-tall-text" style="min-height:150px;"></textarea>
					
					<p>
						<button type="submit" class="qa-form-tall-button">Create Users</button> 
					</p>

				</form>
			</div> <!-- wrap-createnewusers-form -->
			
			<p>
				If email or handle exist already, you get a warning. 
			</p>
			';
			
			return $qa_content;
		}
		
	}; // END q2apro_create_new_users_batch
	

/*
	Omit PHP closing tag to help avoid accidental output
*/