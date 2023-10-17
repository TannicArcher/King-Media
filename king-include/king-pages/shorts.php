<?php
/*

	File: king-include/king-page/shorts.php
	Description: Controller for page listing recent questions without upvoted/selected/any answers


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
	require_once QA_INCLUDE_DIR . 'king-db/metas.php';
	require_once QA_INCLUDE_DIR . 'king-app-video.php';

	$userid=qa_get_logged_in_userid();
	$questions = qa_db_single_select( get_shorts($userid, 100) );
	$count = count($questions);
	$qa_content = qa_content_prepare();
	$qa_content['title']=qa_lang_html('misc/shorts');
	if ( $count ) {
		foreach ($questions as $question) {
			$qa_content['shorts'][]=qa_post_html_fields($question, $userid, qa_cookie_get(), array(), null, array(
			'voteview' => qa_get_vote_view($question, true), // behave as if on question page since the vote succeeded
		));
		}
		
	} else {
		$qa_content['custom'] = '<div class="nopost"><i class="far fa-frown-open fa-4x"></i> '.qa_lang_html('main/no_unselected_qs_found').'</div>';
	}

	$qa_content['class']=' full-page';
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/