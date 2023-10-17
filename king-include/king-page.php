<?php
/*

File: king-include/king-page.php
Description: Routing and utility functions for page requests

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

require_once QA_INCLUDE_DIR . 'king-app/cookies.php';
require_once QA_INCLUDE_DIR . 'king-app/format.php';
require_once QA_INCLUDE_DIR . 'king-app/users.php';
require_once QA_INCLUDE_DIR . 'king-app/options.php';
require_once QA_INCLUDE_DIR . 'king-db/selects.php';

//	Functions which are called at the bottom of this file

/**
 * @param $type
 * @param $errno
 * @param null $error
 * @param null $query
 */
function qa_page_db_fail_handler( $type, $errno = null, $error = null, $query = null )
/*
Standard database failure handler function which bring up the install/repair/upgrade page
 */
{
	if ( qa_to_override( __FUNCTION__ ) ) {
		$args = func_get_args();

		return qa_call_override( __FUNCTION__, $args );}

	$pass_failure_type  = $type;
	$pass_failure_errno = $errno;
	$pass_failure_error = $error;
	$pass_failure_query = $query;

	require_once QA_INCLUDE_DIR . 'king-install.php';

	qa_exit( 'error' );
}

function qa_page_queue_pending()
/*
Queue any pending requests which are required independent of which page will be shown
 */
{
	if ( qa_to_override( __FUNCTION__ ) ) {
		$args = func_get_args();

		return qa_call_override( __FUNCTION__, $args );}

	qa_preload_options();
	$loginuserid = qa_get_logged_in_userid();

	if ( isset( $loginuserid ) ) {
		if ( ! QA_FINAL_EXTERNAL_USERS ) {
			qa_db_queue_pending_select( 'loggedinuser', qa_db_user_account_selectspec( $loginuserid, true ) );
		}

		qa_db_queue_pending_select( 'notices', qa_db_user_notices_selectspec( $loginuserid ) );
		qa_db_queue_pending_select( 'favoritenonqs', qa_db_user_favorite_non_qs_selectspec( $loginuserid ) );
		qa_db_queue_pending_select( 'userlimits', qa_db_user_limits_selectspec( $loginuserid ) );
		qa_db_queue_pending_select( 'userlevels', qa_db_user_levels_selectspec( $loginuserid, true ) );
	}

	qa_db_queue_pending_select( 'iplimits', qa_db_ip_limits_selectspec( qa_remote_ip_address() ) );
	qa_db_queue_pending_select( 'navpages', qa_db_pages_selectspec( array( 'B', 'M', 'O', 'F', 'H', 'G' ) ) );
	qa_db_queue_pending_select( 'widgets', qa_db_widgets_selectspec() );
}

function qa_load_state()
/*
Check the page state parameter and then remove it from the $_GET array
 */
{
	global $qa_state;

	$qa_state = qa_get( 'state' );
	unset( $_GET['state'] ); // to prevent being passed through on forms
}

function qa_check_login_modules()
/*
If no user is logged in, call through to the login modules to see if they want to log someone in
 */
{
	if (  ( ! QA_FINAL_EXTERNAL_USERS ) && ! qa_is_logged_in() ) {
		$loginmodules = qa_load_modules_with( 'login', 'check_login' );

		foreach ( $loginmodules as $loginmodule ) {
			$loginmodule->check_login();

			if ( qa_is_logged_in() ) // stop and reload page if it worked
			{
				qa_redirect( qa_request(), $_GET );
			}
		}
	}
}

function qa_check_page_clicks()
/*
React to any of the common buttons on a page for voting, favorites and closing a notice
If the user has Javascript on, these should come through Ajax rather than here.
 */
{
	if ( qa_to_override( __FUNCTION__ ) ) {
		$args = func_get_args();

		return qa_call_override( __FUNCTION__, $args );}

	global $qa_page_error_html;

	if ( qa_is_http_post() ) {
		foreach ( $_POST as $field => $value ) {
			if ( strpos( $field, 'vote_' ) === 0 ) {
				// voting...
				@list( $dummy, $postid, $vote, $anchor ) = explode( '_', $field );

				if ( isset( $postid ) && isset( $vote ) ) {
					if ( ! qa_check_form_security_code( 'vote', qa_post_text( 'code' ) ) ) {
						$qa_page_error_html = qa_lang_html( 'misc/form_security_again' );
					} else {
						require_once QA_INCLUDE_DIR . 'king-app/votes.php';
						require_once QA_INCLUDE_DIR . 'king-db/selects.php';

						$userid = qa_get_logged_in_userid();

						$post               = qa_db_select_with_pending( qa_db_full_post_selectspec( $userid, $postid ) );
						$qa_page_error_html = qa_vote_error_html( $post, $vote, $userid, qa_request() );

						if ( ! $qa_page_error_html ) {
							qa_vote_set( $post, $userid, qa_get_logged_in_handle(), qa_cookie_get(), $vote );
							qa_redirect( qa_request(), $_GET, null, null, $anchor );
						}

						break;
					}
				}
			} elseif ( strpos( $field, 'favorite_' ) === 0 ) {
				// favorites...
				@list( $dummy, $entitytype, $entityid, $favorite ) = explode( '_', $field );

				if ( isset( $entitytype ) && isset( $entityid ) && isset( $favorite ) ) {
					if ( ! qa_check_form_security_code( 'favorite-' . $entitytype . '-' . $entityid, qa_post_text( 'code' ) ) ) {
						$qa_page_error_html = qa_lang_html( 'misc/form_security_again' );
					} else {
						require_once QA_INCLUDE_DIR . 'king-app/favorites.php';

						qa_user_favorite_set( qa_get_logged_in_userid(), qa_get_logged_in_handle(), qa_cookie_get(), $entitytype, $entityid, $favorite );
						qa_redirect( qa_request(), $_GET );
					}
				}
			} elseif ( strpos( $field, 'notice_' ) === 0 ) {
				// notices...
				@list( $dummy, $noticeid ) = explode( '_', $field );

				if ( isset( $noticeid ) ) {
					if ( ! qa_check_form_security_code( 'notice-' . $noticeid, qa_post_text( 'code' ) ) ) {
						$qa_page_error_html = qa_lang_html( 'misc/form_security_again' );
					} else {
						if ( 'visitor' == $noticeid ) {
							setcookie( 'qa_noticed', 1, time() + 86400 * 3650, '/', QA_COOKIE_DOMAIN );
						} elseif ( 'welcome' == $noticeid ) {
							require_once QA_INCLUDE_DIR . 'king-db/users.php';
							qa_db_user_set_flag( qa_get_logged_in_userid(), QA_USER_FLAGS_WELCOME_NOTICE, false );
						} else {
							require_once QA_INCLUDE_DIR . 'king-db/notices.php';
							qa_db_usernotice_delete( qa_get_logged_in_userid(), $noticeid );
						}

						qa_redirect( qa_request(), $_GET );
					}
				}
			}
		}
	}
}

/**
 *	Run the appropriate king-page-*.php file for this request and return back the $qa_content it passed
 */
function qa_get_request_content() {
	if ( qa_to_override( __FUNCTION__ ) ) {
		$args = func_get_args();

		return qa_call_override( __FUNCTION__, $args );}

	$requestlower = strtolower( qa_request() );
	$requestparts = qa_request_parts();
	$firstlower   = strtolower( $requestparts[0] );
	$routing      = qa_page_routing();

	if ( isset( $routing[$requestlower] ) ) {
		qa_set_template( $firstlower );
		$qa_content = require QA_INCLUDE_DIR . $routing[$requestlower];
	} elseif ( isset( $routing[$firstlower . '/'] ) ) {
		qa_set_template( $firstlower );
		$qa_content = require QA_INCLUDE_DIR . $routing[$firstlower . '/'];
	} elseif ( qa_opt( 'enable_amp' ) && isset( $requestparts[2] ) &&  $requestparts[2]  == 'amp' ) {
		qa_set_template( 'tags' );
		$qa_content = require QA_INCLUDE_DIR . 'king-pages/amp.php';
	} elseif ( isset( $requestparts[2] ) && $requestparts[2] == 'embed' ) {
		qa_set_template( 'tags' );
		$qa_content = require QA_INCLUDE_DIR . 'king-pages/embed.php';
	} elseif ( is_numeric( $requestparts[0] ) ) {
		qa_set_template( 'question' );
		$qa_content = require QA_INCLUDE_DIR . 'king-pages/question.php';
	} else {
		qa_set_template( strlen( $firstlower ) ? $firstlower : 'qa' ); // will be changed later
		$qa_content = require QA_INCLUDE_DIR . 'king-pages/default.php'; // handles many other pages, including custom pages and page modules
	}

	if ( 'admin' == $firstlower ) {
		$_COOKIE['qa_admin_last'] = $requestlower; // for navigation tab now...
		setcookie( 'qa_admin_last', $_COOKIE['qa_admin_last'], 0, '/', QA_COOKIE_DOMAIN ); // ...and in future
	}

	if ( isset( $qa_content ) ) {
		qa_set_form_security_key();
	}

	return $qa_content;
}

/**
 *	Output the $qa_content via the theme class after doing some pre-processing, mainly relating to Javascript
 */
function qa_output_content( $qa_content ) {
	if ( qa_to_override( __FUNCTION__ ) ) {
		$args = func_get_args();

		return qa_call_override( __FUNCTION__, $args );}

	global $qa_template;

	$requestlower = strtolower( qa_request() );

	//	Set appropriate selected flags for navigation (not done in qa_content_prepare() since it also applies to sub-navigation)

	foreach ( $qa_content['navigation'] as $navtype => $navigation ) {
		if ( ! is_array( $navigation ) || 'cat' == $navtype ) {
			continue;
		}

		foreach ( $navigation as $navprefix => $navlink ) {
			$selected = &$qa_content['navigation'][$navtype][$navprefix]['selected'];

			if ( isset( $navlink['selected_on'] ) ) {
				// match specified paths

				foreach ( $navlink['selected_on'] as $path ) {
					if ( strpos( $requestlower . '$', $path ) === 0 ) {
						$selected = true;
					}
				}
			} elseif ( $requestlower === $navprefix || $requestlower . '$' === $navprefix ) {
				// exact match for array key
				$selected = true;
			}
		}
	}

	//	Slide down notifications

	if ( ! empty( $qa_content['notices'] ) ) {
		foreach ( $qa_content['notices'] as $notice ) {
			$qa_content['script_onloads'][] = array(
				"qa_reveal(document.getElementById(" . qa_js( $notice['id'] ) . "), 'notice');",
			);
		}
	}

	//	Handle maintenance mode

	if ( qa_opt( 'site_maintenance' ) && ( 'login' != $requestlower ) ) {
		if ( qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN ) {
			if ( ! isset( $qa_content['error'] ) ) {
				$qa_content['error'] = strtr( qa_lang_html( 'admin/maintenance_admin_only' ), array(
					'^1' => '<a href="' . qa_path_html( 'admin/general' ) . '">',
					'^2' => '</a>',
				) );
			}
		} else {
			$qa_content          = qa_content_prepare();
			$qa_content['error'] = qa_lang_html( 'misc/site_in_maintenance' );
		}
	}

	//	Handle new users who must confirm their email now, or must be approved before continuing

	$userid = qa_get_logged_in_userid();

	if ( isset( $userid ) && ( 'confirm' != $requestlower ) && ( 'account' != $requestlower ) ) {
		$flags = qa_get_logged_in_flags();

		if (  ( $flags & QA_USER_FLAGS_MUST_CONFIRM ) && ( ! ( $flags & QA_USER_FLAGS_EMAIL_CONFIRMED ) ) && qa_opt( 'confirm_user_emails' ) ) {
			$qa_content          = qa_content_prepare();
			$qa_content['title'] = qa_lang_html( 'users/confirm_title' );
			$qa_content['error'] = strtr( qa_lang_html( 'users/confirm_required' ), array(
				'^1' => '<a href="' . qa_path_html( 'confirm' ) . '">',
				'^2' => '</a>',
			) );
		} elseif (  ( $flags & QA_USER_FLAGS_MUST_APPROVE ) && ( qa_get_logged_in_level() < QA_USER_LEVEL_APPROVED ) && qa_opt( 'moderate_users' ) ) {
			$qa_content          = qa_content_prepare();
			$qa_content['title'] = qa_lang_html( 'users/approve_title' );
			$qa_content['error'] = strtr( qa_lang_html( 'users/approve_required' ), array(
				'^1' => '<a href="' . qa_path_html( 'account' ) . '">',
				'^2' => '</a>',
			) );
		}
	}

	//	Combine various Javascript elements in $qa_content into single array for theme layer

	$script = array( '<script>' );

	if ( isset( $qa_content['script_var'] ) ) {
		foreach ( $qa_content['script_var'] as $var => $value ) {
			$script[] = 'var ' . $var . ' = ' . qa_js( $value ) . ';';
		}
	}

	if ( isset( $qa_content['script_lines'] ) ) {
		foreach ( $qa_content['script_lines'] as $scriptlines ) {
			$script[] = '';
			$script   = array_merge( $script, $scriptlines );
		}
	}

	if ( isset( $qa_content['focusid'] ) ) {
		$qa_content['script_onloads'][] = array(
			"var elem = document.getElementById(" . qa_js( $qa_content['focusid'] ) . ");",
			"if (elem) {",
			"\telem.select();",
			"\telem.focus();",
			"}",
		);
	}

	if ( isset( $qa_content['script_onloads'] ) ) {
		array_push( $script,
			'',
			'var qa_oldonload = window.onload;',
			'window.onload = function() {',
			"\tif (typeof qa_oldonload == 'function')",
			"\t\tqa_oldonload();"
		);

		foreach ( $qa_content['script_onloads'] as $scriptonload ) {
			$script[] = "\t";

			foreach ( (array) $scriptonload as $scriptline ) {
				$script[] = "\t" . $scriptline;
			}
		}

		$script[] = '};';
	}

	$script[] = '</script>';

	if ( isset( $qa_content['script_rel'] ) ) {
		$uniquerel = array_unique( $qa_content['script_rel'] ); // remove any duplicates

		foreach ( $uniquerel as $script_rel ) {
			$script[] = '<script src="' . qa_html( qa_path_to_root() . $script_rel ) . '"></script>';
		}
	}

	if ( isset( $qa_content['script_src'] ) ) {
		$uniquesrc = array_unique( $qa_content['script_src'] ); // remove any duplicates

		foreach ( $uniquesrc as $script_src ) {
			$script[] = '<script src="' . qa_html( $script_src ) . '"></script>';
		}
	}

	$qa_content['script'] = $script;

	//	Load the appropriate theme class and output the page

	$tmpl       = substr( $qa_template, 0, 7 ) == 'custom-' ? 'custom' : $qa_template;
	$themeclass = qa_load_theme_class( qa_get_site_theme(), $tmpl, $qa_content, qa_request() );
	$themeclass->initialize();

	header( 'Content-type: ' . $qa_content['content_type'] );

	$themeclass->doctype();
	$themeclass->html();

}

/**
 * @param $qa_content
 */
function qa_do_content_stats( $qa_content )
/*
Update any statistics required by the fields in $qa_content, and return true if something was done
 */
{
	if ( isset( $qa_content['inc_views_postid'] ) ) {
		require_once QA_INCLUDE_DIR . 'king-db/hotness.php';
		qa_db_hotness_update( $qa_content['inc_views_postid'], null, true );

		return true;
	}

	return false;
}

//	Other functions which might be called from anywhere

function qa_page_routing()
/*
Return an array of the default KINGMEDIA requests and which king-page-*.php file implements them
If the key of an element ends in /, it should be used for any request with that key as its prefix
 */
{
	if ( qa_to_override( __FUNCTION__ ) ) {
		$args = func_get_args();

		return qa_call_override( __FUNCTION__, $args );}

	return array(
		'account'             => 'king-pages/account.php',
		'activity/'           => 'king-pages/activity.php',
		'admin/'              => 'king-pages/admin/admin-default.php',
		'admin/approve'       => 'king-pages/admin/admin-approve.php',
		'admin/categories'    => 'king-pages/admin/admin-categories.php',
		'admin/flagged'       => 'king-pages/admin/admin-flagged.php',
		'admin/hidden'        => 'king-pages/admin/admin-hidden.php',
		'admin/layoutwidgets' => 'king-pages/admin/admin-widgets.php',
		'admin/moderate'      => 'king-pages/admin/admin-moderate.php',
		'admin/pages'         => 'king-pages/admin/admin-pages.php',
		'admin/plugins'       => 'king-pages/admin/admin-plugins.php',
		'admin/points'        => 'king-pages/admin/admin-points.php',
		'admin/recalc'        => 'king-pages/admin/admin-recalc.php',
		'admin/stats'         => 'king-pages/admin/admin-stats.php',
		'admin/userfields'    => 'king-pages/admin/admin-userfields.php',
		'admin/usertitles'    => 'king-pages/admin/admin-usertitles.php',
		'admin/editusers'     => 'king-pages/admin/admin-users.php',
		'admin/editposts'     => 'king-pages/admin/admin-posts.php',
		'admin/king'          => 'king-pages/admin/admin-king.php',
		'feed'                 => 'king-pages/feed.php',
		'ask'                 => 'king-pages/ask.php',
		'news'                => 'king-pages/news.php',
		'poll'                => 'king-pages/poll.php',
		'trivia'              => 'king-pages/trivia.php',
		'list'                => 'king-pages/list.php',
		'video'               => 'king-pages/video.php',
		'music'               => 'king-pages/music.php',
		'edit'                => 'king-pages/edit.php',
		'categories/'         => 'king-pages/categories.php',
		'comments/'           => 'king-pages/comments.php',
		'confirm'             => 'king-pages/confirm.php',
		'favorites'           => 'king-pages/favorites.php',
		'feedback'            => 'king-pages/feedback.php',
		'forgot'              => 'king-pages/forgot.php',
		'hot/'                => 'king-pages/hot.php',
		'ip/'                 => 'king-pages/ip.php',
		'login'               => 'king-pages/login.php',
		'logout'              => 'king-pages/logout.php',
		'messages/'           => 'king-pages/messages.php',
		'message/'            => 'king-pages/message.php',
		'home/'               => 'king-pages/questions.php',
		'register'            => 'king-pages/register.php',
		'reset'               => 'king-pages/reset.php',
		'search'              => 'king-pages/search.php',
		'tag/'                => 'king-pages/tag.php',
		'tags'                => 'king-pages/tags.php',
		'type/'               => 'king-pages/type.php',
		'reactions/'          => 'king-pages/reactions.php',
		'shorts/'             => 'king-pages/shorts.php',
		'unsubscribe'         => 'king-pages/unsubscribe.php',
		'updates'             => 'king-pages/updates.php',
		'user/'               => 'king-pages/user.php',
		'users'               => 'king-pages/users.php',
		'users/blocked'       => 'king-pages/users-blocked.php',
		'users/special'       => 'king-pages/users-special.php',
	);
}

/**
 * @param $template
 */
function qa_set_template( $template )
/*
Sets the template which should be passed to the theme class, telling it which type of page it's displaying
 */
{
	global $qa_template;
	$qa_template = $template;
}

/**
 * @param $voting
 * @param false $categoryids
 * @return mixed
 */
function qa_content_prepare( $voting = false, $categoryids = null )
/*
Start preparing theme content in global $qa_content variable, with or without $voting support,
in the context of the categories in $categoryids (if not null)
 */
{
	if ( qa_to_override( __FUNCTION__ ) ) {
		$args = func_get_args();

		return qa_call_override( __FUNCTION__, $args );}

	global $qa_template, $qa_page_error_html;

	if ( QA_DEBUG_PERFORMANCE ) {
		global $qa_usage;
		$qa_usage->mark( 'control' );
	}

	$request      = qa_request();
	$requestlower = qa_request();
	$navpages     = qa_db_get_pending_result( 'navpages' );
	$widgets      = qa_db_get_pending_result( 'widgets' );

	if ( ! is_array( $categoryids ) ) {
		// accept old-style parameter
		$categoryids = array( $categoryids );
	}

	$lastcategoryid = count( $categoryids ) > 0 ? end( $categoryids ) : null;
	$charset        = 'utf-8';

	$qa_content = array(
		'content_type' => 'text/html; charset=' . $charset,
		'charset'      => $charset,

		'direction'    => qa_opt( 'site_text_direction' ),

		'site_title'   => qa_html( qa_opt( 'site_title' ) ),

		'head_lines'   => array(),

		'navigation'   => array(
			'user'   => array(),

			'main'   => array(),

			'footer' => array(
				'feedback' => array(
					'url'   => qa_path_html( 'feedback' ),
					'label' => qa_lang_html( 'main/nav_feedback' ),
				),
			),

		),

		'sidebar'      => qa_opt( 'show_custom_sidebar' ) ? qa_opt( 'custom_sidebar' ) : null,

		'sidepanel'    => qa_opt( 'show_custom_sidepanel' ) ? qa_opt( 'custom_sidepanel' ) : null,

		'widgets'      => array(),
	);

	// add meta description if we're on the home page

	if ( '' === $request || array_search( '', qa_get_request_map() ) === $request ) {
		$qa_content['description'] = qa_html( qa_opt( 'home_description' ) );
	}

	if ( qa_opt( 'show_custom_in_head' ) ) {
		$qa_content['head_lines'][] = qa_opt( 'custom_in_head' );
	}

	if ( qa_opt( 'show_custom_header' ) ) {
		$qa_content['body_header'] = qa_opt( 'custom_header' );
	}

	if ( isset( $categoryids ) ) {
		$qa_content['categoryids'] = $categoryids;
	}

	foreach ( $navpages as $page ) {
		if ( 'B' == $page['nav'] ) {
			qa_navigation_add_page( $qa_content['navigation']['main'], $page );
		}
	}

	if ( qa_opt( 'nav_home' ) && qa_opt( 'show_custom_home' ) ) {
		$qa_content['navigation']['main']['$'] = array(
			'url'   => qa_path_html( '' ),
			'label' => qa_lang_html( 'main/nav_home' ),
		);
	}

	if ( qa_opt( 'nav_activity' ) ) {
		$qa_content['navigation']['main']['activity'] = array(
			'url'   => qa_path_html( 'activity' ),
			'label' => qa_lang_html( 'main/nav_activity' ),
		);
	}

	$hascustomhome = qa_has_custom_home();

	if ( qa_opt( $hascustomhome ? 'nav_qa_not_home' : 'nav_qa_is_home' ) ) {
		$qa_content['navigation']['main'][$hascustomhome ? 'qa' : '$'] = array(
			'url'   => qa_path_html( $hascustomhome ? 'qa' : '' ),
			'label' => qa_lang_html( 'main/nav_qa' ),
		);
	}

	if ( qa_opt( 'nav_questions' ) ) {
		$qa_content['navigation']['main']['home'] = array(
			'url'   => qa_path_html( 'home' ),
			'label' => '<i class="fas fa-home"></i>' . qa_lang_html( 'main/nav_qs' ),
		);
	}

	if ( qa_opt( 'nav_hot' ) ) {
		$qa_content['navigation']['main']['hot'] = array(
			'url'   => qa_path_html( 'hot' ),
			'label' => '<i class="fa-solid fa-fire-flame-simple"></i>' . qa_lang_html( 'main/nav_hot' ),
		);
	}

	if ( qa_opt( 'nav_news' ) ) {
		$qa_content['navigation']['main']['news'] = array(
			'url'   => qa_path_html( 'news' ),
			'label' => '<i class="fas fa-newspaper"></i>' . qa_lang_html( 'main/nav_news' ),
		);
	}
	if ( qa_opt( 'nav_video' ) ) {
		$qa_content['navigation']['main']['video'] = array(
			'url'   => qa_path_html( 'video' ),
			'label' => '<i class="fas fa-video"></i>' . qa_lang_html( 'main/nav_video' ),
		);
	}

	if ( qa_opt( 'hnav_home' ) ) {
		$qa_content['navigation']['head']['home'] = array(
			'url'   => qa_path_html( 'home' ),
			'label' => '<i class="fas fa-home"></i> ' . qa_lang_html( 'main/nav_qs' ),
		);
	}
	if ( qa_opt( 'hnav_shorts' ) ) {
		$qa_content['navigation']['head']['shorts'] = array(
			'url'   => qa_path_html( 'shorts' ),
			'label' => '<i class="fa-solid fa-clapperboard"></i> ' . qa_lang_html( 'misc/shorts' ),
		);
	}
	if ( qa_opt( 'hnav_updates' ) && qa_is_logged_in() ) {
		$qa_content['navigation']['head']['updates'] = array(
			'url'   => qa_path_html( 'updates' ),
			'label' => '<i class="fa-solid fa-bolt"></i> ' . qa_lang_html( 'misc/nav_discover' ),
		);
	}
	if ( qa_opt( 'hnav_reactions' ) ) {
		$qa_content['navigation']['head']['reactions'] = array(
			'url'   => qa_path_html( 'reactions' ),
			'label' => '<i class="fa-brands fa-ello"></i> ' . qa_lang_html( 'misc/reactions' ),
		);
	}

	if ( qa_opt( 'hnav_hot' ) ) {
		$qa_content['navigation']['head']['hot'] = array(
			'url'   => qa_path_html( 'hot' ),
			'label' => '<i class="fa-solid fa-fire-flame-simple"></i> ' . qa_lang_html( 'main/nav_hot' ),
		);
	}
	if ( qa_opt( 'hnav_formats' ) ) {
		$qa_content['navigation']['head']['type'] = array(
			'url'   => qa_path_html( 'type' ),
			'label' => '<i class="fas fa-compass"></i> ' . qa_lang_html( 'misc/nav_types' ),
		);
	}
	// Only the 'level' permission error prevents the menu option being shown - others reported on king-page-ask.php

	if ( qa_opt( 'nav_ask' ) && ( qa_user_maximum_permit_error( 'permit_post_q' ) != 'level' ) ) {
		$qa_content['navigation']['main']['ask'] = array(
			'url'   => qa_path_html( 'ask', ( qa_using_categories() && strlen( $lastcategoryid ) ) ? array( 'cat' => $lastcategoryid ) : null ),
			'label' => '<i class="fas fa-image"></i>' . qa_lang_html( 'main/nav_ask' ),
		);
	}

	if ( qa_using_tags() && qa_opt( 'nav_tags' ) ) {
		$qa_content['navigation']['main']['tag'] = array(
			'url'         => qa_path_html( 'tags' ),
			'label'       => '<i class="fas fa-hashtag"></i>' . qa_lang_html( 'main/nav_tags' ),
			'selected_on' => array( 'tags$', 'tag/' ),
		);
	}

	if ( qa_using_categories() && qa_opt( 'nav_categories' ) ) {
		$qa_content['navigation']['main']['categories'] = array(
			'url'         => qa_path_html( 'categories' ),
			'label'       => '<i class="fas fa-hashtag"></i>' . qa_lang_html( 'main/nav_categories' ),
			'selected_on' => array( 'categories$', 'categories/' ),
		);
	}

	if ( qa_opt( 'nav_users' ) ) {
		$qa_content['navigation']['main']['user'] = array(
			'url'         => qa_path_html( 'users' ),
			'label'       => '<i class="fas fa-users"></i>' . qa_lang_html( 'main/nav_users' ),
			'selected_on' => array( 'users$', 'users/', 'user/' ),
		);
	}

	if (
		( qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN ) ||
		( ! qa_user_maximum_permit_error( 'permit_moderate' ) ) ||
		( ! qa_user_maximum_permit_error( 'permit_hide_show' ) ) ||
		( ! qa_user_maximum_permit_error( 'permit_delete_hidden' ) )
	) {
		$qa_content['navigation']['main']['admin'] = array(
			'url'         => qa_path_html( 'admin' ),
			'label'       => '<i class="fas fa-cog"></i>' . qa_lang_html( 'main/nav_admin' ),
			'selected_on' => array( 'admin/' ),
		);
	}

	$qa_content['search'] = array(
		'form_tags'    => 'method="get" action="' . qa_path_html( 'search' ) . '"',
		'form_extra'   => qa_path_form_html( 'search' ),
		'title'        => qa_lang_html( 'main/search_title' ),
		'field_tags'   => 'name="q"',
		'button_label' => qa_lang_html( 'main/search_button' ),
	);

	if ( ! qa_opt( 'feedback_enabled' ) ) {
		unset( $qa_content['navigation']['footer']['feedback'] );
	}
	foreach ( $navpages as $page ) {
		if ( 'H' == $page['nav'] ) {
			qa_navigation_add_page( $qa_content['navigation']['head'], $page );
		}
	}
	foreach ( $navpages as $page ) {
		if ( 'G' == $page['nav'] ) {
			qa_navigation_add_page( $qa_content['navigation']['headmenu'], $page );
		}
	}

	foreach ( $navpages as $page ) {
		if (  ( 'M' == $page['nav'] ) || ( 'O' == $page['nav'] ) || ( 'F' == $page['nav'] ) ) {
			qa_navigation_add_page( $qa_content['navigation'][( 'F' == $page['nav'] ) ? 'footer' : 'main'], $page );
		}
	}

	$regioncodes = array(
		'F' => 'full',
		'M' => 'main',
		'S' => 'side',
	);

	$placecodes = array(
		'T' => 'top',
		'H' => 'high',
		'L' => 'low',
		'B' => 'bottom',
	);

	foreach ( $widgets as $widget ) {
		if ( is_numeric( strpos( ',' . $widget['tags'] . ',', ',' . $qa_template . ',' ) ) || is_numeric( strpos( ',' . $widget['tags'] . ',', ',all,' ) ) ) {
			// see if it has been selected for display on this template
			$region = @$regioncodes[substr( $widget['place'], 0, 1 )];
			$place  = @$placecodes[substr( $widget['place'], 1, 2 )];

			if ( isset( $region ) && isset( $place ) ) {
				// check region/place codes recognized
				$module = qa_load_module( 'widget', $widget['title'] );

				if (
					isset( $module ) &&
					method_exists( $module, 'allow_template' ) &&
					$module->allow_template(  ( substr( $qa_template, 0, 7 ) == 'custom-' ) ? 'custom' : $qa_template ) &&
					method_exists( $module, 'allow_region' ) &&
					$module->allow_region( $region ) &&
					method_exists( $module, 'output_widget' )
				) {
					$qa_content['widgets'][$region][$place][] = $module; // if module loaded and happy to be displayed here, tell theme about it
					$qa_content['wtitle'][$region][$place][]  = $widget['wtitle'];
					$qa_content['wextra'][$region][$place][]  = $widget['wextra'];
				}
			}
		}
	}

	$logoshow   = qa_opt( 'logo_show' );
	$logourl    = 'king-include/watermark/' . qa_opt( 'logo_url' );
	$logowidth  = qa_opt( 'logo_width' );
	$logoheight = qa_opt( 'logo_height' );

	if ( $logoshow ) {
		$qa_content['logo'] = '<a href="' . qa_path_html( '' ) . '" class="king-logo-link" title="' . qa_html( qa_opt( 'site_title' ) ) . '">';
		$qa_content['logo'] .= '<img class="king-logol" src="' . qa_html( is_numeric( strpos( $logourl, '://' ) ) ? $logourl : qa_path_to_root() . $logourl ) . '" border="0" alt="' . qa_html( qa_opt( 'site_title' ) ) . '"/>';
		if (qa_opt('night_logo_url')) {
			$qa_content['logo'] .= '<img class="king-logon" src="' . qa_html( qa_path_to_root() . 'king-include/watermark/' . qa_opt( 'night_logo_url' ) ) . '" border="0" alt="' . qa_html( qa_opt( 'site_title' ) ) . '"/>';
		}
		$qa_content['logo'] .= '</a>';
	} else {
		$qa_content['logo'] = '<a href="' . qa_path_html( '' ) . '" class="king-logo-link">' . qa_html( qa_opt( 'site_title' ) ) . '</a>';
	}

	$topath = qa_get( 'to' ); // lets user switch between login and register without losing destination page

	$userlinks = qa_get_login_links( qa_path_to_root(), isset( $topath ) ? $topath : qa_path( $request, $_GET, '' ) );

	$qa_content['navigation']['user'] = array();

	if ( qa_is_logged_in() ) {
		$qa_content['loggedin'] = qa_lang_html_sub_split( 'main/logged_in_x', QA_FINAL_EXTERNAL_USERS
				? qa_get_logged_in_user_html( qa_get_logged_in_user_cache(), qa_path_to_root(), false )
				: qa_get_one_user_html( qa_get_logged_in_handle(), false )
		);

		if ( ! QA_FINAL_EXTERNAL_USERS ) {
			$qa_content['navigation']['user']['account'] = array(
				'url'   => qa_path_html( 'account' ),
				'label' => qa_lang_html( 'main/nav_account' ),
			);
		}

		$qa_content['navigation']['user']['messages'] = array(
			'url'   => qa_path_html( 'messages' ),
			'label' => qa_lang_html( 'main/nav_user_pms' ),
		);
		$qa_content['navigation']['user']['updates2'] = array(
			'url'   => qa_path_html( 'updates' ),
			'label' => qa_lang_html( 'main/nav_updates2' ),
		);
		$qa_content['navigation']['user']['updates'] = array(
			'url'   => qa_path_html( 'favorites' ),
			'label' => qa_lang_html( 'main/nav_updates' ),
		);

		if ( ! empty( $userlinks['logout'] ) ) {
			$qa_content['navigation']['user']['logout'] = array(
				'url'   => qa_html( @$userlinks['logout'] ),
				'label' => qa_lang_html( 'main/nav_logout' ),
			);
		}

		if ( ! QA_FINAL_EXTERNAL_USERS ) {
			$source = qa_get_logged_in_source();

			if ( strlen( $source ) ) {
				$loginmodules = qa_load_modules_with( 'login', 'match_source' );

				foreach ( $loginmodules as $module ) {
					if ( $module->match_source( $source ) && method_exists( $module, 'logout_html' ) ) {
						ob_start();
						$module->logout_html( qa_path( 'logout', array(), qa_opt( 'site_url' ) ) );
						$qa_content['navigation']['user']['logout'] = array( 'label' => ob_get_clean() );
					}
				}
			}
		}

		$notices = qa_db_get_pending_result( 'notices' );

		foreach ( $notices as $notice ) {
			$qa_content['notices'][] = qa_notice_form( $notice['noticeid'], qa_viewer_html( $notice['content'], $notice['format'] ), $notice );
		}
	} else {
		require_once QA_INCLUDE_DIR . 'king-util/string.php';

		if ( ! QA_FINAL_EXTERNAL_USERS ) {
			$loginmodules = qa_load_modules_with( 'login', 'login_html' );

			foreach ( $loginmodules as $tryname => $module ) {
				ob_start();
				$module->login_html( isset( $topath ) ? ( qa_opt( 'site_url' ) . $topath ) : qa_path( $request, $_GET, qa_opt( 'site_url' ) ), 'menu' );
				$label = ob_get_clean();

				if ( strlen( $label ) ) {
					$qa_content['navigation']['user'][implode( '-', qa_string_to_words( $tryname ) )] = array( 'label' => $label );
				}
			}
		}
	}

	if ( QA_FINAL_EXTERNAL_USERS || ! qa_is_logged_in() ) {
		if ( qa_opt( 'show_notice_visitor' ) && ( ! isset( $topath ) ) && ( ! isset( $_COOKIE['qa_noticed'] ) ) ) {
			$qa_content['notices'][] = qa_notice_form( 'visitor', qa_opt( 'notice_visitor' ) );
		}
	} else {
		setcookie( 'qa_noticed', 1, time() + 86400 * 3650, '/', QA_COOKIE_DOMAIN ); // don't show first-time notice if a user has logged in

		if ( qa_opt( 'show_notice_welcome' ) && ( qa_get_logged_in_flags() & QA_USER_FLAGS_WELCOME_NOTICE ) ) {
			if (  ( 'confirm' != $requestlower ) && ( 'account' != $requestlower ) ) // let people finish registering in peace
			{
				$qa_content['notices'][] = qa_notice_form( 'welcome', qa_opt( 'notice_welcome' ) );
			}
		}
	}
	if ( qa_opt( 'show_gdpr' ) && ( ! isset( $_COOKIE['qa_gdpr'] ) ) ) {
		$qa_content['notices'][] = qa_notice_form( 'gdpr', qa_opt( 'gdpr_box' ) );
	}

	$qa_content['script_rel']   = array( 'king-content/jquery-1.7.2.min.js' );
	$qa_content['script_rel'][] = 'king-content/king-page.js?' . QA_VERSION;

	if ( $voting ) {
		$qa_content['error'] = @$qa_page_error_html;
	}

	$qa_content['script_var'] = array(
		'qa_root'    => current_url(),
		'qa_request' => $request,
	);

	return $qa_content;
}

function qa_get_start()
/*
Get the start parameter which should be used, as constrained by the setting in king-config.php
 */
{
	return min( max( 0, (int) qa_get( 'start' ) ), QA_MAX_LIMIT_START );
}

/**
 * @return mixed
 */
function qa_get_state()
/*
Get the state parameter which should be used, as set earlier in qa_load_state()
 */
{
	global $qa_state;

	return $qa_state;
}

//	Below are the steps that actually execute for this file - all the above are function definitions

global $qa_usage;

qa_report_process_stage( 'init_page' );
qa_db_connect( 'qa_page_db_fail_handler' );

qa_page_queue_pending();
qa_load_state();
qa_check_login_modules();

if ( QA_DEBUG_PERFORMANCE ) {
	$qa_usage->mark( 'setup' );
}

qa_check_page_clicks();

$qa_content = qa_get_request_content();

if ( is_array( $qa_content ) ) {
	if ( QA_DEBUG_PERFORMANCE ) {
		$qa_usage->mark( 'view' );
	}

	qa_output_content( $qa_content );

	if ( QA_DEBUG_PERFORMANCE ) {
		$qa_usage->mark( 'theme' );
	}

	if ( qa_do_content_stats( $qa_content ) && QA_DEBUG_PERFORMANCE ) {
		$qa_usage->mark( 'stats' );
	}

	if ( QA_DEBUG_PERFORMANCE ) {
		$qa_usage->output();
	}
}

qa_db_disconnect();

/*
Omit PHP closing tag to help avoid accidental output
 */