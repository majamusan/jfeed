<?php 
/*
	Class with all the action in it.
	Note: view is inside the controler, Only because this is a demo script.
*/

class jfeed {
	
	private $sizes;
	private $url;

	function __construct($url){
		$this->url = $url;
		$this->sizes['teamW'] = 90;
		$this->sizes['teamH'] = 90;
		$this->sizes['iconW'] = 50;
		$this->sizes['iconH'] = 30;
		$this->sizes['smallW'] = 469;
		$this->sizes['smallH'] = 245;
		$this->sizes['bigW'] = 200;
		$this->sizes['bigH'] = 105;
	}

	public function proccessIt($cron){

	 	//-----------------------------------------------------------------------------------------------------------------[load and proccess file]
		$xml = simplexml_load_file($this->url);
		foreach($xml as $k => $v){
			$imageUrl = $v->media->mediaItem->url.$v->media->mediaItem->size[42].'/'.$v->media->mediaItem->mediaName;
			$teams = [];
			$content = [];

			////-----------------------------------------------------------------------------------------------------------------[data mining content ]
			$teams = explode(' v ', str_replace("Preview: ", "", $v->title));
			$data = explode('<p>', str_replace("<br />", "<p>", $v->body));
			foreach ($data as $key => $value) {
				$info = explode('</strong>', str_replace("<strong>", "", $value));
				if(trim($info[0]) == 'Date:' ) $knowlage['date'] = $info[1];
				if(trim($info[0]) == 'Venue:' ) $knowlage['venue'] = explode(',',$info[1]);
				if(trim($info[0]) == 'Kick-off:' ) $knowlage['kickOff'] = $info[1];
			}

			//-----------------------------------------------------------------------------------------------------------------[make gems from mining]
			$data = explode(', ', $knowlage['date']);//strtotime didn't know about this format...
			$dayOf = strtotime($data[1].' '.date('Y',time() ) );
			$dayBefore = $dayOf - 86400;
			$dayAfter = $dayOf + 86400;
			$images['team1'] = $this->sizeImage('../wp-content/plugins/jfeed/images/logos/'.strtolower(str_replace(' ', '', $teams[0]).'.png'), $this->sizes['teamW'], $this->sizes['teamH'], $teams[0]);
			$images['team2'] = $this->sizeImage('../wp-content/plugins/jfeed/images/logos/'.strtolower(str_replace(' ', '', $teams[1]).'.png'), $this->sizes['teamW'], $this->sizes['teamH'], $teams[1]);
			$images['holtes'] = $this->sizeImage('../wp-content/plugins/jfeed/images/logos/hotel.png', $this->sizes['iconW'], $this->sizes['iconH'],'holtes');
			$images['flights'] = $this->sizeImage('../wp-content/plugins/jfeed/images/logos/flight.png', $this->sizes['iconW'], $this->sizes['iconH'],'flights');
			$location = (isset($knowlage['venue'][1])?$knowlage['venue'][1]:$knowlage['venue'][0]);

			//-----------------------------------------------------------------------------------------------------------------[compiling and saving post]
			$body = '<h1>
						<div>
							<img style="float:left" src="'.$this->sizeImage($imageUrl,$this->sizes['bigW'],$this->sizes['bigH'],$v->attributes()['id']).'" alt="'.$v->media->mediaItem->caption.'" >
							<a href="https://www.ecosia.org/search?q='.$teams[0].'+rugby+team" ><img src="'.$images['team1'].'"/></a> & <a href="https://www.ecosia.org/search?q='.$teams[1].'+rugby+team" ><img src="'.$images['team2'].'"/></a>
						</div>
						<small>Kick Off <strong>'.$knowlage['kickOff'].'</strong></small><br />At '.$knowlage['venue'][0].'<br /><small>'.$knowlage['venue'][1].'</small>
					</h1>
					'.$v->body.'
					<h2 style="padding: 0px;">
						<a href="https://www.ecosia.org/search?q=flight to '.$location.', South Africa on '.date('d,M Y',$dayBefore).'"><img src="'.$images['flights'].'"/> Get a flight day before?</a> <br />
						<a href="https://www.ecosia.org/search?q=Book a room in '.$location.', South Africa on '.date('d,M Y',$dayBefore).'"><img src="'.$images['holtes'].'"/> Book a bed day before?</a> <br />
						<a href="https://www.ecosia.org/search?q=Book a room in '.$location.', South Africa on '.date('d,M Y',$dayAfter).'"><img src="'.$images['holtes'].'"/> Book a bed day after?</a>
					</h2> '.
					$this->imageCaption($imageUrl,$this->sizes['smallW'],$this->sizes['smallH'],$v->attributes()['id'],$v->media->mediaItem->caption,$v->abstract,$v->media->mediaItem->keywords);
			$post = [
			    'post_title'    => (string)$v->title,
				'post_excerpt'	=> (string)$v->abstract ,
				'post_content'	=> $body,
				//'post_type'	=> (int)$v->articleType->attributes(),// post type could be useful 
				'post_date'		=> date('Y-m-d h:m:s',strtotime($v->lastUpdated)),
				'post_date_gmt'	=> date('Y-m-d h:m:s',strtotime($v->lastUpdated)),
				'post_modified'	=> date('Y-m-d h:m:s',strtotime($v->lastUpdated)),
				'post_modified_gmt'	=> date('Y-m-d h:m:s',strtotime($v->lastUpdated)),

			];
			if($v->attributes()['active'] == 'yes') $post['post_status'] = 'publish';
		    $post_id = wp_insert_post( $post, true ); 
		    wp_set_post_categories( $post_id, [2,3] ,false );
		    wp_set_post_tags( $post_id, $v->media->mediaItem->keywords, true );


		    //-----------------------------------------------------------------------------------------------------------------[interface feedback]
		 	if(!$cron) {
		 		echo '<a href="http://localhost/wordpress/wp-admin/post.php?post='.$post_id.'&action=edit">edit post</a> - '.
			 		date('d,M Y',$dayOf).'<img src="'.$images['team1'].'"/><img src="'. $images['team2'].'" /><br />';
		 	}else{
		 		echo 'jfeed ran '.date('d,M,y h:m');
		 	}

		}
	}

	//-----------------------------------------------------------------------------------------------------------------[capture format feed images and return nice html output. note pwd is wp-admin]
	private function imagecaption($orginal,$width,$height,$id,$caption,$title,$keywords){
		return '<figure style="width: '.$width.'px" class="wp-caption alignnone">
			<img src="'.$this->sizeImage($orginal,$width,$height,$id).'" alt="'.$keywords.'" width="'.$width.'" height="'.$height.'">
			<figcaption class="wp-caption-text">'.(empty((string)$caption)?$title:$caption).'</figcaption>
			</figure>';
	}

	//-----------------------------------------------------------------------------------------------------------------[create sized images in cache]
	private function sizeImage($orginal,$width,$height,$name){
		//could do a check to see if file exists here.
		$image = wp_get_image_editor( $orginal );
		if (!is_wp_error( $image ) ) {
		    $image->resize( $width, $height, true );
		    $file = '/wp-content/plugins/jfeed/images/cache/'.$name.'_'.$width.$height.'.png';
		    $image->save( '..'.$file );
		}else{
			$mia = wp_get_image_editor('../wp-content/plugins/jfeed/images/logos/mia.png');
		    $mia->resize( $width, $height, true );
		    $file = '/wp-content/plugins/jfeed/images/cache/mia_'.$width.$height.'.png';
		    $mia->save( '..'.$file );
		}
		return site_url($file);
	}

}