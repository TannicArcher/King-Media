<?php
class qa_html_theme extends qa_html_theme_base {

	public function body_footer()
	{
		if (isset($this->content['body_footer'])) {
			$this->output_raw($this->content['body_footer']);
		}
		$this->output( '<link rel="preconnect" href="https://fonts.googleapis.com">' );
		$this->output( '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' );
		$this->output( '<link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">' );
		$this->king_js_codes();
		
	}
	public function king_js() {
		$this->output( '<script src="' . $this->rooturl . 'js/main.js"></script>' );
		$this->output( '<script src="' . $this->rooturl . 'js/bootstrap.min.js"></script>' );

		if ( 'home' == $this->template || 'hot' == $this->template || 'search' == $this->template || 'updates' == $this->template || 'user-questions' == $this->template || 'favorites' == $this->template || 'qa' == $this->template || 'tag' == $this->template || 'type' == $this->template || 'reactions' == $this->template ) {
			$this->output( '<script src="' . $this->rooturl . 'js/jquery-ias.min.js"></script>' );
			$this->output( '<script src="' . $this->rooturl . 'js/masonry.pkgd.min.js"></script>' );
		}

	}

	public function body_content() {
		$this->body_prefix();
		$this->notices();
		$this->body_header();
		$this->header();
		$this->main_up();
		$this->output( '<DIV id="king-body-wrapper" class="king-body-in">' );
		$this->widgets( 'full', 'top' );
		$this->featured();
		$this->widgets( 'full', 'high' );
		$this->nav( 'sub' );
		$this->widgets( 'full', 'low' );
		$this->main();
		$this->output( '</DIV>' );
		$this->footer();
		$this->body_suffix();
	}

	public function body_header() {
		if ( isset( $this->content['body_header'] ) ) {
			$this->output( '<DIV class="ads">' );
			$this->output_raw( $this->content['body_header'] );
			$this->output( '</DIV>' );
		}
	}

	public function main() {
		$content = $this->content;
		$hidden = isset( $content['hidden'] ) ? ' king-main-hidden' : '';
		$class = isset( $content['class'] ) ? $content['class'] : ' one-page';
		$this->widgets( 'main', 'top' );
		$this->output( '<DIV CLASS="king-main' . $class . $hidden.'">' );
		$this->output( '<DIV CLASS="king-main-in">' );
		$this->widgets( 'main', 'high' );
		$this->main_parts( $content );
		$this->page_links();
		$this->widgets('main', 'low');
		$this->output( '</div> <!-- king-main-in -->' );
		if ( isset($content['sside']) ) {
			$this->sidepanel();
		}
		$this->output( '</DIV> <!-- king-main -->' );
		
		$this->suggest_next();
		$this->widgets('main', 'bottom');
	}


	public function main_up() {
		$content = $this->content;
		$q_view = isset($content['q_view']) ? $content['q_view'] : '';
		
		if ($q_view) {
			
			$text2 = $q_view['raw']['postformat'];
			$nsfw  = $q_view['raw']['nsfw'];
			if ( null !== $nsfw && ! qa_is_logged_in() ) {
				$this->output( '<DIV CLASS="king-video">' );
				$this->output( '<span class="king-nsfw-post"><p><i class="fas fa-mask fa-2x"></i></p>' . qa_lang_html( 'misc/nsfw_post' ) . '</span>' );
				$this->output( '</DIV>' );
			} elseif ( 'V' == $text2 ) {
				$this->output( '<DIV CLASS="king-video-in">' );
				$this->output( '<DIV CLASS="king-video">' );
				$this->q_view_extra( $q_view );
				$this->output( '</DIV>' );
				$this->output( '</DIV>' );
			} elseif ( 'music' == $text2 ) {
				$this->output( '<DIV CLASS="king-video-in">' );
				$this->output( '<DIV CLASS="king-video">' );
				$this->music_view( $q_view );
				$this->output( '</DIV>' );
				$this->output( '</DIV>' );
			} elseif ( 'I' == $text2 ) {
				$this->output( '<DIV CLASS="king-video-in">' );
				$this->output( '<DIV CLASS="king-video">' );
				$this->q_view_extra( $q_view );
				$this->output( '</DIV>' );
				$this->output( '</DIV>' );
			}

		}
	}

	public function q_view( $q_view ) {
		$pid   = $q_view['raw']['postid'];
		$text2 = $q_view['raw']['postformat'];
		$nsfw  = $q_view['raw']['nsfw'];

		if ( ! empty( $q_view ) ) {

			$this->viewtop();
			if ( null == $nsfw || qa_is_logged_in() ) {
				$this->output( '<DIV CLASS="king-q-view' . ( @$q_view['hidden'] ? ' king-q-view-hidden' : '' ) . rtrim( ' ' . @$q_view['classes'] ) . '"' . rtrim( ' ' . @$q_view['tags'] ) . '>' );
				$this->a_count( $q_view );
				$this->output( '<DIV CLASS="rightview">' );
				$this->page_title_error();
				$blockwordspreg = qa_get_block_words_preg();
				$this->output( '<div class="post-content">' . qa_block_words_replace( $q_view['raw']['pcontent'], $blockwordspreg ) . '</div>' );

				if ( 'poll' == $text2 ) {
					$this->get_poll( $pid );
				} elseif ( 'list' == $text2 ) {
					$this->get_list( $pid );
				} elseif ( 'trivia' == $text2 ) {
					$this->get_trivia( $pid );
				}

				$this->post_tags( $q_view, 'king-q-view' );
				$this->view_count( $q_view );
				$this->post_meta_when( $q_view, 'meta' );
				$this->output( '<div class="prev-next">' );
				$this->get_next_q();
				$this->get_prev_q();
				$this->output( '</div>' );
				$this->output( '</DIV>' );
				if ( qa_opt( 'show_ad_post_below' ) ) {
					$this->output( '<div class="ad-below">' );
					$this->output( '' . qa_opt( 'ad_post_below' ) . '' );
					$this->output( '</div>' );
				}
				$this->output( '</DIV> <!-- END king-q-view -->', '' );
			}
			$this->socialshare($q_view);
			$this->pboxes( $q_view );
			$this->maincom( $q_view );

		}
	}

	public function header() {
		$this->output( '<header CLASS="king-headerf">' );
		$this->output( '<DIV CLASS="king-header">' );
		$this->header_left();
		$this->header_middle();
		$this->header_right();
		$this->output( '</DIV>' );
		$this->leftmenu();
		$this->nav_user_search();
		$this->output( '</header>' );
		$this->h_title();

		if ( isset( $this->content['profile'] ) ) {
			$this->profile_page();
		}

		if ( isset( $this->content['error'] ) ) {
			$this->error( @$this->content['error'] );
		}
	}
	public function h_title() {
		if (isset( $this->content['header'] ) ) {
			$this->output( '<div class="head-title">' );
			$this->output( $this->content['header'] );
			$this->output( '</div>' );
		}
	}
	public function header_left() {
		$this->output( '<div class="header-left">' );
		$this->output( '<div class="king-left-toggle" data-toggle="dropdown" data-target=".leftmenu" aria-expanded="false" role="button"><span class="left-toggle-line"></span></div>' );
		
		$this->logo();
		$this->output( '</div>' );
	}

	public function header_middle() {
		$this->output( '<div class="header-middle">' );
		$this->nav( 'head' );
		$this->output( '<div class="menutoggle" data-toggle="dropdown" data-target=".king-mega-menu" aria-expanded="false"><i class="fas fa-angle-down"></i></div>' );
		$this->output( '<div class="king-mega-menu">' );
		if ( qa_using_categories() ) {
			$this->king_cats();
		}
		$this->nav('headmenu');
		$this->output('</div>');
		$this->output('</div>');
	}

	public function header_right() {
		$this->output( '<DIV CLASS="header-right">' );
		$this->output( '<ul>' );

		if ( ! qa_is_logged_in() ) {
			$this->output( '<li>' );
			$this->output( '<a class="reglink hreg" href="' . qa_path_html( 'register' ) . '">' . qa_lang_html( 'main/nav_register' ) . '</a>' );
			$this->output( '</li>' );
			$this->output( '<li>' );
			$this->output( '<div class="reglink" data-toggle="modal" data-target="#loginmodal" role="button" title="' . qa_lang_html( 'main/nav_login' ) . '"><i class="fa-solid fa-user"></i></div>' );
			$this->output( '</li>' );
		} else {
			$this->userpanel();
		}

		if (  ( qa_user_maximum_permit_error( 'permit_post_q' ) != 'level' ) ) {
			$this->kingsubmit();
		}

		$this->output( '<li class="search-button"><span data-toggle="dropdown" data-target=".king-search" aria-expanded="false" class="search-toggle"><i class="fas fa-search fa-lg"></i></span></li>' );
		$this->output( '</ul>' );
		$this->output( '</DIV>' );
	}

	public function leftmenu() {
		$this->output( '<div class="leftmenu kingscroll">' );
		$this->output( '<div class="leftmenu-left">' );
		$this->output( '<span>' );
		$this->output('<button type="button" class="king-left-close" data-dismiss="modal" aria-label="Close"></button>');
		if ( qa_is_logged_in() ) {
			if (qa_opt( 'allow_private_messages' )) {
				require_once QA_INCLUDE_DIR . 'king-db/metas.php';
				$mcount  = (INT) qa_db_usermeta_get( qa_get_logged_in_userid(), 'm_count' );
				$this->output( '<a class="leftmenu-lout" href="' . qa_path_html( 'messages' ) . '" data-toggle="tooltip" data-placement="right" title="' . qa_lang_html( 'main/nav_user_pms' ) . '">'.(($mcount > 0) ? '<span class="mcount">'.$mcount.'</span>' : '').'<i class="fa-regular fa-envelope"></i></a>' );
			}
			
			$this->output( '<a class="leftmenu-lout" href="' . qa_path_html( 'favorites' ) . '" data-toggle="tooltip" data-placement="right" title="' . qa_lang_html( 'main/nav_updates' ) . '"><i class="fa-solid fa-heart"></i></a>' );
			$this->output( '<a class="leftmenu-lout" href="' . qa_path_html( 'account' ) . '" data-toggle="tooltip" data-placement="right" title="' . qa_lang_html( 'main/nav_account' ) . '"><i class="fa-solid fa-gear"></i></a>' );
		}
		$this->output( '</span>' );
		$this->output( '<span>' );
		$this->output( '<input type="checkbox" id="king-night" class="hide" /><label for="king-night" class="king-nightb"><i class="fa-solid fa-sun"></i><i class="fa-solid fa-moon"></i></label>' );
		if ( qa_is_logged_in() ) {
			$this->output( '<a class="leftmenu-lout" href="' . qa_path_html( 'logout' ) . '" data-toggle="tooltip" data-placement="right" title="' . qa_lang_html( 'main/nav_logout' ) . '"><i class="fas fa-sign-out-alt"></i></a>' );
		}
		$this->output( '</span>' );
		$this->output( '</div>' );
		$this->nav_main_sub();
		$this->output( '</div>' );
	}

	public function nav_main_sub() {
		$this->output( '<DIV CLASS="king-nav-main">' );
		$this->nav( 'main' );
		$this->output( '</DIV>' );
	}

	public function profile_page() {
		$handle = qa_request_part( 1 );

		if ( ! strlen( $handle ) ) {
			$handle = qa_get_logged_in_handle();
		}

		$user = qa_db_select_with_pending(
			qa_db_user_account_selectspec( $handle, false )
		);

		$this->output( get_user_html( $user, '1200', 'king-profile', '140' ) );
	}

	public function userpanel() {


		$this->output( '<li>' );
		$this->output( '<div class="king-havatar" data-toggle="dropdown" data-target=".king-dropdown" aria-expanded="false" >' );
		$this->output( get_avatar( qa_get_logged_in_user_field('avatarblobid'), 40 ) );
		$this->output( '</div>' );
		$this->output( '<div class="king-dropdown">' );
		$this->output( '<a href="' . qa_path_html('user/'.qa_get_logged_in_user_field('handle')) . '" ><h3>' . qa_get_logged_in_user_field('handle') . '</h3></a>' );
		$this->output( '<span class="user-box-point"><strong>' . qa_html( number_format( qa_get_logged_in_user_field('points') ) ) . '</strong> ' . qa_lang_html( 'admin/points_title' ) . '</span>' );
		$this->nav( 'user' );
		$this->output( '</div>' );
		$this->output( '</li>' );
	}

	/**
	 * @param $q_items
	 */
	public function q_list_items( $q_items ) {
		$this->output( '<div class="container">' );
		$this->output( '<div class="grid-sizer"></div>' );

		foreach ( $q_items as $q_item ) {
			$this->q_list_item( $q_item );
		}

		$this->output( '</div>' );
	}

	/**
	 * @param $q_item
	 */
	public function q_list_item( $q_item ) {
		$format     = $q_item['raw']['postformat'];
		$postformat = '';
		$postc      = '';
		$shomag     = true;

		if ( 'V' == $format ) {
			$postformat = '<a class="king-post-format" href="' . qa_path_html( 'type' ) . '"><i class="fas fa-video"></i> ' . qa_lang_html( 'main/home_video' ) . '</a>';
			$postc      = ' king-class-video';
		} elseif ( 'I' == $format ) {
			$postformat = '<a class="king-post-format" href="' . qa_path_html( 'type', array( 'by' => 'images' ) ) . '"><i class="fas fa-image"></i> ' . qa_lang_html( 'main/home_image' ) . '</a>';
			$postc      = ' king-class-image';
		} elseif ( 'N' == $format ) {
			$postformat = '<a class="king-post-format" href="' . qa_path_html( 'type', array( 'by' => 'news' ) ) . '"><i class="fas fa-newspaper"></i> ' . qa_lang_html( 'main/home_news' ) . '</a>';
			$postc      = ' king-class-news';
		} elseif ( 'poll' == $format ) {
			$postformat = '<a class="king-post-format" href="' . qa_path_html( 'type', array( 'by' => 'poll' ) ) . '"><i class="fas fa-align-left"></i> ' . qa_lang_html( 'misc/king_poll' ) . '</a>';
			$postc      = ' king-class-poll';
		} elseif ( 'list' == $format ) {
			$postformat = '<a class="king-post-format" href="' . qa_path_html( 'type', array( 'by' => 'list' ) ) . '"><i class="fas fa-bars"></i> ' . qa_lang_html( 'misc/king_list' ) . '</a>';
			$postc      = ' king-class-list';
		} elseif ( 'trivia' == $format ) {
			$postformat = '<a class="king-post-format" href="' . qa_path_html( 'type', array( 'by' => 'trivia' ) ) . '"><i class="fas fa-times"></i> ' . qa_lang_html( 'misc/king_trivia' ) . '</a>';
			$postc      = ' king-class-trivia';
		} elseif ( 'music' == $format ) {
			$postformat = '<a class="king-post-format" href="' . qa_path_html( 'type', array( 'by' => 'music' ) ) . '"><i class="fas fa-headphones-alt"></i> ' . qa_lang_html( 'misc/king_music' ) . '</a>';
			$shomag     = false;
			$postc      = ' king-class-music';

			if ( $q_item['ext'] ) {
				$shomag = true;
			}
		}

		$this->output( '<div class="box king-q-list-item' . rtrim( ' ' . @$q_item['classes'] ) . '' . $postc . '" ' . @$q_item['tags'] . '>' );
		$this->output( '<div class="king-post-upbtn">' );
		if ( $shomag ) {
			$this->output( '<a href="' . $q_item['url'] . '" class="ajax-popup-link magnefic-button mgbutton" data-toggle="tooltip" data-placement="right" title="' . qa_lang_html( 'misc/king_qview' ) . '"><i class="fa-solid fa-chevron-up"></i></a>' );
		} else {
			$this->output( '<a href="' . $q_item['url'] . '" class="king-listen magnefic-button mgbutton" data-toggle="tooltip" data-placement="right" title="' . qa_lang_html( 'misc/king_listen' ) . '"><i class="fa-solid fa-headphones"></i></a>' );
		}
		$this->output( '<a href="' . $q_item['url'] . '" class="ajax-popup-share magnefic-button" data-toggle="tooltip" data-placement="right" title="' . qa_lang_html( 'misc/king_share' ) . '"><i class="fas fa-share-alt"></i></a>' );
		$this->output( '</div>' );
		$this->q_item_main( $q_item, $postformat );
		$this->output( '</div>' );
	}

	/**
	 * @param $q_item
	 */
	public function q_item_main( $q_item, $postformat = null ) {
		$this->output( '<div class="king-q-item-main">' );
		$this->q_item_content( $q_item );
		$this->output( '<DIV CLASS="king-post-content">' );
		$this->q_item_title( $q_item, $postformat );
		$this->post_metas( $q_item );
		$this->q_item_buttons( $q_item );
		$this->output( '</DIV>' );
		$this->output( '</div>' );
	}

	/**
	 * @param $q_item
	 */
	public function q_item_content( $q_item ) {
		$text = $q_item['raw']['content'];
		$nsfw = $q_item['raw']['nsfw'];

		if ( null !== $nsfw && ! qa_is_logged_in() ) {
			$this->output( '<a href="' . $q_item['url'] . '" class="item-a"><span class="king-nsfw-post"><p><i class="fas fa-mask fa-2x"></i></p>' . qa_lang_html( 'misc/nsfw_post' ) . '</span></a>' );
		} elseif ( ! empty( $text ) ) {
			$text2 = king_get_uploads( $text );
			$this->output( '<A class="item-a" HREF="' . $q_item['url'] . '">' );

			if ( $text2 ) {
				$this->output_raw( '<span class="post-featured-img"><img class="item-img king-lazy" width="' . $text2['width'] . '" height="' . $text2['height'] . '" data-king-img-src="' . $text2['furl'] . '" alt=""/></span>' );
			} else {
				$this->output_raw( '<span class="post-featured-img"><img class="item-img" data-king-img-src="' . $text . '" alt=""/></span>' );
			}

			$this->output( '</A>' );
		} else {
			$this->output( '<a href="' . $q_item['url'] . '" class="king-nothumb"></a>' );
		}
	}

	/**
	 * @param $q_item
	 */
	public function q_item_title( $q_item, $postformat=null ) {
		$this->output( '<DIV CLASS="king-q-item-title">' );
		$this->output( '<div class="king-title-up">' );
		$this->output( $postformat );
		$this->post_meta_where( $q_item, 'metah' );
		$this->output( '</div>' );
		$this->output( '<A HREF="' . $q_item['url'] . '"><h2>' . $q_item['title'] . '</h2></A>' );
		$this->output( '</DIV>' );
	}



	public function page_title_error() {
		$this->output( '<DIV CLASS="pheader">' );
		$this->output( '<H1>' );
		$this->title();
		$this->output( '</H1>' );
		$this->output( '</DIV>' );
	}

	public function get_prev_q() {
		$myurl       = $this->request;
		$myurlpieces = explode( "/", $myurl );
		$myurl       = $myurlpieces[0];

		$query_p = "SELECT *
				FROM ^posts
				WHERE postid < $myurl
				AND type='Q'
				ORDER BY postid DESC
				LIMIT 1";

		$prev_q = qa_db_query_sub( $query_p );

		while ( $prev_link = qa_db_read_one_assoc( $prev_q, true ) ) {
			$title = $prev_link['title'];
			$pid   = $prev_link['postid'];

			$this->output( '<A HREF="' . qa_q_path_html( $pid, $title ) . '" CLASS="king-prev-q">' . $title . ' <i class="fas fa-angle-right"></i></A>' );
		}
	}

	public function get_next_q() {
		$myurl       = $this->request;
		$myurlpieces = explode( "/", $myurl );
		$myurl       = $myurlpieces[0];

		$query_n = "SELECT *
				FROM ^posts
				WHERE postid > $myurl
				AND type='Q'
				ORDER BY postid ASC
				LIMIT 1";

		$next_q = qa_db_query_sub( $query_n );

		while ( $next_link = qa_db_read_one_assoc( $next_q, true ) ) {
			$title = $next_link['title'];
			$pid   = $next_link['postid'];

			$this->output( '<A HREF="' . qa_q_path_html( $pid, $title ) . '" CLASS="king-next-q"><i class="fas fa-angle-left"></i> ' . $title . '</A>' );
		}
	}
}
