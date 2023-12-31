<?php
/*

	File: king-include/king-page-message.php
	Description: Controller for private messaging page


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
	require_once QA_INCLUDE_DIR.'king-app/limits.php';
	require_once QA_INCLUDE_DIR . 'king-db/metas.php';
	
	$loginUserId = qa_get_logged_in_userid();
	$loginUserHandle = qa_get_logged_in_handle();

	qa_db_usermeta_set( $loginUserId, 'm_count', 0 );
//	Check which box we're showing (inbox/sent), we're not using KINGMEDIA's single-sign on integration and that we're logged in

	$req = qa_request_part(1);
	if ($req === null)
		$showOutbox = false;
	elseif ($req === 'sent')
		$showOutbox = true;
	else
		return include QA_INCLUDE_DIR.'king-page-not-found.php';

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');

	if (!isset($loginUserId)) {
		$qa_content = qa_content_prepare();
		$qa_content['error'] = qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
		return $qa_content;
	}

	if (!qa_opt('allow_private_messages') || !qa_opt('show_message_history'))
		return include QA_INCLUDE_DIR.'king-page-not-found.php';


//	Find the messages for this user

	$start = qa_get_start();
	$pagesize = qa_opt('page_size_pms');

	// get number of messages then actual messages for this page
	$func = $showOutbox ? 'qa_db_messages_outbox_selectspec' : 'qa_db_messages_inbox_selectspec';
	$pmSpecCount = qa_db_selectspec_count( $func('private', $loginUserId, true) );
	$pmSpec = $func('private', $loginUserId, true, $start, $pagesize);

	$userMessages = qa_db_select_with_pending($pmSpec);
	$count = count($userMessages);


//	Prepare content for theme

	$qa_content = qa_content_prepare();
	$qa_content['title'] = qa_lang_html( $showOutbox ? 'main/pm_outbox_title' : 'main/pm_inbox_title' );
	$qa_content['script_rel'][] = 'king-content/king-user.js?'.QA_VERSION;

	if ($userMessages) {

		$qa_content['message_list'] = array(
			'tags' => 'id="privatemessages"',
			'messages' => array(),
			'form' => array(
				'tags' => 'name="pmessage" method="post" action="'.qa_self_html().'"',
				'style' => 'tall',
				'hidden' => array(
					'qa_click' => '', // for simulating clicks in Javascript
					'handle' => qa_html($loginUserHandle),
					'start' => qa_html($start),
					'code' => qa_get_form_security_code('pm-'.$loginUserHandle),
				),
			),
		);
	} else {
		$qa_content['custom'] = '<div class="nopost"><i class="far fa-frown-open fa-4x"></i> '.qa_lang_html('main/no_unselected_qs_found').'</div>';
	}

	$htmlDefaults = qa_message_html_defaults();
	if ($showOutbox)
		$htmlDefaults['towhomview'] = true;

	foreach ($userMessages as $message) {
		$msgFormat = qa_message_html_fields($message, $htmlDefaults);
		$replyHandle = $showOutbox ? $message['tohandle'] : $message['fromhandle'];
		$replyid = $showOutbox ? $message['touserid'] : $message['fromuserid'];
		$msgFormat['form'] = array(
			'style' => 'light',
			'buttons' => array(
				'reply' => array(
					'tags' => 'onclick="window.location.href=\''.qa_path_html('message/'.$replyHandle).'\';return false"',
					'label' => qa_lang_html('question/reply_button'),
				),
				'delete' => array(
					'tags' => 'name="m'.qa_html($message['messageid']).'_dodelete" onclick="return qa_pm_click('.qa_js($message['messageid']).', this, '.qa_js($showOutbox ? 'outbox' : 'inbox').', '.qa_js($replyid).');"',
					'label' => qa_lang_html('question/delete_button'),
					'popup' => qa_lang_html('profile/delete_pm_popup'),
				),
			),
		);

		$qa_content['message_list']['messages'][] = $msgFormat;
		$qa_content['message_list']['wpage'] = $showOutbox ? 'out' : 'in';
	}


	$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

	$qa_content['navigation']['sub'] = qa_messages_sub_navigation($showOutbox ? 'outbox' : 'inbox');

	return $qa_content;
