<?php
/*

	File: king-include/king-page-users-blocked.php
	Description: Controller for page showing users who have been blocked


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: LICENCE.html
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'king-db/selects.php';
	require_once QA_INCLUDE_DIR.'king-app/users.php';
	require_once QA_INCLUDE_DIR.'king-app/format.php';


//	Check we're not using single-sign on integration

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');


//	Get list of blocked users

	$start = qa_get_start();
	$pagesize = qa_opt('page_size_users');

	$userSpecCount = qa_db_selectspec_count( qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED) );
	$userSpec = qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED, $start, $pagesize);

	list($numUsers, $users) = qa_db_select_with_pending($userSpecCount, $userSpec);
	$count = $numUsers['count'];


//	Check we have permission to view this page (moderator or above)

	if (qa_get_logged_in_level() < QA_USER_LEVEL_MODERATOR) {
		$qa_content = qa_content_prepare();
		$qa_content['error'] = qa_lang_html('users/no_permission');
		return $qa_content;
	}


//	Get userids and handles of retrieved users

	$usershtml = qa_userids_handles_html($users);


//	Prepare content for theme

	$qa_content = qa_content_prepare();

	$qa_content['title'] = $count > 0 ? qa_lang_html('users/blocked_users') : qa_lang_html('users/no_blocked_users');

	$html = '<div class="king-users-page">';
	if (count($users)) {
		foreach ($users as $user) {
			$html .= get_user_html($user, '400');
		}
	}
	$html .= '</div>';
	$qa_content['custom'] = $html;

	$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

	$qa_content['navigation']['sub'] = qa_users_sub_navigation();

	$qa_content['class']=' full-page';
	$qa_content['header']= $qa_content['title'];
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/