<?php
@session_start();
if (!class_exists('TwitterOAuth')) {
    require_once('twitteroauth/twitteroauth.php');
}
require_once('config.php');

$prereqOK = true;

if (!function_exists('json_decode')) {
    error_log("Function json_decode not found");
    add_action('admin_notices', showAdminMessage('Your php installation doesn\'t support the <a href="http://us.php.net/json_decode">json_decode</a> functionality.', true));
    $prereqOK = false;
}

if (!function_exists('curl_exec')) {
    error_log("Function curl_exec not found");
    add_action('admin_notices', showAdminMessage('Your php installation doesn\'t include <a href="http://us.php.net/manual/en/ref.curl.php">curl</a> support.', true));
    $prereqOK = false;
}

if (get_option('tweetlyUpdater_initialised') != '1') {
    update_option('tweetlyUpdater_newpost-published-update', '1');
    update_option('tweetlyUpdater_newpost-published-text', 'Published a new blog post: #title#');
    update_option('tweetlyUpdater_newpost-published-showlink', '1');
    update_option('tweetlyUpdater_newpost-published-skippages', '1');

    update_option('tweetlyUpdater_oldpost-edited-update', '1');
    update_option('tweetlyUpdater_oldpost-edited-text', 'Fiddling with my blog post: #title#');
    update_option('tweetlyUpdater_oldpost-edited-showlink', '1');
    update_option('tweetlyUpdater_oldpost-edited-skippages', '1');

    update_option('tweetlyUpdater_bitlyuser', '');
    update_option('tweetlyUpdater_bitlyapikey', '');

    update_option('tweetlyUpdater_usehashtags', '');
    update_option('tweetlyUpdater_usehashtags-cats', '');
    update_option('tweetlyUpdater_usehashtags-tags', '');
    update_option('tweetlyUpdater_usehashtags-static', '');

    update_option('tweetlyUpdater_initialised', '1');

}

if ($_POST['submit-type'] == 'options') {
    update_option('tweetlyUpdater_newpost-published-update', $_POST['newpost-published-update']);
    update_option('tweetlyUpdater_newpost-published-text', $_POST['newpost-published-text']);
    update_option('tweetlyUpdater_newpost-published-showlink', $_POST['newpost-published-showlink']);
    update_option('tweetlyUpdater_newpost-published-skippages', ($_POST['newpost-published-skippages'] == 1) ? 1 : 0);

    update_option('tweetlyUpdater_oldpost-edited-update', $_POST['oldpost-edited-update']);
    update_option('tweetlyUpdater_oldpost-edited-text', $_POST['oldpost-edited-text']);
    update_option('tweetlyUpdater_oldpost-edited-showlink', $_POST['oldpost-edited-showlink']);
    update_option('tweetlyUpdater_oldpost-edited-skippages', ($_POST['oldpost-edited-skippages'] == 1) ? 1 : 0);

    update_option('tweetlyUpdater_usehashtags', $_POST['usehashtags']);
    update_option('tweetlyUpdater_usehashtags-cats', $_POST['usehashtags-cats']);
    update_option('tweetlyUpdater_usehashtags-tags', $_POST['usehashtags-tags']);
    update_option('tweetlyUpdater_usehashtags-static', $_POST['usehashtags-static']);

    add_action('admin_notices', showAdminMessage("Post options saved.", false));
} else if ($_POST['submit-type'] == 'bitly') {
    if (($_POST['bitlyuser'] != '') AND ($_POST['bitlyapikey'] != '')) {
        update_option('tweetlyUpdater_bitlyuser', $_POST['bitlyuser']);
        update_option('tweetlyUpdater_bitlyapikey', $_POST['bitlyapikey']);
    } else {
        add_action('admin_notices', showAdminMessage("You need to provide your bit.ly login and api key!", true));
    }
    add_action('admin_notices', showAdminMessage("bit.ly options saved.", false));
}

error_log("tweetlyUpdater_newpost-published-skippages: " . get_option('tweetlyUpdater_newpost-published-skippages'));
error_log("tweetlyUpdater_oldpost-edited-skippages: " . get_option('tweetlyUpdater_oldpost-edited-skippages'));


if (isset($_REQUEST['oauth_token']) && isset($_REQUEST['oauth_verifier'])) {
    if ($_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
        $_SESSION['oauth_status'] = 'oldtoken';
        add_action('admin_notices', showAdminMessage("Could not bind to your twitter account, please try again!", true));
    } else {

        /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
        $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

        /* Request access tokens from twitter */
        $token_credentials = $connection->getAccessToken($_REQUEST['oauth_verifier']);

        update_option('tweetlyUpdater_oauthToken', $token_credentials['oauth_token']);
        update_option('tweetlyUpdater_oauthTokenSecret', $token_credentials['oauth_token_secret']);

        /* Remove no longer needed request tokens */
        unset($_SESSION['oauth_token']);
        unset($_SESSION['oauth_token_secret']);

        /* If HTTP response is 200 continue otherwise send to connect page to retry */
        if (200 == $connection->http_code) {
            add_action('admin_notices', showAdminMessage("Tweetly Updater is now connected to your Twitter account.", true));
        } else {
            add_action('admin_notices', showAdminMessage("Could not bind to your twitter account, please try again!", true));
        }

    }
}

function vc_checkCheckbox($theFieldname) {
    if (get_option($theFieldname) == '1') {
        echo('checked="true"');
    }
}

?>
<style type="text/css">
    fieldset {
        margin: 20px 0;
        border: 1px solid #cecece;
        padding: 15px;
    }
</style>



<div class="wrap">
    <h2>Your Tweetly Updater options</h2>

<?php if ($prereqOK) { ?>

    <h3>Your Twitter account details</h3>

<?php $tweetlyUpdater = new TweetlyUpdater(get_option('tweetlyUpdater_oauthToken'), get_option('tweetlyUpdater_oauthTokenSecret'), get_option('tweetlyUpdater_bitlyuser'), get_option('tweetlyUpdater_bitlyapikey')); ?>

    <fieldset>
        <legend>Twitter login</legend>
        <form method="post">
            <div>
            <?php
            if (!$tweetlyUpdater->twitterVerifyCredentials()) {
                add_action('admin_notices', showAdminMessage("You twitter login could not be verified!" . false));
                $callBackUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?page=tweetly-updater/tweetly_updater.php');
                ?>
                    <p>
                    <?php
                    echo('<a href="' . WP_PLUGIN_URL . '/tweetly-updater/redirect.php?callback=' . $callBackUrl . '">');
                    echo('<img src="' . WP_PLUGIN_URL . '/tweetly-updater/images/lighter.png" alt="Sign in with Twitter"/>');
                    echo('</a>');
                    ?>
                    </p>
                    <p>
                        <strong>Don't have a Twitter account? <a href="http://www.twitter.com">Get one for free
                            here!</a></strong>
                    </p>
                <?php

            } else {
                ?><p>Tweetly Updater is successfully connected to your Twitter account. Manage your application
                    connections <a href="http://twitter.com/settings/connections">here</a>.</p><?php

            }
            ?>
            </div>
        </form>
    </fieldset>

    <h3>bit.ly settings</h3>
    <fieldset>
        <legend>bit.ly account data</legend>
        <form method="post">
            <div>
            <?php
            if (!$tweetlyUpdater->bitlyVerifyCredentials()) {
                add_action('admin_notices', showAdminMessage("Your bit.ly login could not be verified!", true));
            }
            ?>
                <p>
                    <label for="bitlyuser">Your bit.ly username:</label><br/>
                    <input type="text" name="bitlyuser" id="bitlyuser"
                           value="<?php echo(get_option('tweetlyUpdater_bitlyuser')) ?>"/>
                </p>

                <p>
                    <label for="bitlyapikey">Your bit.ly api key:</label><br/>
                    <input type="text" name="bitlyapikey" id="bitlyapikey"
                           value="<?php if (get_option('tweetlyUpdater_bitlyapikey') != '') {
                               echo("-- not shown --");
                           }?>"/>
                </p>
                <input type="hidden" name="submit-type" value="bitly">

                <p><input type="submit" name="submit" class="button-primary" value="save login"/></p>

                <p><strong>Don't have a bit.ly account? <a href="http://bit.ly/">Sign up!</a></strong></p>
            </div>
        </form>
    </fieldset>

    <h3>Post settings</h3>

    <p>
        You can use placholders in your tweet templates.
    <ul>
        <li>#title# - Placeholder for page title</li>
        <li>#firstcategory# - The name of the first category</li>
    </ul>
    </p>

    <form method="post">
        <div>
            <fieldset>
                <legend>New post published</legend>
                <p>
                    <input type="checkbox" name="newpost-published-update" id="newpost-published-update"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_newpost-published-update')?> />
                    <label for="newpost-published-update">Update Twitter when the new post is published</label>
                </p>

                <p>
                    <label for="newpost-published-text">Text for this Twitter update</label><br/>
                    <input type="text" name="newpost-published-text" id="newpost-published-text" size="60"
                           maxlength="146" value="<?php echo(get_option('tweetlyUpdater_newpost-published-text')) ?>"/>
                    &nbsp;&nbsp;
                    <input type="checkbox" name="newpost-published-showlink" id="newpost-published-showlink"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_newpost-published-showlink')?> />
                    <label for="newpost-published-showlink">Link title to blog?</label>
                </p>

                <p>
                    <input type="checkbox" name="newpost-published-skippages" id="newpost-published-skippages"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_newpost-published-skippages')?> />
                    <label for="newpost-published-skippages">Skip pages</label>
                </p>
            </fieldset>
            <fieldset>
                <legend>Existing posts</legend>
                <p>
                    <input type="checkbox" name="oldpost-edited-update" id="oldpost-edited-update"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_oldpost-edited-update')?> />
                    <label for="oldpost-edited-update">Update Twitter when the an old post has been edited</label>
                </p>

                <p>
                    <label for="oldpost-edited-text">Text for this Twitter update</label><br/>
                    <input type="text" name="oldpost-edited-text" id="oldpost-edited-text" size="60" maxlength="146"
                           value="<?php echo(get_option('tweetlyUpdater_oldpost-edited-text')) ?>"/>
                    &nbsp;&nbsp;
                    <input type="checkbox" name="oldpost-edited-showlink" id="oldpost-edited-showlink"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_oldpost-edited-showlink')?> />
                    <label for="oldpost-edited-showlink">Link title to blog?</label>
                </p>

                <p>
                    <input type="checkbox" name="oldpost-edited-skippages" id="oldpost-edited-skippages"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_oldpost-edited-skippages')?> />
                    <label for="oldpost-edited-skippages">Skip pages</label>
                </p>
            </fieldset>
            <fieldset>
                <legend>Hashtags</legend>
                <p>
                    <input type="checkbox" name="usehashtags" id="usehashtags"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_usehashtags')?> />
                    <label for="usehashtags">Generate hashtags from the posts categories and/or tags</label>
                </p>

                <p>
                    <input type="checkbox" name="usehashtags-cats" id="usehashtags-cats"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_usehashtags-cats')?>" />
                    <label for="usehashtags-cats">Use categories</label>
                    &nbsp;&nbsp;
                    <input type="checkbox" name="usehashtags-tags" id="usehashtags-tags"
                           value="1" <?php vc_checkCheckbox('tweetlyUpdater_usehashtags-tags')?>" />
                    <label for="usehashtags-tags">Use tags</label>
                </p>

                <p>
                    <label for="newpost-published-text">Add fixed hash tags(i.e. #foo #bar)</label><br/>
                    <input type="text" name="usehashtags-static" id="usehashtags-static" size="60"
                           maxlength="146" value="<?php echo(get_option('tweetlyUpdater_usehashtags-static')) ?>"/>
                </p>
            </fieldset>
            <input type="hidden" name="submit-type" value="options">
            <input type="submit" name="submit" class="button-primary" value="save options"/>
        </div>
    </form>

<?php } else { ?>
    <p><strong>The plugin is deactivated, please check error messages above!</strong></p>
<?php } ?>

</div>

