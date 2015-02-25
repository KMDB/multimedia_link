<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  multimedia_link
 * @author NAVER (developers@xpressengine.com)
 * @brief The components connected to the body of multimedia data
 */
/** 1Sam Online Edtion (csh@korea.com // 1sam.kr) */

class multimedia_link extends EditorHandler
{	// editor_sequence from the editor must attend mandatory wearing ....
	var $editor_sequence = 0;
	var $component_path = '';

	/**
	 * @brief editor_sequence and components out of the path
	 */
	function multimedia_link($editor_sequence, $component_path)
	{	$this->editor_sequence = $editor_sequence;
		$this->component_path = $component_path;
	}

	/**
	 * @brief popup window to display in popup window request is to add content
	 */
	function getPopupContent()
	{	// Pre-compiled source code to compile template return to
		$tpl_path = $this->component_path.'tpl';
		$tpl_file = 'popup.html';

		Context::set("tpl_path", $tpl_path);

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	function transHTML($xml_obj)
	{	/*
		$is_yt = Context::get('is_yt');
		if(empty($is_yt))
		{	Context::set('is_yt', $is_yt);}
		else
		{	Context::set('is_yt', $is_yt);}
		*/

		// popup.html에서 변수 받아 오기
		$src = $xml_obj->attrs->multimedia_src;
		$start = $xml_obj->attrs->multimedia_start;
		$style = $xml_obj->attrs->style;
		$responsive = $xml_obj->attrs->multimedia_responsive | 'true';
		$volume = $xml_obj->attrs->volume | '100';

		// https://developers.google.com/youtube/player_parameters?hl=ko#loop
		// 반복 재생을 위해서는 playlist가 꼭 필요함. 따라서 playlist에 자신의 id를 아래에서 지정하게 함

		// popup.html 에서 넘어온 추가 옵션
		$yt_loop = $xml_obj->attrs->yt_loop | '1';
		//$playlist = 

		/*
		//뭔가 이상해서 attrs에서 값을 참조하도록 수정하였음
		preg_match_all('/(width|height)([^[:digit:]]+)([0-9]+)/i',$style,$matches);
		$width = trim($matches[3][0]);
		$height = trim($matches[3][1]);
		*/

		$width = $xml_obj->attrs->width;
		$height = $xml_obj->attrs->height;

		//기본 높이와 너비를 지정해줌
		if(!$width) $width = 896; //890
		if(!$height) $height = 504; //530

		// 멀티미디어 컴포넌트에서 auto_play변수 였던 것이 auto_start로 변경되었음.
		// 기존 설정에서 누락된 경우 true로 지정(false로 지정이 된 경우에도 true로 바꿔버리는 위험한 옵션)
		$auto_start = $xml_obj->attrs->auto_start; //| "1";

		// 자동재생 옵션, 에디터에서 간편 코드 수정을 위해 true -> 1로 변경
		$auto_play = $auto_start=="1" ? '1': '0';
		// 네이버 tvcast 등 true 옵션 사용 용도
		$auto_play_true = $auto_start=="1" ? 'true': 'false';

		$wmode = $xml_obj->attrs->wmode;
		if($wmode == 'window') $wmode = 'window';
		else if($wmode == 'opaque') $wmode = 'opaque';
		else $wmode = 'transparent';

		$caption = $xml_obj->body;

		$src = str_replace(array('&','"'), array('&amp;','&qout;'), $src);
		$src = str_replace('&amp;amp;', '&amp;', $src);

		if(preg_match_all('/(?:youtube-nocookie\.com\/embed\/|youtube\.com\/watch\?v\=|youtube\.com\/v\/|youtu\.be\/?|youtube\.com\/embed\/).*?([0-9a-zA-Z-_]{11}?)(.*?list=([0-9a-zA-Z-_]{34}?))?(@pl=([^@]*))?(@s=([^@]*))?(@e=([^@]*))?/i', $src, $matches))
		{	// Youtube list(재생 목록 ID) 형태
			if(strpos($src, "list=") !== false)
			{	$yt_list_id = $matches[3][0];
				$yt_list_type = 'playlist';
			}
			else
			{	$yt_list_id = null;
				$yt_list_type = null;
			}

			// Youtube playlist(재생 목록 ID가 아닌) 형태
			// 영상ID, 영상ID...
			if(strpos($src, "@pl=") !== false)
			{	$yt_playlist_ids = $matches[5][0];}
			else
			{	$yt_playlist_ids = null;}

			// Youtube start time(초단위 변환)
			if(strpos($src, "@s=") !== false)
			{	preg_match('/^(\d*?):?(\d*?):?(\d*)$/i', $matches[7][0], $yt_start_match);
				$yt_start_time = ($yt_start_match[1]*3600) + ($yt_start_match[2]*60) + $yt_start_match[3];
			}
			else
			{	$yt_start_time = null;}

			// Youtube end time(초단위 변환)
			if(strpos($src, "@e=") !== false)
			{	preg_match('/^(\d*?):?(\d*?):?(\d*)$/i', $matches[9][0], $yt_end_match);
				$yt_end_time = ($yt_end_match[1]*3600) + ($yt_end_match[2]*60) + $yt_end_match[3];
			}
			else
			{	$yt_end_time = null;}


			// Youtube ID
			$yt_id = $matches[1][0];
			$yt_ids = Context::get('yt_ids');
			$yt_options = Context::get('yt_options');

			// <div>의 개별 코드로 활용됨 ex) <div id="plyaer0">, <div id="plyaer1">
			//플레이 리스트는 일단 자기 자신만 테스트삼아 추가
			if($yt_loop == "1") $yt_playlist = $yt_id;

			// 처음일 경우에는 0임
			if(empty($yt_ids))
			{	$vars = Context::getRequestVars();
				$args->document_srl = $vars->document_srl;
				$output = executeQuery('document.getDocument', $args, '');

				preg_match_all('/<img.*?(?:youtu\.be\/|youtube\.com\/(?:watch\?(?:.*&)?v=|(?:embed|v)\/))([^\?\"\'\>]{11}).*?[^\w]/i', $output->data->content, $match);

				// 변수를 선언하고 첫번째 값 대입
				$yt_ids = array();
				$yt_ids[] = $yt_id;
				Context::set('yt_ids', $yt_ids);

				// $yt_howmany 가 0이되면 마지막임
				Context::set('yt_howmany', count($match[1]));
				$yt_counter = 1;
				Context::set('yt_counter', $yt_counter);
				$yt_options[$yt_counter] = array
				(	/* 기본 크기와 동영상 아이디
					* Options (Private)
					*
					* holds options for helper
					*/
					'width'		=>	$width,
					'height'	=>	$height,
					'video_id'	=>	$yt_id,
					'responsive'=>	$responsive
				);

				/* 플레이어와 연관된 변수
				* Player Vars (Private)
				*
				* holds parameters for embedded player
				* @see http://code.google.com/apis/youtube/player_parameters.html?playerVersion=HTML5
				* https://developers.google.com/youtube/youtube_player_demo?hl=ko
				*/

/*** ABC 순서 (기본값 [옵션]) ***
★ autohide			> 컨트롤바를 자동으로 숨길지 여부 (2 [0, 1])
|					├ 0: 프로그레스바 및 컨트롤바가 재생중 & 전체화면에서도 표시됨
|					├ 1: 재생 몇 초 후 프로그레스바 및 컨트롤바 사라짐. 마우스 hover, 키보드 누를 경우출력
|					└ 2: 컨트롤바가 계속 표시되는 반면 프로그레스바는 점차 사라짐

★ autoplay			> 자동재생 여부 (0 [1])

★ cc_load_policy	> 자막 강제 표시 여부 (사용자 환경설정 따름 [1])

★ color				> 프로그레스바 색상 (red [white])
|					└* white로 할 경우 modestbranding 옵션 사용 불가

★ controls			> 컨트롤바 표시 여부 (1 [0, 2])
|					├ 0: 표시 해제
|					├ 1: 표시
|					└ 2: 재생 시작 후 컨트롤바 표시 (iframe의 경우만)

★ disablekb			> 키보드 컨트롤 적용 여부 (0 [1])

★ enablejsapi		> JavaScript API 사용 여부 (0 [1])
|					└* seekTo 등 페이지에서 버튼 컨트롤을 위해 필요

★ end				> 재생 중지 시간을 시작 부분(start 지정 시간이 아닌)부터 초 단위로 측정하여 지정 (양의 정수)

★ fs				> 전체화면 버튼이 표시 여부 (1 [0])

★ hl				> 플레이어 인터페이스 언어 설정 (두 문자 언어코드 [ex: ko])
					└* http://www.loc.gov/standards/iso639-2/php/code_list.php

★ iv_load_policy	> 특수효과 표시 여부 (1 [3])

★ list				> listType 과 함께, 로드될 콘텐츠 식별
					├ listType 값, playlist 일 경우-> YouTube 재생목록 ID 지정 (*ID앞에 "PL" 필수)
					├ listType 값, search 일 경우-> 검색어 지정
					├ listType 값, user_uploads 일 경우-> YouTube 채널 식별
					└* list 와 listType 값 지정할 경우, IFrame 삽입 URL에서 동영상 ID 지정 불필요

★ listType			> list 와 함께, 로드할 콘텐츠를 식별 ([playlist, search, user_uploads])

★ loop				> 반복 재생 (0 [1])
					├* 현재 playlist 와 함께 사용하는 경우에만 AS3 플레이어에서 작동
					└* http://www.youtube.com/v/영상ID?version=3&loop=1&playlist=영상ID

★ modestbranding	> 컨트롤바에 YouTube 로고 표시 여부 (0 [1])
|					└* color 값을 white로 할 경우 옵션 적용 안됨

★ origin			> 추가 보안 수단 제공, IFrame 삽입에서만 지원 (도메인 URL)
					└* enablejsapi 를 1로 설정할 경우, 도메인(URL)을 항상 origin 값으로 지정해야 함

★ playerapiid		> JavaScript API와 함께 사용 (모든 영,숫자 문자열)
					└* https://developers.google.com/youtube/js_api_reference?hl=ko

★ playlist			> (동영상 ID를 쉼표(,)로 구분한 목록)
					└* URL 에서 지정한 VIDEO_ID 먼저 재생 후, playlist 지정 동영상 재생

★ playsinline		> iOS HTML5 플레이어, 인라인 or 전체화면 재생 여부 제어 (0 [1])
					├ 0: 전체화면 재생. 현재 기본값이지만 변경될 수 있음
					└ 1: TRUE로 설정된 allowsInlineMediaPlayback 속성과 함께 만들어진 UIWebViews이 인라인으로 재생됨

★ rel				> 재생 종료 시점에, 관련 동영상 표시할지 여부 (1 [0])

★ showinfo			> 동영상 제목 및 업로더 같은 정보 표시 여부 (1 [0])

★ start				> 특정 시간(단위: 초) 지점부터, 동영상 재생 (양의 정수)
					└* seekTo 와 비슷하게, 지정한 시간과 가장 가까운 키프레임 찾는 점 유의. 즉, 요청한 시간 바로 앞 부분을 찾을 수도 있으며 일반적으로 2초 이내

★ theme				> 어두운 컨트롤바 or 밝은 컨트롤바 표시 제어 (dark [light])

= 공식 API 매개변수에 소개되지 않은 변수들 =
@ html5				> html5 지원 영상일 경우 강제 활성화 여부 (브라우져?, 사용자설정?, 따름?[0, 1])
					└* 파이어폭스는 기본 html5 라서 켜고 끄는게 자유롭지 못함

= 공식 API 매개변수에 없는 확인되지 않은 변수들 =
[X] cc_lang_pref	> 자막 언어 api (두 문자 언어코드 [ex: ko])
					└* 기본 iframe 플레이어에서 작동한 변수
[X] vq				> 화질
					└* 기본 iframe 플레이어에서 작동한 변수
[X] showsearch
* * * * * */

				$yt_options[$yt_counter]["playerVars"] = array
				(	'autoplay'		=>	$auto_play,
					'enablejsapi'	=>	1,
					'autohide'		=>	1,
					'showinfo'		=>	0,
					'controls'		=>	2,
					'rel'			=>	1,
					'cc_load_policy'=>	1,
					'iv_load_policy'=>	3,
					'hl'			=>	'ja',
					'color'			=>	'white',
					'theme'			=>	'light',
					'html5'			=>	0,
					'list'			=>	$yt_list_id,
					'listType'		=>	$yt_list_type,
					'playlist'		=>	$yt_playlist_ids,
					'start'			=>	$yt_start_time,
					'end'			=>	$yt_end_time
				);

				$yt_options[$yt_counter]["event"] = array
				(	'volume'		=>	$volume);

				Context::set('yt_options', $yt_options);
			}
			else
			{	// 변수값 추가하기
				$yt_ids[] = $yt_id;
				Context::set('yt_ids', $yt_ids);

				// $yt_howmany 가 0이되면 마지막임
				//$yt_howmany = Context::get('yt_howmany') -1;
				//Context::set('yt_howmany', $yt_howmany);
				$yt_counter = Context::get('yt_counter') +1;
				Context::set('yt_counter', $yt_counter);

				$yt_options[$yt_counter] = array
				(	'width'			=>	$width,
					'height'		=>	$height,
					'video_id'		=>	$yt_id,
					'responsive' => $responsive
				);

				$yt_options[$yt_counter]["playerVars"] = array
				(	'autoplay'		=>	$auto_play,
					'enablejsapi'	=>	1,
					'autohide'		=>	1,
					'showinfo'		=>	0,
					'controls'		=>	2,
					'rel'			=>	1,
					'cc_load_policy'=>	1,
					'iv_load_policy'=>	3,
					'hl'			=>	'en',
					'color'			=>	'white',
					'theme'			=>	'light',
					'html5'			=>	0,
					'list'			=>	$yt_list_id,
					'listType'		=>	$yt_list_type,
					'playlist'		=>	$yt_playlist_ids,
					'start'			=>	$yt_start_time,
					'end'			=>	$yt_end_time
				);

				$yt_options[$yt_counter]["event"] = array
				(	'volume'		=>	$volume);
				Context::set('yt_options', $yt_options);
			}

			// require( 'youtube_helper.php');
			// $youtubehelper = YoutubeHelper::loadClass('YoutubeHelper');

			$yh = new YoutubeHelper;

			// youtube 개체수와 코드생성 회차를 비교하여 마지막에만 js 코드를 생성해 붙임
			if(Context::get('yt_howmany') == $yt_counter) $yt_html_code = $yh->iframePlayer ('http://www.youtube.com/watch?v='.$yt_id.'&feature=feedrec', $yt_ids, $yt_options);

			// 로그인 여부에 따른 다른 출력
			$logged_info = Context::get('logged_info');
			if ($logged_info->is_admin=='Y' || $logged_info->group_list[3])
			{	// Youtube iframe 스타일
				// return sprintf('<div class="trailer"><iframe src="//www.youtube.com/embed/%srel=1&amp;vq=hd1080&amp;autoplay=%s&amp;color=white&amp;theme=light&amp;iv_load_policy=3&amp;cc_lang_pref=ko&amp;cc_load_policy=1" frameborder="0" allowfullscreen></iframe></div>', $youtube_id, $auto_option);

				// Youtube API 스타일
				// return youtube_api_code($width, $height, $youtube_id, $auto_option);
				return '<div class="'.($responsive == 'true'?'videowrapper':'normalwrapper').'"><div id="player'.(string)$yt_counter.'"></div></div>'.$yt_html_code;
			}
			else
			{	// Youtube Embed 링크 + 이미지 형태 (막상 해놓고 보니 이미지도 저작권...)
				// 이미지에는 썸네일의 영상 ID만 넣어주기 위해 ID 11자리만 가져오도록 수정.
				// 비로그인은 링크이기 때문에 모두 autoplay=1으로 적용.
				return sprintf('<a href="//www.youtube.com/embed/%s?rel=1&amp;vq=hd1080&amp;autoplay=1&amp;color=white&amp;theme=light&amp;iv_load_policy=3" target="_blank"><code style="margin-bottom: 5px;">Youtube에서 감상</code></br><img src="https://img.youtube.com/vi/%s/maxresdefault.jpg" style="background:url() no-repeat center; box-shadow: 0 1px 7px #48335E; margin: 0 0 10px 0;"></a>', $yt_id, $yt_id);

				// <div style="position: absolute; background: url(https://lh4.googleusercontent.com/-PvJZwKv_1H0/UxWALQ2q63I/AAAAAAAAAkY/8R1iBlio12c/s50-no/video-play-3-xxl.png) no-repeat center; width: 90.3%; padding-bottom: 50.75%;"></div>

				// Youtube Embed 링크 + 텍스트 형태
				// return sprintf('<a href="//www.youtube.com/embed/%s?rel=1&amp;vq=hd1080&amp;%s&amp;color=white&amp;theme=light" target="_blank">Youtube로 보러 가기</a>', $youtube_id, $auto_option);

				// Youtube 직접 링크 + 텍스트 형태
				// return sprintf('<a href="https://www.youtube.com/watch?v=%s" target="_blank">Youtube로 보러 가기</a>', $youtube_id);

				// modal 창에 로딩되는 형식이 iframe 형태라 비회원 불가능
				// return sprintf('<script type="text/javascript">jQuery(function () {jQuery(".youtube").YouTubeModal({autoplay:0, width:720, height:405});});</script><a class="youtube" href="https://www.youtube.com/watch?v=%s">Youtube로 보러 가기</a>', $youtube_id, $auto_option);
			}

				/*return sprintf("<center><iframe  width=\"%s\" height=\"%s\"  src=\"http://www.youtube-nocookie.com/embed/%s&amp;rel=0&amp;vq=hd1080&amp;%s&amp;color=white&amp;theme=light\" frameborder=\"0\" allowfullscreen=\"\"></iframe></center>",$width, $height,$youtube_id,$auto_option);*/
		}


		/* daum.net */
		// 신형
		elseif(preg_match('~(?:daum\.net/(?:user/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|daum\.net/)([^"&?/ ].*)~i', $src, $match))
		{	$daum_id = $match[1];
			// $daum_srl = "http://videofarm.daum.net/controller/video/viewer/Video.html?vid=".$daum_id;

		/*구형
		elseif(preg_match_all('/(daum\.net\/moviedetail\/moviedetailVideoView\.do\?movieId\=|daum\.net\/vod\/|daum\.net\/v\/|daum\.net\/flvPlayer\.swf\?vid\=|daum\.net\/controller\/video\/viewer\/Video\.html\?vid\=)(.*)/i',$src,$matches))
		{	// $daum_id = str_replace("/", "", substr($matches[2][0], 0, 12)); //11문자 또는 12문자이기에 마지막에 '/'문자를 제거해 주어야 한다
			$daum_id = split("&",$matches[2][0]);
			$daum_srl = "http://videofarm.daum.net/controller/player/VodPlayer.swf?vid=".$daum_id[0];
		*/

			//http://videofarm.daum.net/controller/player/VodPlayer.swf?vid=ve7cdGWG9eGV9MMTqFpPWpT&play_loc=undefined&autoPlay=true&profileName=HIGH&showPreAD=false&showPostAD=false

			return sprintf('<div class="trailer"><iframe src="http://videofarm.daum.net/controller/video/viewer/Video.html?vid=%s&amp;play_loc=undefined&amp;autoPlay=%s&amp;profileName=HIGH&amp;showPreAD=false&amp;showPostAD=false" scrolling="no" frameborder="0"></iframe></div>', $daum_id, $auto_start);
		}

		// 네이버영화 정보
		elseif(preg_match('/movie\.naver\.com.*code=([^#]*)/i', $src, $match))
		{	$naver_movie_info_id = $match[1];

			return sprintf('<div class="movie_info"><iframe src="//movie.naver.com/movie/bi/mi/basic.nhn?code=%s" scrolling="no" frameborder="0"></iframe></div>', $naver_movie_info_id);
		}
 
		// 네이버 tvcast
		elseif(preg_match('/rmcnmv\.naver\.com.*vid=(.*)/i', $src, $match))
		{	$naver_tvcast_id = $match[1];
			// 반응형 적용 불가능, 고정 픽셀 크기 사용
			// 현재 iframe 1080p 자동 재생 방법 찾지 못함 (iframe 하위 div width, height 계산되는 픽셀 무력화 시켜야 함
			//return sprintf('<div style="margin: 0 auto 10px; width: 1157px; height: 651px; box-shadow: 0px 1px 7px #48335e;"><iframe src="http://serviceapi.rmcnmv.naver.com/flash/outKeyPlayer.nhn?vid=%s&controlBarMovable=true&jsCallable=true&isAutoPlay=%s&skinName=tvcast_white" frameborder="no" scrolling="no" marginwidth="0" marginheight="0" width="1157" height="651" quality="high"></iframe></div>', $naver_tvcast_id, $auto_play_true);

			// 반응형 적용을 위해 직접 embed 코드 삽입
			/* 넣으면 출력 안됨
			&amp;api=http%3A//serviceapi.rmcnmv.naver.com/flash
			&amp;skinURL=http%3A//serviceapi.rmcnmv.naver.com/flash/getCommonPlayerSkin.nhn%3Fname%3Dtvcast_white
			&amp;skinName=tvcast_white // api url을 넣을 수 없으니 삽입해도 적용 불가능
			&amp;contentInfo=%5Bobject%20Object%5D
			&amp;socialInfoData=%5Bobject%20Object%5D
			&amp;customPlayButton=http%3A//serviceapi.rmcnmv.naver.com/resources/img/news_play_btn.png
			&amp;controls=%7B%22visible%22%3A%7B%22logo%22%3Atrue%7D%7D
			width="100%" height="100%" 
			*/

			/* rmcplayer3_launcher_20131018.js
			wmode : "window",
			wmode_outkey : "transparent",
			autoPlay : "",
			api : "",
			skinName : "default",
			coverImageURL : "",
			isResizableCoverImage : "",
			beginTime : "",
			hasRelativeMovie : "",
			isP2P : "",
			defaultResolution : "",
			limitHDResolution : "",
			defaultVolume : "",
			callbackHandler : "",
			ext : "",
			isPullingDownResolution : "",
			timeNoticeDisplayed : "",
			bufferFulledTime : "",
			limitTimeForDroppedFPS : "",
			droppedFPSDetected : "",
			droppedFPSMonitered : "",
			limitCountForDroppedFPS : "",
			objId : "",
			protocol : "",
			backgroundColor : "#000000",
			typeTimeFormat : "",
			playRelationVideo : "",
			reloadPage : "",
			cassiodServiceID : "",
			targetHost : "",
			serviceHost : "",
			controlBarMovable : "false",
			adMeta : "",
			apiAD : "",
			apiMusic : "",
			postkey : "",
			musicLogRoot : "",
			musicUrl : "",
			referrer : "",
			expand : "",
			autoLocale : false,
			locale : "ko",
			contentInfo : {
				title : "",
				category : "",
				categoryLink : null,
				recommendCount : 0
			},
			showSocialPlugIn : false,
			socialInfoData : {
				sourceUrl : "",
				service : {
					recommend : "off",
					bookmark : "off",
					me2post : "off",
					line : "off",
					facebook : "off",
					twitter : "off",
					band : "off",
					kakaotalk : "off",
					copyurl : "off"
				},
				callback : {
					recommend : null
				}
			},
			advertiseInfo : null,
			advertiseUrl : null
			*/
			// 1080p 수동 전환해야 하기때문에 현재 auto play 의미가 없으니 임시 모두 해제 처리
			return sprintf('<div class="trailer"><embed src="http://serviceapi.rmcnmv.naver.com/flash/getCommonPlayer.nhn" quality="high"  flashvars="vid=%s&amp;wmode=window&amp;wmode_outkey=transparent&amp;isAutoPlay=%s&amp;callbackHandler=onPlayerStatusChangeFlash&amp;ext=outService&amp;cassiodServiceID=NAVER&amp;controlBarMovable=true&amp;autoLocale=false&amp;locale=ko&amp;showSocialPlugIn=false&amp;jsCallable=true&amp;showVendor=false&amp;showContentInfo=true" allowscriptaccess="always" allowfullscreen="true" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed></div>', $naver_tvcast_id, $auto_play_true = "false");
		}

		// IMDB (현재 iframe 막혔음)
		elseif(preg_match_all('/(imdb\.com\/video\/imdb\/)(.*)/i', $src, $matches))
		{	// $imdb_id = str_replace("/", "", substr($matches[2][0], 0, 12)); //10문자 또는 12문자이기에 마지막에 '/'문자를 제거해 주어야 한다
			$imdb_id = split("/",$matches[2][0]);
			$imdb_srl = "http://www.imdb.com/video/imdb/".$imdb_id[0]."/html5";
			// http://www.imdb.com/video/imdb/vi4196837657/html5?mode=desktop&amp;format=720p&w=700&h=350
			// http://www.imdb.com/video/imdb/vi3476988953/imdbvideo?format=240p&type=single

			//&amp;wmode=opaque : iframe 일때 유투브의 레이어 순위를 최하단에 위치하게 하여 다른 레이어 위에 겹치지 않게 하는 코드

			return sprintf("<center><iframe width=\"%s\" height=\"%s\" src=\"%s?mode=desktop&amp;format=720p&amp;w=%s&amp;h=%s\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" scolling=\"no\"></iframe></center>", $width+180, $height+15, $imdb_srl, $width, $height);

			//return sprintf("<center><iframe width=\"%s\" height=\"%s\" src=\"%s?\/imdbvideo?format=720p&amp;type=single&amp;w=%s&amp;h=%s\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" scolling=\"no\"  style=\"margin-top:-105px\"></iframe></center>", $width, $height +100, $imdb_srl, $width, $height +100);
		}

		// Vimeo
		elseif(preg_match('/vimeo.com\/?.*\/(\d{8,11})(?:\W)?/', $src, $matches))
		{	$vimeo_id = $matches[1];

			return sprintf("<div style=\"position:relative;padding-bottom:56.25&#37\"><iframe style=\"position:absolute; width:100&#37;; height:100&#37;\"  src=\"http://player.vimeo.com/video/%s?title=1&amp;byline=0&amp;portrait=0&amp;color=ff9933&amp;%s&amp;loop=1\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" scolling=\"no\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>", $vimeo_id, $auto_option);
		}

		//Tudou 동영상
		elseif(preg_match('/tudou.com\/?.*\/([0-9a-zA-Z]{11})(?:\W)?/i', $src, $matches))
		{	$tudou_id = $matches[1];

			return sprintf("<div class=\"embed-responsive embed-responsive-16by9\"><embed src=\"http://www.tudou.com/v/%s/\&resourceId=0_05_02_99\&autoPlay=%s/v.swf\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" wmode=\"opaque\" width=\"700\" height=\"450\"></embed></div>", $tudou_id, $auto_start);
		}

		elseif(preg_match('/dailymotion\.com\/video\/(.*)/i', $src, $match))
		{	$dailymotion_id = reset(split('_', $match[1]));
			$dailymotion_srl ="//www.dailymotion.com/embed/video/".$dailymotion_id;

			return sprintf("<div style=\"position:relative;padding-bottom:56.25&#37\"><iframe style=\"position:absolute; width:100&#37;; height:100&#37;\"  src=\"%s?forcedQuality=auto&amp;autoplay=%s&amp;log\" frameborder=\"0\" scrolling=\"no\"></iframe></div>", $dailymotion_srl,$auto_start);
		}



		/*
		elseif(Context::getResponseMethod() != "XMLRPC")
		{
				 return sprintf("zzzzzzz<script type=\"text/javascript\" src=\"%sjwplayer.js\"></script><div id=\"%s\">Loading the player ...</div><script type=\"text/javascript\">jwplayer(\"%s\").setup({flashplayer: \"%splayer.swf\",file: \"%s\", width: %s, height: %s, autostart: %s, plugins:{\"hd-2\":{state : \"true\"}}});</script>", $this->component_path, $src, $src, $this->component_path, $src, $width, $height, $auto_start, $this->component_path);
		}*/

		elseif(Context::getResponseMethod() != "XMLRPC")
		{	return sprintf("<script type=\"text/javascript\">displayMultimedia(\"%s\", \"%s\",\"%s\", { \"autostart\" : %s, \"wmode\" : \"%s\" });</script>", $src, $width + 6 , $height + 8 , $auto_start, $wmode);
		}

		else return sprintf("<div style=\"width: %dpx; height: %dpx;\"><span style=\"position:relative; top:%dpx;left:%d\"><img src=\"%s\" /><br />Attached Multimedia</span></div>", $width, $height, ($height/2-16), ($width/2-31), Context::getRequestUri().'./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif');
	}
}

/* End of file multimedia_link.class.php */
/* Location: ./modules/editor/components/multimedia_link/multimedia_link.class.php */





/** 
* Author: Tomas Pavlatka [tomas.pavlatka@gmail.com] 
* Created: Sep 8, 2011 
*/ 
class YoutubeHelper
{	/*
	* Options (Private)
	*
	* holds options for helper
	*/
	var $_options = array
	(	'width'		=>	640,
		'height'	=>	390,
		'video_id'	=>	null
	);

	/*
	* Player Vars (Private)
	*
	* holds parameters for embedded player
	* @see http://code.google.com/apis/youtube/player_parameters.html?playerVersion=HTML5
	*/
	var $_playerVars = array
	(	'autohide'		=>	2,
		'autoplay'		=>	0,
		'controls'		=>	1,
		'enablejsapi'	=>	0,
		'loop'			=>	0,
		'origin'		=>	null,
		'start'			=>	null,
		'theme'			=>	'dark'
	);

	/*
	* iFrame Code
	*
	* holds code for iFrame Player
	*/
	var $_frameCode = null;

	/*
	* Init
	*
	* inits helper
	* @param array $options - option for helper
	* @param array $playerVars - parameters for embedded player
	*/
	function init(array $options = array(),array $playerVars = array())
	{	$this->_options = am($this->_options,$options);
		$this->_playerVars = am($this->_playerVars,$playerVars);
	}

	/**
	* iFrame Player
	*
	* creates script for iframe player and returns it back
	* @param string url - url of youtube video
	* @param string divId - id of div element
	*/
	function iframePlayer($url, $divId, $options)
	{	// Get video id
		// $this->_parseVideoId($url);
		// $yt_count = Context::get('yt_count');
		// Validation
		/*
		if(empty($this->_options['video_id']))
		//if(Context::get('yt_howmany') == 0)
		{	$this->_iframeCode = __('Video id cannot be left blank. Check url of youtube video.',true);
		}
		else if(!is_numeric($this->_options['width']) || $this->_options['width'] < 1)
		{	$this->_iframeCode = __('Width of video player must be numeric and greather than 1.',true);
		}
		else if(!is_numeric($this->_options['height']) || $this->_options['height'] < 1)
		{	$this->_iframeCode = __('Height of video player must be numeric and greather than 1.',true);
		}
		else
		*/
			// Build code
			$this->_iframeCode  = "\r\n".'<script type="text/javascript">'."\r\n";
			//if(count($divId) == 1) $this->_loadIframePlayer();

			// 반응형 CSS 코드
			//if($responsive == "true") 
			// 반응형으로 적용될 player ID 구하기
			$iframeIds = '';
			foreach($options as $key => $op) {
				//$yt_options[$yt_counter]["playerVars"] 
				if($op['responsive'] == "true") {
					$iframeIds = '#player'.$key.(($key>1)?',':'')." ".$iframeIds;
				}
			}
			$css_style = '<style type="text/css">'."\r\n".'.videowrapper { position: relative;	padding-bottom: 56.25%;	margin: 0 auto 10px; box-shadow: 0px 1px 7px #48335e;}'."\r\n".'.normalwrapper { position: relative; }'."\r\n".$iframeIds.'{ position: absolute; width: 100%; height: 100%; }'."\r\n".'</style>';
			//'<pre>'.print_r($xml_obj,true).'</pre>'.
			// 반응형 CSS HTML에 삽입하기
			Context::addHtmlHeader($css_style);

			$this->_loadIframePlayer();
			$this->_createIframePlayer($divId, $options);
			$this->_closeIframePlayer();
		
		// Return code
		return $this->_iframeCode;
	}

	/*
	* Close iFrame Player (Private)
	*
	* closes iframe player
	*/
	function _closeIframePlayer()
	{	$this->_iframeCode  .= '</script>'."\r\n";} 

	/*
	* Create iFrame Player
	*
	* creates iframe player
	* @param string divId - id of div element
	*/
	function _createIframePlayer($divIds, $options)
	{	/*// Build player params
		$params = null;
		foreach($this->_playerVars as $key => $value)
		{	if(is_numeric($value) || !empty($value))
			{	$params .= "'{$key}': ";
				if(is_numeric($value))
				{	$params .= $value;}
				else
				{	$params .= "'{$value}'";}
				$params .= ',';
			}
		} */

		// Build JS code
		foreach($divIds as $k => $divId)
		{	++$k;
			$this->_iframeCode .= 'var player'.$k.';'."\r\n";
		}

		$this->_iframeCode .= 'function onYouTubePlayerAPIReady() {'."\r\n";
		foreach($divIds as $k => $divId)
		{	++$k;
			$this->_iframeCode .= 'player'.$k.' = new YT.Player("player'.$k.'", {'."\r\n";
			//$this->_iframeCode .= 'height: "'.(int)$this->_options['height'].'",'."\r\n";
			$this->_iframeCode .= '	height: "'.(int)$options[$k]['height'].'",'."\r\n";
			//$this->_iframeCode .= 'width:  "'.(int)$this->_options['width'].'",'."\r\n";
			$this->_iframeCode .= '	width:  "'.(int)$options[$k]['width'].'",'."\r\n";
			//$this->_iframeCode .= 'videoId: "'.$this->_options['video_id'].'",'."\r\n";
			$this->_iframeCode .= '	videoId: "'.$divId.'",'."\r\n";

			/*if(!empty($params))
			{	$this->_iframeCode .= 'playerVars: {'.substr($params,0,-1).'},'."\r\n";}
			*/

			// Build player params
			$options_params = null;
			foreach($options[$k]["playerVars"] as $key => $value)
			{	if(is_numeric($value) || !empty($value))
				{	$options_params .= "'{$key}': ";

					/*if(is_numeric($value)) $options_params .= $value;
					else $options_params .= "'{$value}'";
					*/

					//$options_params .= (is_numeric($value)) ? $value :"'{$value}'";

					if(is_numeric($value))
					{	$options_params .= $value;}
					else
					{	$options_params .= "'{$value}'";}
					$options_params .= ',';
				}
			}

			if(!empty($options_params))
			{	$this->_iframeCode .= '	playerVars: {'.substr($options_params,0,-1).'},'."\r\n";}
			// event 추가
			$this->_iframeCode .="	events: {'onReady':onPlayerReady".$k.",'onStateChange':onPlayerStateChange".$k."}"."\r\n";
			$this->_iframeCode .= '	});'."\r\n\r\n";
		}
		$this->_iframeCode .= '}'."\r\n\r\n";

		// 동영상 재생 함수
		// 참고 https://developers.google.com/youtube/iframe_api_reference?hl=ko
		foreach($divIds as $k => $divId)
		{	++$k;
			$this->_iframeCode .=
				"function onPlayerReady".$k."(event)
				{	//event.target.playVideo('player".$k."');
					player".$k.".setVolume(".$options[$k]['event']['volume'].");
					player".$k.".setPlaybackQuality('hd1080');"."\r\n";

			$this->_iframeCode .=
				"}"."\r\n\r\n";
			$this->_iframeCode .=
				"var done".$k." = false;";
			$this->_iframeCode .=
				"function onPlayerStateChange".$k."(event)
				{	if (event.data == YT.PlayerState.PLAYING && !done".$k.")
					{	//setTimeout(stopVideo".$k.", 6000);
							done".$k." = true;
					}
				}"."\r\n\r\n";

			$this->_iframeCode .=
				"function stopVideo".$k."()
				{	player".$k.".stopVideo();}"."\r\n\r\n";
		}
	}

	/*
	* Load iFrame Player (Private)
	*
	* starts building iframe player code
	*/
	function _loadIframePlayer()
	{	$this->_iframeCode .=
			'var tag = document.createElement("script");'."\r\n";
		$this->_iframeCode .=
			'tag.src = "http://www.youtube.com/player_api"'."\r\n";
		$this->_iframeCode .=
			'var firstScriptTag = document.getElementsByTagName("script")[0]'."\r\n";
		$this->_iframeCode .=
			'firstScriptTag.parentNode.insertBefore(tag, firstScriptTag)'."\r\n\r\n";
	}

	/*
	* Parse Video Id (Private)
	*
	* parses video id from url
	* @param string $url - url from youtube
	*/
	function _parseVideoId($url)
	{	//http://www.youtube.com/watch?v=UF6wdrRAZug&feature=relmfu
		$urlQuery = parse_url($url, PHP_URL_QUERY);
		if(!empty($urlQuery))
		{	$parseArray = explode('&', $urlQuery);
			foreach($parseArray as $key => $value)
			{	$explodeArray = explode('=', $value);
				if($explodeArray[0] == 'v' && isset($explodeArray[1]))
				{	$this->_options['video_id'] = (string)$explodeArray[1];
					break;
				}
			}
		}
	}
}
