<?php
/*

	File: king-include/king-page-unanswered.php
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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'king-db/selects.php';
	require_once QA_INCLUDE_DIR.'king-app/format.php';
	require_once QA_INCLUDE_DIR.'king-app/q-list.php';

	if (QA_ALLOW_UNINDEXED_QUERIES)
		$categoryslugs=qa_request_parts(1);
	else
		$categoryslugs=null;

	$by=qa_get('by');
	$start=qa_get_start();
	$userid=qa_get_logged_in_userid();

	switch ($by) {
		case '2':
			$selectby='reac_2';
			break;
		case '3':
			$selectby='reac_3';
			break;
		case '4':
			$selectby='reac_4';
			break;
		case '5':
			$selectby='reac_5';
			break;
		case '6':
			$selectby='reac_6';
			break;
		case '7':
			$selectby='reac_7';
			break;
		case '8':
			$selectby='reac_8';
			break;
		default:
			$selectby='reac_1';
			break;
	}


	$pagesize = qa_opt('page_size_qs');
	list($questions, $categories, $categoryid)=qa_db_select_with_pending(
		king_db_get_reaction_posts($userid, $start, 222, $selectby),
		QA_ALLOW_UNINDEXED_QUERIES ? qa_db_category_nav_selectspec($categoryslugs, false, false, true) : null,
		qa_db_slugs_to_category_id_selectspec($categoryslugs)
	);


	$count = count($questions);
	$linkparams=array('by' => $by);
	$nonetitle=qa_lang_html('main/no_unselected_qs_found');
	$sometitle=qa_lang_html('misc/reactions');



//	Prepare and return content for theme
	
	$qa_content=qa_q_list_page_content(
		$questions, // questions
		$pagesize, // questions per page
		$start, // start offset
		$count, // total count
		$sometitle, // title if some questions
		$nonetitle, // title if no questions
		QA_ALLOW_UNINDEXED_QUERIES ? $categories : null, // categories for navigation (null if not shown on this page)
		QA_ALLOW_UNINDEXED_QUERIES ? $categoryid : null, // selected category id (null if not relevant)
		false, // show question counts in category navigation
		QA_ALLOW_UNINDEXED_QUERIES ? 'reactions/' : null, // prefix for links in category navigation (null if no navigation)
		null, // prefix for RSS feed paths (null to hide)
		null, // suggest what to do next
		$linkparams, // extra parameters for page links
		$linkparams // category nav params
	);
	if (!$count) {
		$qa_content['custom'] = '<div class="nopost"><i class="far fa-frown-open fa-4x"></i> '.qa_lang_html('main/no_unselected_qs_found').'</div>';
	}

	$qa_content['navigation']['sub']=reaction_nav($by);

	$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

	$qa_content['class']=' full-page';
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/