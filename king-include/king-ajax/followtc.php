<?php
/*

File: king-include/king-ajax-click-wall.php
Description: Server-side response to Ajax single clicks on wall posts

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

require_once QA_INCLUDE_DIR . 'king-app/users.php';
require_once QA_INCLUDE_DIR . 'king-app/limits.php';
require_once QA_INCLUDE_DIR . 'king-db/selects.php';
require_once QA_INCLUDE_DIR . 'king-db/metas.php';

$id   = qa_post_text( 'id' );
$tt   = qa_post_text( 'type' );
$type = 'follow_' . $tt;

if ( qa_is_logged_in() ) {
	$userid = qa_get_logged_in_userid();

	$query  = qa_db_usermeta_get( $userid, $type );
	$result = $query ? unserialize( $query ) : '';

	if ( is_array( $result ) && in_array( $id, $result ) ) {
		if ( $result ) {
			$uid_key = array_search( $id, $result );
			unset( $result[$uid_key] );
			$arr = serialize( $result );
			qa_db_usermeta_set( $userid, $type, $arr );

			$text = qa_lang_html('main/nav_follow');

			echo "QA_AJAX_RESPONSE\n0\n".$text;

		}
	} else {
		if ( count( $result ) !== 0 ) {
			$post_users = $result;
		}

		if ( ! is_array( $post_users ) ) {
			$post_users = array();
		}

		if ( ! in_array( $id, $post_users ) ) {
			$post_users[] = $id;
			$arr          = serialize( $post_users );
		}

		qa_db_usermeta_set( $userid, $type, $arr );

		$text = qa_lang_html('main/nav_unfollow');

		echo "QA_AJAX_RESPONSE\n1\n".$text;

	}


}
