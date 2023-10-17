<?php
/*

	File: king-include/king-page-question.php
	Description: Controller for question page (only viewing functionality here)


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

	require_once QA_INCLUDE_DIR.'king-app/cookies.php';
	require_once QA_INCLUDE_DIR.'king-app/format.php';
	require_once QA_INCLUDE_DIR.'king-db/selects.php';
	require_once QA_INCLUDE_DIR.'king-util/sort.php';
	require_once QA_INCLUDE_DIR.'king-util/string.php';
	require_once QA_INCLUDE_DIR.'king-app/captcha.php';
	require_once QA_INCLUDE_DIR.'king-pages/question-view.php';
	require_once QA_INCLUDE_DIR.'king-app/updates.php';

	$postid=qa_request_part(0);
	$userid=qa_get_logged_in_userid();
	$cookieid=qa_cookie_get();


//	Get information about this question

	list($post, $extravalue)=qa_db_select_with_pending(
		qa_db_full_post_selectspec($userid, $postid),
		qa_db_post_meta_selectspec($postid, 'qa_q_extra')
	);

	if (isset($post['basetype']) && $post['basetype'] != 'Q') {
		$post=null;
	}

	if (isset($post)) {

		$post=$post+qa_page_q_post_rules($post, null, null, null); // array union


	}

//	Deal with question not found or not viewable, otherwise report the view event

	if (!isset($post))
		return include QA_INCLUDE_DIR.'king-page-not-found.php';

	if (!$post['viewable']) {

		if ($post['queued'])
			$error=qa_lang_html('question/q_waiting_approval');
		elseif ($post['flagcount'] && !isset($post['lastuserid']))
			$error=qa_lang_html('question/q_hidden_flagged');
		elseif ($post['authorlast'])
			$error=qa_lang_html('question/q_hidden_author');
		else
			$error=qa_lang_html('question/q_hidden_other');


	}

	$permiterror=qa_user_post_permit_error('permit_view_q_page', $post, null, false);

	if ( $permiterror && (qa_is_human_probably() || !qa_opt('allow_view_q_bots')) ) {

		$topage=qa_q_request($postid, $post['title']);

		switch ($permiterror) {
			case 'login':
				$error=qa_insert_login_links(qa_lang_html('main/view_q_must_login'), $topage);
				break;

			case 'confirm':
				$error=qa_insert_login_links(qa_lang_html('main/view_q_must_confirm'), $topage);
				break;

			case 'approve':
				$error=qa_lang_html('main/view_q_must_be_approved');
				break;

			default:
				$error=qa_lang_html('users/no_permission');
				break;
		}


	}
$shareurl  = qa_path_html(qa_q_request($post['postid'], $post['title']), null, qa_opt('site_url'));
$featured = king_get_uploads($post['content']);
$output = '<!DOCTYPE html>
<html lang="en" dir="ltr" data-cast-api-enabled="true">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>YouTube</title>
    <link rel="canonical" href="'.qa_html($shareurl).'" />
    <style>
    body {
font: 12px Roboto, Arial, sans-serif;
    background-color: #000;
    color: #fff;
    height: 100%;
    width: 100%;
    overflow: hidden;
    position: absolute;
    margin: 0;
    padding: 0;
}
#embed-video {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    top: 0;
    height: 100%;
    width: 100%;

}
#player {
	    height: 100%;
    width: 100%;
    display:flex;
    align-items:center;
    justify-content:center;
}
</style>
<script src="' . qa_path_to_root() . 'king-content/js/videojs/video.min.js"></script>
<link href="' . qa_path_to_root() . 'king-content/js/videojs/video-js.css" rel="stylesheet" >
<link rel="stylesheet" href="' . qa_path_to_root() . 'king-theme/default/font-awesome/css/all.min.css" type="text/css" media="all">


  </head>
  <body
    class="date-20220425 en_US ltr site-center-aligned site-as-giant-card webkit webkit-537"
    dir="ltr"
  >';
 $output .= '<div id="player">';
if( !isset($error) ) {

	if (is_numeric($extravalue) && $post['postformat'] == 'V') {
		$vidurl = king_get_uploads($extravalue);
		$output .= '<video id="embed-video" class="video-js vjs-theme-forest" controls width="100%" height="100%" data-setup="{}" poster="'.qa_html($featured['furl']).'">
			<source src="'.qa_html($vidurl['furl']).'" type="video/mp4" />
			<div fallback>
				<p>This browser does not support the video element.</p>
			</div>
		</video>
		<a class="embedname" id="ename" target="_blank" href="'.qa_html($shareurl).'"><img src="'.get_avatar($post['avatarblobid'], '27', true).'" />'.qa_html($post['title']).'</a>';
}

} else {
	$output .= 'Can\'t play the video . Visit page <a href="'.qa_html($shareurl).'" > Link </a>';
}

$output .= '</div>';
$output .= '<script>

var player = videojs(document.querySelector(\'.video-js\'));
var b=document.getElementById("ename");
player.on(\'play\', function() {
  b.classList.add("hide");

});
player.on(\'pause\', function() {
  b.classList.remove("hide");

});

</script></body></html>';
echo $output;



