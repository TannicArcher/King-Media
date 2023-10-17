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

$pid = qa_post_text( 'pid' );
$rid = qa_post_text( 'rid' );

if ( qa_is_logged_in() ) {
	$userid = qa_get_logged_in_userid();
} else {
	$userid = qa_remote_ip_address();
}

$slct = 'reac_' . $rid;
$query  = qa_db_postmeta_get( $pid, $slct );
$result = unserialize( $query );


$total  = qa_db_postmeta_get( $pid, 'reactotal' );

if ( is_array( $result ) && in_array( $userid, $result ) ) {
	echo "QA_AJAX_RESPONSE\n0\n";
} else {
	if ( count( $result ) !== 0 ) {
		$king_voters = $result;
	}

	if ( ! is_array( $king_voters ) ) {
		$king_voters = array();
	}

	if ( ! in_array( $userid, $king_voters ) ) {
		$king_voters[] = $userid;
		$king_voters2  = serialize( $king_voters );
	}
	$ttotal = $total+1;
	qa_db_postmeta_set( $pid, 'reactotal', $ttotal );
	qa_db_postmeta_set( $pid, $slct, $king_voters2 );

	echo "QA_AJAX_RESPONSE\n1\n";

	echo $ttotal."\n";
}
