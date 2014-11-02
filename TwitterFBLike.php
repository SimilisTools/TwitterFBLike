<?php
/*
 * Parser that inserts Twitter and Facebook "Like" buttons on a page
 *
 * For more info see http://mediawiki.org/wiki/Extension:TwitterFBLike
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Barry Coughlan
 * @copyright © 2012 Barry Coughlan
 * modified Toniher
 * @licence GNU General Public Licence 2.0 or later
 */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'TwitterFBLike', 
	'author' => 'Barry Coughlan', 
	'url' => 'http://mediawiki.org/wiki/Extension:TwitterFBLike',
	'description' => 'Template that inserts Twitter and Facebook "Like" buttons on a page',
);

$wgHooks['ParserFirstCallInit'][] = 'twitterFBLikeParserFunction_Setup';
$wgHooks['LanguageGetMagic'][]       = 'twitterFBLikeParserFunction_Magic';
$wgHooks['BeforePageDisplay'][] = 'twitterFBLikeParserFeedHead'; # Setup function

$wgTwitterFBLikeTweetName = "Tweet";
$wgTwitterFBLikeFacebookID = "";

function twitterFBLikeParserFunction_Setup( &$parser ) {
	# Set a function hook associating the "twitterFBLike_parser" magic word with our function
	$parser->setFunctionHook( 'twitterFBLike', 'twitterFBLikeParserFunction_Render' );
	return true;
}
 
function twitterFBLikeParserFunction_Magic( &$magicWords, $langCode ) {
        //Set first parameter to 1 to make it case sensitive
		$magicWords['twitterFBLike'] = array( 0, 'TwitterFBLike' );
        return true;
}

function twitterFBLikeParserFeedHead(&$out, &$sk) {
	global $wgScriptPath;
	$out->addHeadItem('twitterFBLike.css','<link rel="stylesheet" type="text/css" href="'.$wgScriptPath.'/extensions/TwitterFBLike/TwitterFBLike.css"/>');
	return $out;
}

 
function twitterFBLikeParserFunction_Render( &$parser, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = "", $param6 ) {
		global $wgSitename;
		global $wgTwitterFBLikeFacebookID;
		global $wgTwitterFBLikeTweetName;
		
		$show = array( "twitter", "facebook" );
		$via = "";
		$count = "data-count='horizontal'";
	
		// Location
		if ($param1 === "left" || $param1 === "right") {
			$float = $param1;
		} else {
			$float = "none";
		}
		
		// size
		if ($param2 === "small") {
			$twitterextra="";
			$size="small";
			$linebreak = "";
			$layout = "button_count";
			$height = "21";
		} else {
			$twitterextra="data-count=\"vertical\"";
			$size="big";
			$layout = "box_count";
			$linebreak = "<br />";
			$height = "65";
		}
		
		// Extra
		$extra = explode(",", $param3);
		if ( in_array( "like", $extra ) ) {
			$width = 75;
			$action="like";
		} else {
			$width = 115;
			$action="recommend";
		}

		foreach ( $extra as $ex ) {
			if ( preg_match( "/via=/", $ex ) ) {
				$viaparams = explode("=", trim($ex), 2);
				if ( key_exists( 1, $viaparams ) ) {
					$via = "data-via=".$viaparams[1];
				}
			}
			
			if ( preg_match( "/count=/", $ex ) ) {
				$countparams = explode("=", trim($ex), 2);
				if ( key_exists( 1, $countparams ) ) {
					$count = "data-count=".$countparams[1];
				}
			}
		}
		
		//Get page title and URL
		$title = $parser->getTitle();
		if (!$title) return "";
		$urltitle = $title->getPartialURL(); //e.g. "Main_Page"
		$url = $title->getFullURL();
		if (!$url ) return "";

		// Text to share
		$text = str_replace("\"", "\\\"", $wgSitename . ": " . $title->getFullText());
		if ( !empty( $param4 ) ) {
			$text = $param4;
			$text = str_replace("\"", "\\\"", $text);
		}
		
		$FBappID = "";
		
		if ( !empty( $wgTwitterFBLikeFacebookID ) ) {
			$FBappID = "app_id=".$wgTwitterFBLikeFacebookID."&amp;";
		}
		
		// Services
		if ( !empty( $param5 ) ) {
			$show = explode( ",", $param5 );
		}
		
		// URL
		if ( !empty( $param6 ) ) {
			$extra_url = $param6;
			$extra_url = str_replace(" ", "_", $extra_url);
			$url = $url."#".$extra_url;
			// TODO: Deal with http://www.utf8-chartable.de/
		}
		
		// TODO: Need improve a lot considering Twitter: https://dev.twitter.com/web/tweet-button
		
		$twitter = "";
		$facebook = "";

		if ( in_array( "twitter", $show ) ) {
			$twitter.="<a style='display: none' href='http://twitter.com/share' class='twitter-share-button' data-text='$text' $via $count data-url='$url' $twitterextra>".$wgTwitterFBLikeTweetName."</a>";
		}
		
		if ( in_array( "facebook", $show ) ) {
			$facebook.= "
				<iframe src='http://www.facebook.com/plugins/like.php?".$FBappID."href=${url}&layout=${layout}&show_faces=false&send=false&width=450&amp;action=$action&colorscheme=light&height=65'
					scrolling='no' frameborder='0' class='fb-like' style='width:${width}px; height: ${height}px;' allowTransparency='true'>
				</iframe>
			";
		}
		
		$output = "<div class='twitterFBLike_$size' twitterFBLike_$urltitle' style='float: ${float}'>".$twitter.$facebook."<script src='http://platform.twitter.com/widgets.js' type='text/javascript'></script></div>";

		

		
		return $parser->insertStripItem($output, $parser->mStripState);
}