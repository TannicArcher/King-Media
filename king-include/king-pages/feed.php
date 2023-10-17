<?php
/*

File: king-include/king-page/feed.php
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

if ( ! defined( 'QA_VERSION' ) ) {
	// don't allow this page to be requested directly from browser
	header( 'Location: ../' );
	exit;
}

require_once QA_INCLUDE_DIR . 'king-db/selects.php';
require_once QA_INCLUDE_DIR . 'king-db/metas.php';
require_once QA_INCLUDE_DIR . 'king-app-video.php';

$qa_content          = qa_content_prepare();
$qa_content['title'] = qa_lang_html( 'admin/feeds_title' );
$html                = '<div class="rssfeed">';

if ( qa_opt( 'feed_for_questions' ) ) {
	$html .= '<a href="' . qa_path_html( qa_feed_request( 'home' ) ) . '" target="_blank"><i class="fa-solid fa-rss"></i> ' . qa_lang_html( 'main/recent_qs_as_title' ) . '</a>';
}

if ( qa_opt( 'feed_for_hot' ) ) {
	$html .= '<a href="' . qa_path_html( qa_feed_request( 'hot' ) ) . '" target="_blank"><i class="fa-solid fa-rss"></i> ' . qa_lang_html( 'main/hot_qs_title' ) . '</a>';
}

if ( qa_opt( 'feed_per_category' ) ) {
	$categories = qa_db_single_select( qa_db_category_nav_selectspec( null, true ) );

	foreach ( $categories as $key => $category ) {
		$html .= '<a href="' . qa_path_html( qa_feed_request( 'qa/' . $category['tags'] ) ) . '" target="_blank"><i class="fa-solid fa-rss"></i> ' . $category['title'] . '</a>';
	}
}
$html .= '</div>';
$qa_content['custom'] = $html;

return $qa_content;

/*
Omit PHP closing tag to help avoid accidental output
 */