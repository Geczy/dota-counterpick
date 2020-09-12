<?php require_once 'functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Dotabuff Profiles</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <style type="text/css">
		footer {
		  padding: 30px 0;
		}
    </style>
  </head>
  <body>
  	<div class="container">
  		<h1 class="page-header">Dotabuff Profiles <button class="pull-right btn btn-xs btn-default" data-toggle="modal" data-target="#myModal">changelog</button></h1>

  		<?php

  		if (!empty($dbuff->error)) {
  			echo $dbuff->error;
  		} else if ( !empty($dbuff->links) ) {

			if ( $dbuff->isServerLog ) {
				echo '<p class="text-center">' . (!empty($dbuff->gameModes[$dbuff->gameType]) ? $dbuff->gameModes[$dbuff->gameType] : $dbuff->gameType) . '<br>';
				echo (!empty($dbuff->gameModesDesc[$dbuff->gameType]) ? ( $dbuff->gameModesDesc[$dbuff->gameType] .'<br>' ) : '');
				echo $dbuff->serverStats.'</p>';

				echo '<div class="row">';

				echo '<div class="col-sm-6">';
				echo '<h4>Radiant</h4>';
				$dbuff->displayRates( 'Radiant' );
				echo '</div>';

				echo '<div class="col-sm-6">';
				echo '<h4>Dire</h4>';
				$dbuff->displayRates( 'Dire' );
				echo '</div>';

				echo '</div>';

				echo '<small>Player data shown is filtered to the last 3 months of their most played heroes.</small><br>';
				echo '<small>Counter data is based on weekly figures. Last updated '.date('F d Y h:i A', filemtime('counters.json') ).'</small>';

			} else {
				echo '<ol id="allLinks">';
				for ($i=0; $i < 10; $i++) {
					?><li><a href="<?php echo $dbuff->links[$i]; ?>" target='_BLANK'><?php echo $dbuff->links[$i]; ?></a></li><?php
				}
				echo '</ol>';

				echo '<small>If you want an advanced detailed report, I recommend you try method A)</small>';
			}

			echo '<hr>';
		}
		?>

  		<p>This can be useful when selecting heroes. You can give your teammates their best hero as suggestion, or check the enemies best heroes and find counters.</p>
		<form method="POST">

			<div class="row">
				<div class="col-sm-6">
				  <div class="form-group <?php if(!empty($dbuff->error)) { echo 'has-error'; } else if(empty($dbuff->error) && (!empty($_POST['serverLog']) || !empty($_GET['serverLog']))) { echo 'has-success'; } ?>">
				  	<label class="control-label">A) Contents of <code>server_log.txt</code></label>
				    <textarea rows="5" class="form-control" id="serverLog" name="serverLog"><?php echo !empty($_POST['serverLog']) ? $_POST['serverLog'] : (!empty($_GET['serverLog']) ? $_GET['serverLog'] : ''); ?></textarea>
				  	<p class="help-block">You can copy paste the entire server_log.txt, or just the last couple lines. Found in:</p>
				  	<p class="help-block"><code>C:\Program Files (x86)\Steam\steamapps\common\dota 2 beta\dota\</code></p>

				  	<p class="help-block text-right"><a href="/dota/DotabuffChecker.ahk">Download this script</a> to automate this process. You will need <a href="http://www.autohotkey.com/">AuotHotKey</a> to run it.<br><br>
				  		<small>This program runs in the background. Modify the filepath in the script if your Dota exists in a different folder than mine.</small>
				  	</p>

				  </div>
				</div>

				<div class="col-sm-6">
				  <div class="form-group">
				  	<label class="control-label">B) Console after typing <code>/ping</code></label>
				    <textarea rows="5" class="form-control" id="nameList" name="nameList"><?php echo !empty($_POST['nameList']) ? $_POST['nameList'] : ''; ?></textarea>
				  	<p class="help-block">You can copy paste the entire console log.</p>
				  </div>
				</div>
			</div>

		  <button type="submit" class="btn btn-primary btn-lg btn-block">Submit</button>
		</form>

		<!-- Changelog -->
		<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title" id="myModalLabel">Changelog</h4>
		      </div>
		      <div class="modal-body">
<pre>Oct 11, 2015
* Fix: Dota 2 Reborn now works

Apr 23, 2015 - 3:33 PM
* New: Showing more counters than just one now. Click more to see a list of 8!

Apr 21, 2015 - 12:48 PM
* New: Added a counter column. Uses data from Dotabuff and displays the best counter of the week.

Apr 21, 2015 - 10:39 AM
* Tweak: Replaced one line of text with tables to show hero win rates. Dotabuff link is an icon now.

Apr 21, 2015 - 10:10 AM
* New: Added session caching for dotabuff results. This way if you refresh, the page will load instantly without rechecking all the top heroes again. Much less requests to Dotabuff.com as a result, and less stress on my servers.
* Tweak: Replaced dota game mode enums with english text. Eg, from DOTA_GAMEMODE_AP to All Pick. Added game mode description below this title.

Apr 21, 2015 - 8:26 AM
* New: Gets last 3 months data, instead of all time. Also doesn't filter for >=51% anymore, just shows top 3 most played heroes in last 3 months.
* New: Added total matches next to win rates for context
* Fix: An exception for when the dbuff profile was public, but had no match data
* Fix: "Invalid data" error. Submitting form A wasn't working, but it was through the URL scheme</pre>
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		      </div>
		    </div>
		  </div>
		</div>
  	</div>


  	<footer>
  		<div class="container">
  			<hr>
  			<p class="text-muted text-right">As seen <a href="http://www.reddit.com/r/DotA2/comments/33bb98/now_with_dotabuff_integration_check_ingame/" target="_BLANK">on reddit</a></p>
  		</div>
  	</footer>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script src="assets/modal.js"></script>
	<script src="assets/tooltip.js"></script>
	<script src="assets/popover.js"></script>
	<script type="text/javascript">
		$(function () {
		  $('[data-toggle="popover"]').popover();
		});

		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-62079540-1', 'auto');
		ga('send', 'pageview');
	</script>
  </body>
</html>
