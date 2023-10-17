<?php
/*

	File: king-include/king-ajax-wallpost.php
	Description: Server-side response to Ajax wall post requests


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

	require_once QA_INCLUDE_DIR.'king-app/messages.php';
	require_once QA_INCLUDE_DIR.'king-app/users.php';
	require_once QA_INCLUDE_DIR.'king-app/cookies.php';
	require_once QA_INCLUDE_DIR.'king-db/selects.php';
require_once QA_INCLUDE_DIR.'king-app/format.php';
	require_once QA_INCLUDE_DIR.'king-app/limits.php';
	$message=qa_post_text('message');
	$tohandle=qa_post_text('handle');
	$morelink=qa_post_text('morelink');

	$touseraccount=qa_db_select_with_pending(qa_db_user_account_selectspec($tohandle, false));
	$loginuserid=qa_get_logged_in_userid();

	$errorhtml=qa_wall_error_html($loginuserid, $touseraccount['userid'], $touseraccount['flags']);

	if ( !$loginuserid || (!strlen($message)) || !qa_check_form_security_code('message-'.$tohandle, qa_post_text('code')) ) {
		echo "QA_AJAX_RESPONSE\n0"; // if there's an error, process in non-Ajax way
	} else {
		$messageid=king_pm_add_post($loginuserid, qa_get_logged_in_handle(), qa_cookie_get(),
			$touseraccount['userid'], $touseraccount['handle'], $message, '');



		list($torecent, $fromrecent) = qa_db_select_with_pending(
			qa_db_recent_messages_selectspec($loginuserid, true, $tohandle, false),
			qa_db_recent_messages_selectspec($tohandle, false, $loginuserid, true)
		);
		$recent = array_merge($torecent, $fromrecent);
		qa_sort_by($recent, 'created');

		$showmessages = array_slice(array_reverse($recent, true), 0, 140);
		$options = qa_message_html_defaults();


		$themeclass=qa_load_theme_class(qa_get_site_theme(), 'messages', null, null);
		$themeclass->initialize();

		echo "QA_AJAX_RESPONSE\n1\n";

		echo 'm'.$messageid."\n"; // element in list to be revealed



		foreach ($showmessages as $message) {
			$themeclass->pmmessage_item(qa_message_html_fields($message, $options));
		}

	}


/*
	Omit PHP closing tag to help avoid accidental output
*/