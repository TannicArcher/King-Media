<?php

class king_ad {
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
			case 'activity':
			case 'qa':
			case 'hot':
			case 'type':
			case 'updates':
			case 'reactions':
			case 'categories':
			case 'question':
			case 'tag':
			case 'tags':
			case 'unanswered':
			case 'user':
			case 'users':
			case 'search':
			case 'custom':
			case 'ask':
			case 'video':
			case 'news':
			case 'list':
			case 'poll':
			case 'trivia':
			case 'music':
				$allow = true;
				break;
		}

		return $allow;
	}

	/**
	 * @param $region
	 */
	public function allow_region( $region ) {
		return in_array( $region, array( 'side', 'main', 'full' ) );
	}

	/**
	 * @param $wextra
	 * @return mixed
	 */
	public function widget_option( $wextra ) {
		$warray = ( null !== $wextra ) ? unserialize( $wextra ) : array();
		$number = isset( $warray['ad'] ) ? $warray['ad'] : '';
		$woptions = array(
			'tnumber' => array(
				'tags'  => 'name="wextra[ad]"',
				'label' => 'Ad Code:',
				'type'  => 'textarea',
				'value' => $number,
			) );

		return $woptions;
	}

	/**
	 * @param $region
	 * @param $place
	 * @param $themeobject
	 * @param $template
	 * @param $request
	 * @param $qa_content
	 * @param $wtitle
	 * @param null $wextra
	 */
	public function output_widget( $region, $place, $themeobject, $template, $request, $qa_content, $wtitle = null, $wextra = null ) {
		require_once QA_INCLUDE_DIR . 'king-db/selects.php';
		$warray      = ( null !== $wextra ) ? unserialize( $wextra ) : array();
		$ad      = isset( $warray['ad'] ) ? $warray['ad'] : '';

		$title = isset( $wtitle ) ? $wtitle : '';

		$themeobject->output( '<div class="ad-widget king-widget-wb">' );
		$themeobject->output(
			'<div class="widget-title">',
			$title,
			'</div>'
		);
		$themeobject->output( $ad );

		$themeobject->output( '</div>' );
	}
}

/*
Omit PHP closing tag to help avoid accidental output
 */