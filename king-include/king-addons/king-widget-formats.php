<?php

/*
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

class king_postformats {

	public $voteformcode;


	public function allow_template( $template ) {
		$allow = false;

		switch ( $template ) {
			case 'home':
			case 'question':
			case 'custom':
				$allow = true;
				break;
		}

		return $allow;
	}


	public function allow_region( $region ) {
		return in_array( $region, array( 'side', 'main' ) );
	}
	public function widget_option($wextra) {
		$warray = ( null !== $wextra ) ? unserialize($wextra) : array();
		$number = isset($warray['number']) ? $warray['number'] : '6';
		$select = isset($warray['select']) ? $warray['select'] : 'N';
		$positionoptions['N']=qa_lang_html( 'main/home_news' );
		$positionoptions['V']=qa_lang_html( 'main/home_video' );
		$positionoptions['I']=qa_lang_html( 'main/home_image' );
		$positionoptions['poll']=qa_lang_html( 'misc/king_poll' );
		$positionoptions['list']=qa_lang_html( 'misc/king_list' );
		$positionoptions['trivia']=qa_lang_html( 'misc/king_trivia' );
		$positionoptions['music']=qa_lang_html( 'misc/king_music' );
		$woptions = array(
			'asdsd' => array(
				'tags' => 'name="wextra[select]"',
				'label' => qa_lang_html('misc/s_format'),
				'type' => 'select',
				'options' => $positionoptions,
				'value' => $positionoptions[$select],
			),
			'sdsadsdsd' => array(
				'tags' => 'name="wextra[number]"',
				'label' => qa_lang_html('misc/post_number'),
				'type' => 'text',
				'value' => $number,
			));

		return $woptions;
	}

	public function output_widget( $region, $place, $themeobject, $template, $request, $qa_content, $wtitle = null, $wextra = null ) {
		require_once QA_INCLUDE_DIR . 'king-db/selects.php';


		$userid   = qa_get_logged_in_userid();
		$cookieid = qa_cookie_get();

		$warray = ( null !== $wextra ) ? unserialize($wextra) : array();
		$selectby = $warray['select'] ? $warray['select'] : 'N';
		$number = isset($warray['number']) ? $warray['number'] : '6';

		$posts =  qa_db_single_select( qa_db_unanswered_qs_selectspec($userid, $selectby, 0, null, false, false, $number));

		$title = isset( $wtitle ) ? $wtitle : qa_lang_html( 'main/related_qs_title' );

		if ( 'side' == $region ) {
			$themeobject->output(
				'<DIV CLASS="ilgilit">',
				'<div class="widget-title">',
				$title,
				'</div>'
			);
		} elseif ( 'main' == $region ) {
			$themeobject->output(
				'<DIV CLASS="ilgilit under-content">',
				'<div class="widget-title">',
				$title,
				'</div>'
			);
		}

		$themeobject->output( '<div CLASS="ilgili">' );

		foreach ( $posts as $post ) {
			$themeobject->output( get_simple_post( $post ) );
		}

		$themeobject->output( '</div>' );
		$themeobject->output( '</DIV>' );
	}
}

/*
Omit PHP closing tag to help avoid accidental output
 */