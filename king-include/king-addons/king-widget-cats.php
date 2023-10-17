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

class king_cats {
	/**
	 * @var mixed
	 */
	public $voteformcode;

	/**
	 * @param $template
	 * @return mixed
	 */
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

	/**
	 * @param $region
	 */
	public function allow_region( $region ) {
		return ( 'side' == $region );
	}

	/**
	 * @param $region
	 * @param $place
	 * @param $themeobject
	 * @param $template
	 * @param $request
	 * @param $qa_content
	 */
	public function output_widget( $region, $place, $themeobject, $template, $request, $qa_content, $wtitle=null, $wextra=null ) {

		$title = isset($wtitle) ? $wtitle : qa_lang_html('main/nav_categories');
		if ( qa_using_categories() ) {
			$themeobject->output('<div class="king-widget-wb">');
			$themeobject->output(
				'<div class="widget-title">',
				$title,
				'</div>'
			);

			$themeobject->output( '<div class="king-cat-side">' );
			$themeobject->nav( 'cat', 1 );
			$themeobject->output( '</div>' );
			$themeobject->output( '</div>' );
		}
	}
}

/*
Omit PHP closing tag to help avoid accidental output
 */