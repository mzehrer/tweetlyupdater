<?php
/*
 Plugin Name: Tweetly Updater
 Plugin URI: http://www.zepan.org/software/tweetly-updater
 Description: Updates Twitter when you create or edit a blog entry, uses bit.ly for short urls
 Version: 1.3.0
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
@session_start();
if (!class_exists('TwitterOAuth')) {
    require_once('twitteroauth/twitteroauth.php');
}
require_once "tweetly_updater_api.inc";

function triggerEditTweet($post_ID) {
    return triggerTweet($post_ID, true);
}

function triggerTweet($post_ID, $isedit = false) {

    if (!function_exists('json_decode') || !function_exists('curl_exec')) {
        error_log("Can not tweet, essential php functions (json, curl) missing");
        return $post_ID;
    }

    $thisPost = get_post($post_ID);

    $thisposttitle = $thisPost->post_title;
    $thispostlink = get_permalink($post_ID);

    $tweetlyUpdater = new TweetlyUpdater(get_option('tweetlyUpdater_oauthToken'), get_option('tweetlyUpdater_oauthTokenSecret'), get_option('tweetlyUpdater_bitlyuser'), get_option('tweetlyUpdater_bitlyapikey'));

    if (!$tweetlyUpdater->twitterVerifyCredentials()) {
        error_log("Twitter login failed");
        add_action('admin_notices', showAdminMessage("Twitter login failed, the update for this post was not successful.", true));
        return $post_ID;
    }

    $buildlink = false;
    $titleTemplate = "";

    $category = null;
    $categories = get_the_category($post_ID);
    if ($categories > 0) {
        $category = $categories[0]->cat_name;
    }


    error_log("Post type: " . $thisPost->post_type);
    if ($isedit) {
        error_log("This is an update");
        if (get_option('tweetlyUpdater_oldpost-edited-update') == '1') {
            error_log("tweetlyUpdater_oldpost-edited-skippages: " . get_option('tweetlyUpdater_oldpost-edited-skippages'));
            if ($thisPost->post_type == "page" and  get_option('tweetlyUpdater_oldpost-edited-skippages') == '1') {
                return $post_ID;
            }

            $titleTemplate = get_option('tweetlyUpdater_oldpost-edited-text');
            if (strlen(trim($thisposttitle)) == 0) {
                $post = get_post($post_ID);
                if ($post) {
                    $thisposttitle = $post->post_title;
                }
            }
            if (get_option('tweetlyUpdater_oldpost-edited-showlink') == '1') {
                $buildlink = true;
            }
        } else {
            return $post_ID;
        }
    } else {
        error_log("This is a new post");
        error_log("tweetlyUpdater_newpost-published-skippages: " . get_option('tweetlyUpdater_newpost-published-skippages'));
        if ($thisPost->post_type == "page" and  get_option('tweetlyUpdater_newpost-published-skippages') == '1') {
            return $post_ID;
        }

        if (get_option('tweetlyUpdater_newpost-published-update') == '1') {
            $titleTemplate = get_option('tweetlyUpdater_newpost-published-text');
            if (get_option('tweetlyUpdater_newpost-published-showlink') == '1') {
                $buildlink = true;
            }
        } else {
            return $post_ID;
        }
    }


    $shortlink = null;
    if ($buildlink) {
        $shortlink = $tweetlyUpdater->getBitlyUrl($thispostlink);
    }

    $hashtags = null;
    if (get_option('tweetlyUpdater_usehashtags') == '1') {

        if (get_option('tweetlyUpdater_usehashtags-cats') == '1') {
            $categories = get_the_category($post->ID);
            if ($categories) {
                foreach ($categories as $cat) {
                    $hashtags .= '#' . str_replace(" ", "", strtolower($cat->cat_name)) . ' ';
                }
            }
        }

        if (get_option('tweetlyUpdater_usehashtags-tags') == '1') {
            $tags = get_the_tags($post->ID);
            if ($tags) {
                foreach ($tags as $tag) {
                    $hashtags .= '#' . str_replace(" ", "", strtolower($tag->name)) . ' ';
                }
            }
        }

        if (get_option('tweetlyUpdater_usehashtags-static')) {
            $hashtags .= ' ' . get_option('tweetlyUpdater_usehashtags-static');
        }

        $hashtags = prepare_text($hashtags);
    }


    $status = buildTwitterStatus($titleTemplate, $thisposttitle, $category, $shortlink, trim($hashtags));
    if ($status) {
        $res = $tweetlyUpdater->twitterUpdate($status);
        if ($res == null) {
            error_log("Twitter update failed");
        } else {
            if ($buildlink) {
                if (!add_post_meta($post_ID, "tweetlyUpdater_bitlyUrl", $shortlink, false))
                    error_log("Could not add bitly url to meta data");
            }
        }
    }
    return $post_ID;
}

function buildTwitterStatus($titleTemplate, $thisposttitle, $thispostfirstcategory, $link, $hashtags) {

    $maxStatusLength = 140;

    if ($link) {
        $maxStatusLength = $maxStatusLength - (strlen($link) + 1);
    }

    if ($hashtags) {
        $maxStatusLength = $maxStatusLength - (strlen($hashtags) + 1);
    }

    if (ereg("#title#", $titleTemplate)) {
        $status = str_replace('#title#', $thisposttitle, $titleTemplate);
    }
    if (ereg("#firstcategory#", $titleTemplate)) {
        if ($thispostfirstcategory && $thispostfirstcategory != "Uncategorized") {
            $status = str_replace('#firstcategory#', $thispostfirstcategory, $status);
        } else {
            $status = str_replace('#firstcategory#', "", $status);
        }
    }
    $status = trim_text($status, $maxStatusLength, true, true);

    if ($link) {
        $status = $status . ' ' . $link;
    }

    if ($hashtags) {
        $status = $status . ' ' . $hashtags;
    }

    return $status;
}

function trim_text($input, $length, $ellipses = true, $strip_html = true) {

    if ($ellipses) {
        $length = $length - 3;
    }

    if ($strip_html) {
        $input = prepare_text($input);
    }

    if (strlen($input) <= $length) {
        return $input;
    }

    $last_space = strrpos(substr($input, 0, $length), ' ');
    $trimmed_text = substr($input, 0, $last_space);

    if ($ellipses) {
        $trimmed_text .= '...';
    }

    return $trimmed_text;
}

function prepare_text($input) {

    $input = trim($input);
    $input = html_entity_decode($input);
    $input = strip_tags($input);

    return $input;
}


function addTweetlyOptionsPage() {
    if (function_exists('add_options_page')) {
        add_options_page('Tweetly Updater', 'Tweetly Updater', 8, __FILE__, 'showTweetlyOptionsPage');
    }
}

function showTweetlyOptionsPage() {
    include(dirname(__FILE__) . '/tweetly_updater_options.php');
}

function showAdminMessage($message, $error = false) {
    global $wp_version;

    echo '<div '
            . ($error ? 'style="border:1px solid red;" ' : '')
            . 'class="updated fade"><p><strong>'
            . $message
            . '</strong></p></div>';
}

add_action('future_to_publish', 'triggerTweet', 10, 2);
add_action('new_to_publish', 'triggerTweet', 10, 2);
add_action('draft_to_publish', 'triggerTweet', 10, 2);
add_action('pending_to_publish', 'triggerTweet', 10, 2);

add_action('publish_to_publish', 'triggerEditTweet', 10, 1);

add_action('admin_menu', 'addTweetlyOptionsPage');
?>
