<?php
/*

File: king-include/king-page-updates.php
Description: Controller for page listing recent updates for a user

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
require_once QA_INCLUDE_DIR . 'king-app/format.php';
require_once QA_INCLUDE_DIR . 'king-app/users.php';
require_once QA_INCLUDE_DIR . 'king-app/q-list.php';
require_once QA_INCLUDE_DIR . 'king-db/metas.php';
//    Check that we're logged in

$userid = qa_get_logged_in_userid();

if ( ! isset( $userid ) ) {
	qa_redirect( 'login' );
}

//    Find out which updates to show

$by        = qa_get( 'by' );
$pagesize  = qa_opt( 'page_size_qs' );
$questions = array();
$start     = qa_get_start();

$sometitle = qa_lang_html( 'misc/nav_discover' );
$nonetitle = qa_lang_html( 'misc/no_updates_favorites' );

	switch ($by) {
		case 'cats':
			$ftitle=qa_lang_html( 'misc/following_cats' );
			break;
		case 'tags':
			$ftitle=qa_lang_html( 'misc/following_tags' );
			break;
		default:
			$ftitle=qa_lang_html( 'misc/recent_updates_favorites' );
			break;
	}


$html = '<div class="dashavatar">';
$html .= get_avatar( qa_get_logged_in_user_field( 'avatarblobid' ), 100 );
$html .= '</div>';
$html .= '<span><b>' . qa_get_logged_in_user_field( 'handle' ) . ' /</b> ' . $ftitle . '</span>';

if ( '' == $by ) {
	$users = qa_db_select_with_pending(
		qa_db_user_favorite_users_selectspec( $userid, '16' )
	);

	if ( count( $users ) ) {
		$html .= '<div class="discover-boxes">';

		foreach ( $users as $user ) {
			$html .= '<a href="' . qa_path_html( 'user/' . $user['handle'] ) . '" data-toggle="tooltip" data-placement="top" title="' . qa_html( $user['handle'] ) . '">' . qa_html( '@' . $user['handle'] ) . '</a>';
		}

		$html .= '</div>';
	}

	$questions = qa_db_single_select( qa_db_user_updates_selectspec( $userid, 222, $start ) );
} elseif ( 'cats' === $by ) {
	$type    = 'follow_cat';
	$query   = qa_db_usermeta_get( $userid, $type );
	$results = $query ? unserialize( $query ) : array();

	if ( $results ) {
		$html .= '<div class="discover-boxes">';

		foreach ( $results as $result ) {
			$cats = qa_db_single_select( qa_db_full_category_selectspec( $result, true ) );
			$html .= '<a href="' . qa_path_html( '' . implode( '/', array_reverse( explode( '/', $cats['backpath'] ) ) ) ) . '">' . $cats['title'] . '</a>';
		}

		$html .= '</div>';
		list($question1, $question2) = qa_db_select_with_pending(
			king_following_cats( $userid, 222, $start, $results ),
			king_following_cats2( $userid, 222, $start, $results )
		);
		$questions = qa_any_sort_and_dedupe(array_merge($question1, $question2));

	}
} elseif ( 'tags' === $by ) {
	$type    = 'follow_tag';
	$query   = qa_db_usermeta_get( $userid, $type );
	$results = $query ? unserialize( $query ) : array();

	if ( $results ) {
		$html .= '<div class="discover-boxes">';

		foreach ( $results as $result ) {
			$tag = qa_db_select_with_pending( get_tagname_byid( $result ) );
			$html .= '<a href="' . qa_path_html( 'tag/' . $tag['word'] ) . '">#' . qa_html( $tag['word'] ) . '</a>';
		}

		$html .= '</div>';

		$questions = qa_db_single_select( king_following_tags( $userid, 222, $start, $results ) );
	}
}

$count      = count( $questions );
$linkparams = array( 'by' => $by );
$qa_content = qa_q_list_page_content(
	$questions,
	$pagesize, // questions per page
	$start, // start offset
	$count, // total count (null to hide page links)
	$sometitle, // title if some questions
	$nonetitle, // title if no questions
	null, // categories for navigation
	null, // selected category id
	null, // show question counts in category navigation
	QA_ALLOW_UNINDEXED_QUERIES ? 'updates/' : null, // prefix for links in category navigation
	null, // prefix for RSS feed paths (null to hide)
	null, // suggest what to do next
	$linkparams, // extra parameters for page links
	null// category nav params
 );

if ( ! $count ) {
	$qa_content['custom'] = '<div class="nopost"><i class="far fa-frown-open fa-4x"></i> ' . qa_lang_html( 'main/no_unselected_qs_found' ) . '</div>';
}

$qa_content['navigation']['sub'] = subscriptions_nav( $by );

$qa_content['header'] = $html;
$qa_content['class']  = ' full-page';

return $qa_content;
