<?php
session_start();

define('unisitecms', true);

$config = require "config.php";
include_once("systems/unisite.php");

$Ads = new Ads();
$Blog = new Blog();
$Elastic = new Elastic();
$CategoryBoard = new CategoryBoard();

$count = (int)$_GET["count"] ? (int)$_GET["count"] : 20;
$content = clear($_GET["content"]);
$turbo = (int)$_GET["turbo"];
$sort = clear($_GET["sort"]);
$title = clear(urldecode($_GET["title"]));
$desc = clear(urldecode($_GET["desc"]));
$id_metrics = clear($_GET["id_metrics"]);

if( !$turbo ){

$out = '<?xml version="1.0"?> <rss version="2.0"> <channel>';
$out .= '<title>'.$title.'</title>';
$out .= '<description>'.$desc.'</description>';
$out .= '<link>'.$config["urlPath"].'</link>';
 
$out .= '
<image>
	<url>'.$settings["logotip"].'</url>
	<title>'.$title.'</title>
	<link>'.$config["urlPath"].'</link>
</image>
';

}else{

$out = '<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:yandex="http://news.yandex.ru" 
	 xmlns:media="http://search.yahoo.com/mrss/" 
	 xmlns:turbo="http://turbo.yandex.ru" 
	 version="2.0">
<channel>
';
$out .= '<title>'.$title.'</title>';
$out .= '<description>'.$desc.'</description>';
$out .= '<link>'.$config["urlPath"].'</link>';
$out .= '<language>ru</language>';
$out .= '<turbo:analytics id="'.$id_metrics.'" type="Yandex" params=""></turbo:analytics>'; 

}

if( $content == "blog" ){

	$query = "blog_articles_visible='1'";

	if( $sort == "news" ){
        $sort = 'ORDER By blog_articles_id DESC';
	}elseif( $sort == "rand" ){
		$sort = 'ORDER By Rand()';
	}else{
		$sort = 'ORDER By blog_articles_id DESC';
	}

	$getCategoryBlog = $Blog->getCategories("where blog_category_visible=1");

	if( intval($_GET["category"]) ){
		$category = idsBuildJoin( $Blog->idsBuild(intval($_GET["category"]), $getCategoryBlog), intval($_GET["category"]) );
		$query .= " and blog_articles_id_cat IN(".$category.")";
	}

	$data = $Blog->getAll( array("query"=>$query, "sort"=>$sort . " limit {$count}") );

	if( $data["count"] ){

        foreach ($data["all"] as $key => $value) {

        	$date = date(DATE_RFC822, strtotime($value['blog_articles_date_add']));
            
            if( !$turbo ){

				$out .= '
				<item>
					<title>' . str_replace(array("&"),array("-"),trim($value['blog_articles_title'])) . '</title>
					<link>' . $Blog->aliasArticle( $value ) . '</link>
					<description><![CDATA[' . custom_substr(urldecode($value["blog_articles_desc"]), 150, "...") . ']]></description>
					<category>' . $value['blog_category_name'] . '</category>
					<guid>' . $Blog->aliasArticle( $value ) . '</guid>
					<pubDate>' .$date. '</pubDate>
				</item>'; 

			}else{

				$out .= '
				<item turbo="true">		
					<link>' . $Blog->aliasArticle( $value ) . '</link>
					<category>'.$value['blog_category_name'].'</category>
					<pubDate>'.$date.'</pubDate>
					<turbo:content>
						<![CDATA[
							<header>
								<h1>' . $value['blog_articles_title'] . '</h1>
						        <figure>
						            <img src="'.Exists($config["media"]["big_image_blog"],$value["blog_articles_image"],$config["media"]["no_image"]).'"/>
						        </figure>								
							</header>
							' . urldecode($value['blog_articles_text']) . '
						]]>
					</turbo:content>			
				</item>
				';			

			}    

        }

	}

}elseif( $content == "ads" ){

	$query = "ads_status='1' and clients_status IN(0,1) and ads_period_publication > now()";
	
	if( $sort == "news" ){
        $sort = 'ORDER By ads_id DESC';
	}elseif( $sort == "rand" ){
		$sort = 'ORDER By Rand()';
	}else{
		$sort = 'ORDER By ads_id DESC';
	}

	$getCategoryBoard = $CategoryBoard->getCategories("where category_board_visible=1");

	if( intval($_GET["category"]) ){
		$category = idsBuildJoin( $CategoryBoard->idsBuild(intval($_GET["category"]), $getCategoryBoard), intval($_GET["category"]) );
		$query .= " and ads_id_cat IN(".$category.")";
	}

	$data = $Ads->getAll( ["sort"=>$sort . " limit {$count}" , "query"=>$query ] );

	if( $data["count"] ){
		foreach ($data["all"] as $value) {

			$value = $Ads->getDataAd($value);

			$image = $Ads->getImages($value["ads_images"]);

			$date = date(DATE_RFC822, strtotime($value['ads_datetime_add']));
            
            if( !$turbo ){

				$out .= '
				<item>
					<title>' . str_replace(array("&"),array("-"),trim($value['ads_title'])) . '</title>
					<link>' . $Ads->alias( $value ) . '</link>
					<description><![CDATA[' . custom_substr($value["ads_text"], 150, "...") . ']]></description>
					<category>' . $value['category_board_name'] . '</category>
					<guid>' . $Ads->alias( $value ) . '</guid>
					<pubDate>' .$date. '</pubDate>
				</item>';

		    }else{

				$out .= '
				<item turbo="true">		
					<link>' . $Ads->alias( $value ) . '</link>
					<category>'.$value['category_board_name'].'</category>
					<pubDate>'.$date.'</pubDate>
					<turbo:content>
						<![CDATA[
							<header>
								<h1>' . $value['ads_title'] . '</h1>
						        <figure>
						            <img src="'.Exists($config["media"]["big_image_ads"],$image[0],$config["media"]["no_image"]).'"/>
						        </figure>								
							</header>
							' . $value['ads_text'] . '
						]]>
					</turbo:content>			
				</item>
				';

		    }

		}
	}

}

$out .= '</channel>';
$out .= '</rss>';
 
header('Content-Type: text/xml; charset=utf-8');
echo $out;  
?> 
