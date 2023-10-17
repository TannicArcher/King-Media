<?php

	class king_tag_cloud {
		public $voteformcode;

		public function allow_template($template)
		{
			$allow=false;
			
			switch ($template)
			{

				case 'home':
				case 'question':
				case 'custom':
					$allow=true;
					break;
			}
			
			return $allow;
		}

		
		public function allow_region( $region ) {
			return in_array( $region, array( 'side' ) );
		}

		public function widget_option($wextra) {
			$warray = ( null !== $wextra ) ? unserialize($wextra) : array();
			$number = isset($warray['number']) ? $warray['number'] : '6';
			$woptions = array(
				'tnumber' => array(
					'tags' => 'name="wextra[number]"',
					'label' => 'Maximum tags to show:',
					'type' => 'text',
					'value' => $number,
				));

			return $woptions;
		}

		public function output_widget($region, $place, $themeobject, $template, $request, $qa_content, $wtitle=null, $wextra=null)
		{
			require_once QA_INCLUDE_DIR.'king-db/selects.php';
			$warray = ( null !== $wextra ) ? unserialize($wextra) : array();
			$number = isset($warray['number']) ? $warray['number'] : '6';
			$populartags=qa_db_single_select(qa_db_popular_tags_selectspec(0, $number));
			
			$title = isset($wtitle) ? $wtitle : qa_lang_html('main/popular_tags');

			reset($populartags);
			$maxcount=current($populartags);

			$themeobject->output('<div class="tagcloud king-widget-wb">');
			$themeobject->output(
				'<div class="widget-title">',
				$title,
				'</div>'
			);
			$maxsize=100;
			$scale=true;
			
			foreach ($populartags as $tag => $count) {
				$size=number_format(($scale ? ($maxsize*$count/$maxcount) : $maxsize), 1);
				
				if (($size>=5))
					$themeobject->output('<a href="'.qa_path_html('tag/'.$tag).'" >'.qa_html($tag).'</a>');
			}
			
			$themeobject->output('</div>');

		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/