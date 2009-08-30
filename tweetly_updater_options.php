<?php

	$prereqOK = true;

	if(!function_exists('json_decode')) {
		error_log("Function json_decode not found");
		add_action('admin_notices',showAdminMessage('Your php installation doesn\'t support the <a href="http://us.php.net/json_decode">json_decode</a> functionality.', true));
		$prereqOK = false;
	}
	
	if(!function_exists('curl_exec')) {
		error_log("Function curl_exec not found");
		add_action('admin_notices',showAdminMessage('Your php installation doesn\'t include <a href="http://us.php.net/manual/en/ref.curl.php">curl</a> support.', true));
		$prereqOK = false;
	}

	if(get_option('tweetlyUpdater_initialised') != '1'){
		update_option('tweetlyUpdater_newpost-published-update', '1');
		update_option('tweetlyUpdater_newpost-published-text', 'Published a new blog post: #title#');
		update_option('tweetlyUpdater_newpost-published-showlink', '1');

		update_option('tweetlyUpdater_oldpost-edited-update', '1');
		update_option('tweetlyUpdater_oldpost-edited-text', 'Fiddling with my blog post: #title#');
		update_option('tweetlyUpdater_oldpost-edited-showlink', '1');
		update_option('tweetlyUpdater_initialised', '1');
		update_option('tweetlyUpdater_bitlyuser', '');
		update_option('tweetlyUpdater_bitlyapikey', '');
	}
	
	if($_POST['submit-type'] == 'options'){
		update_option('tweetlyUpdater_newpost-published-update', $_POST['newpost-published-update']);
		update_option('tweetlyUpdater_newpost-published-text', $_POST['newpost-published-text']);
		update_option('tweetlyUpdater_newpost-published-showlink', $_POST['newpost-published-showlink']);

		update_option('tweetlyUpdater_oldpost-edited-update', $_POST['oldpost-edited-update']);
		update_option('tweetlyUpdater_oldpost-edited-text', $_POST['oldpost-edited-text']);
		update_option('tweetlyUpdater_oldpost-edited-showlink', $_POST['oldpost-edited-showlink']);
		add_action('admin_notices',showAdminMessage("Post options saved.", false));
	} else if ($_POST['submit-type'] == 'bitly') {
		if(($_POST['bitlyuser'] != '') AND ($_POST['bitlyapikey'] != '')){
			update_option('tweetlyUpdater_bitlyuser', $_POST['bitlyuser']);
			update_option('tweetlyUpdater_bitlyapikey', $_POST['bitlyapikey']);
		} else {
			add_action('admin_notices',showAdminMessage("You need to provide your bit.ly login and api key!", true));
		}
		add_action('admin_notices',showAdminMessage("bit.ly options saved.", false));
	} else if ($_POST['submit-type'] == 'login') {
		if(($_POST['twitterlogin'] != '') AND ($_POST['twitterpw'] != '')){
			update_option('tweetlyUpdater_twitterlogin', $_POST['twitterlogin']);
			update_option('tweetlyUpdater_twitterpw', $_POST['twitterpw']);
		} else {
			add_action('admin_notices',showAdminMessage("You need to provide your twitter login and password!", true));
		}
		add_action('admin_notices',showAdminMessage("Twitter options saved.", false));
	}

	function vc_checkCheckbox($theFieldname){
		if( get_option($theFieldname) == '1'){
			echo('checked="true"');
		}
	}
?>
<style type="text/css">
	fieldset{margin:20px 0; 
	border:1px solid #cecece;
	padding:15px;
	}
</style>



<div class="wrap">
	<h2>Your Tweetly Updater options</h2>
	
	<?php if ($prereqOK) { ?>
	
	<h3>Your Twitter account details</h3>
	
	<?php $tweetlyUpdater = new TweetlyUpdater(get_option('tweetlyUpdater_twitterlogin'), get_option('tweetlyUpdater_twitterpw'), get_option('tweetlyUpdater_bitlyuser'), get_option('tweetlyUpdater_bitlyapikey')); ?>
	
	<fieldset>
	<legend>Twitter login</legend>
	<form method="post" >
	<div>
		<?php
		if (!$tweetlyUpdater->twitterVerifyCredentials()) {
			add_action('admin_notices',showAdminMessage("You twitter login could not be verified!", true));
		}
		?>
		<p>
		<label for="twitterlogin">Your Twitter login name:</label><br />
		<input type="text" name="twitterlogin" id="twitterlogin" value="<?php echo(get_option('tweetlyUpdater_twitterlogin')) ?>" />
		</p>
		<p>
		<label for="twitterpw">Your Twitter password:</label><br />
		<input type="password" name="twitterpw" id="twitterpw" value="<?php if(get_option('tweetlyUpdater_twitterpw') !=''){echo("********");}?>" />
		</p>
		<input type="hidden" name="submit-type" value="login">
		<p><input type="submit" name="submit" class="button-primary" value="save login" /></p>
		<p><strong>Don't have a Twitter account? <a href="http://www.twitter.com">Get one for free here!</a></strong></p>
	</div>
	</form>
	</fieldset>

	<h3>bit.ly settings</h3>
	<fieldset>
		<legend>bit.ly account data</legend>
		<form method="post" >
		<div>
		<?php
		if (!$tweetlyUpdater->bitlyVerifyCredentials()) {
			add_action('admin_notices',showAdminMessage("Your bit.ly login could not be verified!", true));
		}
		?>			
			<p>
			<label for="bitlyuser">Your bit.ly username:</label><br />
			<input type="text" name="bitlyuser" id="bitlyuser" value="<?php echo(get_option('tweetlyUpdater_bitlyuser')) ?>" />
			</p>
			<p>
			<label for="bitlyapikey">Your bit.ly api key:</label><br />
			<input type="text" name="bitlyapikey" id="bitlyapikey" value="<?php if(get_option('tweetlyUpdater_bitlyapikey') !=''){echo("-- not shown --");}?>" />
			</p>
			<input type="hidden" name="submit-type" value="bitly">
			<p><input type="submit" name="submit" class="button-primary" value="save login" /></p>
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
				<input type="checkbox" name="newpost-published-update" id="newpost-published-update" value="1" <?php vc_checkCheckbox('tweetlyUpdater_newpost-published-update')?> />
				<label for="newpost-published-update">Update Twitter when the new post is published</label>
			</p>
			<p>
				<label for="newpost-published-text">Text for this Twitter update</label><br />
				<input type="text" name="newpost-published-text" id="newpost-published-text" size="60" maxlength="146" value="<?php echo(get_option('tweetlyUpdater_newpost-published-text')) ?>" />
				&nbsp;&nbsp;
				<input type="checkbox" name="newpost-published-showlink" id="newpost-published-showlink" value="1" <?php vc_checkCheckbox('tweetlyUpdater_newpost-published-showlink')?> />
				<label for="newpost-published-showlink">Link title to blog?</label>
			</p>
		</fieldset>
		
		<fieldset>
			<legend>Existing posts</legend>
			<p>
				<input type="checkbox" name="oldpost-edited-update" id="oldpost-edited-update" value="1" <?php vc_checkCheckbox('tweetlyUpdater_oldpost-edited-update')?> />
				<label for="oldpost-edited-update">Update Twitter when the an old post has been edited</label>
			</p>
			<p>
				<label for="oldpost-edited-text">Text for this Twitter update</label><br />
				<input type="text" name="oldpost-edited-text" id="oldpost-edited-text" size="60" maxlength="146" value="<?php echo(get_option('tweetlyUpdater_oldpost-edited-text')) ?>" />
				&nbsp;&nbsp;
				<input type="checkbox" name="oldpost-edited-showlink" id="oldpost-edited-showlink" value="1" <?php vc_checkCheckbox('tweetlyUpdater_oldpost-edited-showlink')?> />
				<label for="oldpost-edited-showlink">Link title to blog?</label>
			</p>
		</fieldset>

		<input type="hidden" name="submit-type" value="options">
		<input type="submit" name="submit" class="button-primary" value="save options" />
	</div>
	</form>

	<?php } else { ?>
		<p><strong>The plugin is deactivated, please check error messages above!</strong></p>
	<?php } ?>

</div>

