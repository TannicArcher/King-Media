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

$pid = qa_post_text('pid');
$cc  = qa_post_text('cc');
$ttl  = qa_post_text('ttl');

$query  = qa_db_read_one_value(qa_db_query_sub('SELECT content FROM ^poll WHERE postid=$ AND type=$ ', $pid, 'rtrivia'));
$trs = unserialize($query);
if ($trs) {
	$rate    = round( 100 * $cc / $ttl );
	$out = '<div class="tresult">';
	foreach ($trs as $tr) {
		$high   = $tr['max'];
		$low    = $tr['min'];
		if ( $rate >= $low && $rate <= $high ) {
			$out .= '<h3>'.$tr['title'].'</h3>';
			$img = king_get_uploads($tr['img']);
			$out .='<img class="poll-img" src="' . $img['furl'] . '" alt=""/>';
			$out .= '<span>'.$tr['desc'].'</span>';
		}
		
	}
	$text = 'I got '.$cc.' out of '.$ttl.' right! Do you wanna try ?';
	$title  = qa_db_read_one_value(qa_db_query_sub('SELECT title FROM ^posts WHERE postid=$', $pid));
	$out .= '<div class="quiz-share"><h5>'.qa_lang_html('misc/tshare').'</h5><span class="qresult-share">';
	$out .= '<a class="post-share share-fb" href="#" onclick="window.open(\'https://www.facebook.com/sharer/sharer.php?u=' .  qa_q_path($pid, $title). '&quote=' . $text . '\',\'facebook-share-dialog\',\'width=626,height=436\');return false;" target="_blank" rel="nofollow"><i class="fab fa-facebook-square"></i></i></a>';
	$out .= '<a class="social-icon share-tw" href="#" onclick="window.open(\'http://twitter.com/share?text=' . $text . '&amp;url=' . qa_q_path($pid, $title) . '\',\'twitter-share-dialog\',\'width=626,height=436\');return false;" rel="nofollow" target="_blank"><i class="fab fa-twitter"></i></a>';
	$out .= '</span>';
	$out .= '</div>';
	$out .= '</div>';
	
	echo "QA_AJAX_RESPONSE\n1\n";

	echo $out."\n";

}
