<?php
/*
Plugin Name: Tweetly Updater
Plugin URI: http://www.zepan.org/software/tweetly-updater
Description: Updates Twitter when you create or edit a blog entry, uses bit.ly for short urls
Version: 1.0
Author: Michael Zehrer
Author URI: http://zepan.org
*/

/*
Copyright 2008  Michael Zehrer  (email : zehrer@zepan.net)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*
Based on ideas and work by:
Ingo "Ingoal" Hildebrandt, edited by Marco Luthe
http://www.ingoal.info/archives/2008/07/08/twitter-updater/
Victoria Chan
http://blog.victoriac.net/?p=87
*/

require "tweetly_updater_api.inc";

function triggerTweet($post_ID)  {
	
	if(!function_exists('json_decode') || !function_exists('curl_exec')) {
		error_log("Can not tweet, essential php functions (json, curl) missing");
		return $post_ID;
	}
	
	$thisPost = get_post($post_ID);
        
	$thisposttitle = $thisPost->post_title;
	$thispostlink = get_permalink($post_ID);
	
	$tweetlyUpdater = new TweetlyUpdater(get_option('tweetlyUpdater_twitterlogin'), get_option('tweetlyUpdater_twitterpw'), get_option('tweetlyUpdater_bitlyuser'), get_option('tweetlyUpdater_bitlyapikey'));
	
	if (!$tweetlyUpdater->twitterVerifyCredentials()) {
		error_log("Twitter login failed");
		add_action('admin_notices',showAdminMessage("Twitter login failed, the update for this post was not successful.", true));	
		return $post_ID;
	}
	
        $shortlink = $tweetlyUpdater->getBitlyUrl($thispostlink);
	
	$sentence = "";
        
        $thisPostPreviousStatus = $_POST['prev_status'];
	        
        if ($thisPostPreviousStatus == 'publish') {
            if(get_option('oldpost-edited-update') == '1') {
                $sentence = get_option('oldpost-edited-text');
                if (strlen(trim($thisposttitle)) == 0) {
                        $post = get_post($post_ID);
                        if ($post) {
                                $thisposttitle = $post->post_title;
                        }
                }
                if(get_option('oldpost-edited-showlink') == '1') {
                        $thisposttitle = $thisposttitle . ' @' . $shortlink;
                }
                $sentence = str_replace ( '#title#', $thisposttitle, $sentence);
            }
        } else {
            if(get_option('newpost-published-update') == '1'){
                $sentence = get_option('newpost-published-text');
                if(get_option('newpost-published-showlink') == '1'){
                    $thisposttitle = $thisposttitle . ' @' . $shortlink;
                }
                $sentence = str_replace ( '#title#', $thisposttitle, $sentence);
            }
        }
        
	if($sentence != ""){
		$status = utf8_encode($sentence);
		$res = $tweetlyUpdater->twitterUpdate($status);
		if ($res == null) {
			error_log("Twitter update failed");
		}
	}
	return $post_ID;
}

function addTweetlyOptionsPage() {
    if (function_exists('add_options_page')) {
		add_options_page('Tweetly Updater', 'Tweetly Updater', 8, __FILE__, 'showTweetlyOptionsPage');
    }
}

function showTweetlyOptionsPage() {
    include(dirname(__FILE__).'/tweetly_updater_options.php');
}

function showAdminMessage($message, $error = false) {
	global $wp_version;

	echo '<div '
		. ( $error ? 'style="border:1px solid red;" ' : '')
		. 'class="updated fade"><p><strong>'
		. $message
		. '</strong></p></div>';
}

add_action('publish_post', 'triggerTweet');
add_action('admin_menu', 'addTweetlyOptionsPage');
?>
