<?php
class MMRGuess
{

	public $error, $results;

	/**
	 *
	 *
	 * @param unknown $id
	 */

	function __construct( $id ) {
		$this->start( $id );
	}

	/**
	 *
	 *
	 * @param unknown $id
	 */
	public function start( $id ) {
		if( !empty( $_POST['sauce'] ) ) {
			$html = $_POST['sauce'];
		}
		else {
			if( ! is_numeric( $id ) ) {
				$this->error = '<div class="alert alert-warning">Invalid Dotabuff ID "<strong>'.$_GET['id'].'</strong>". Make sure this ID is valid.<br>Or, visit your Dotabuff profile manually, and copy paste HTML source into the box below.</div>';
				return false;
			}
			$url = 'http://www.dotabuff.com/players/' . $id;
			$html = $this->getHTML( $url );
		}

		if( strstr( $html, '<div>' ) === false ) {
			$this->error = '<div class="alert alert-warning">Your input isnt the HTML source of your profile.<br>
			Visit your profile page, right click anywhere, click View page source, and copy paste that entire page into here.</div>';
			return false;
		}

		if( strstr( $html, 'Too Many Requests' ) || empty( $html ) ) {
			$this->error = '<div class="alert alert-warning">Could not calculate. My server has been limited by dotabuff cause of all of you requesting.<br>Check again later, or paste your page source into the box below to <strong>get your MMR now!</strong></div>';
			return false;
		}

		if( strstr( $html, 'Not Found' ) !== false ) {
			$this->error = '<div class="alert alert-warning">Invalid Dotabuff ID.</div>';
			return false;
		}

		if( strstr( $html, 'This profile is private' ) !== false ) {
			$this->error = '<div class="alert alert-warning">Your Dotabuff profile is private.</div>';
			return false;
		}

		if( strstr( $html, '<li class="active"><div class="flag flag-en">' ) === false ) {
			$this->error = '<div class="alert alert-warning">Your profile must be in English. Please visit your profile, set the language selector to English at the bottom, and try again.</div>';
			return false;
		}

		$this->guessMMR( $html );
	}

	/**
	 *
	 *
	 * @param unknown $html
	 * @return unknown
	 */
	public function guessMMR( $html ) {
		$currentMMR = 0;
		$response = '<table style="width:300px;" class="table">';

		// Skill checks
		$response.=( "<tr class='text-muted'><td>Skill Bracket</td>" );
		if( strstr( $html, "Very High Skill" ) && strstr( $html, "High Skill" ) !== false ) {
			$currentMMR = 3500;
		}
		else if( strstr( $html, "High Skill" ) && strstr( $html, "Normal Skill" ) && !strstr( $html, "Very High Skill" ) !== false ) {
			$currentMMR = 2850;
		}
		else if( strstr( $html, "Very High Skill" ) !== false ) {
			$currentMMR = 3800;
		}
		else if( strstr( $html, "Normal Skill" ) !== false ) {
			$currentMMR = 1750;
		}
		else if( strstr( $html, "High Skill" ) !== false ) {
			$currentMMR = 3000;
		}
		else {
			$currentMMR = 2500;
		}

		$response.= '<td>' . $currentMMR . '</td></tr>';

		// Hero checks
		if( strstr( $html, "Earth Spirit" ) !== false ) {
			$currentMMR+= 350;
			$response.=( "<tr class='text-muted'><td>OP Hero (Earth Spirit)</td><td>" . $currentMMR . '</td></tr>' );
		}
		else if( strstr( $html, "Juggernaut" ) !== false ) {
			$currentMMR+= 150;
			$response.=( "<tr class='text-muted'><td>OP Hero (Juggernaut)</td><td>" . $currentMMR . '</td></tr>' );
		}
		else if( strstr( $html, "Troll Warlord" ) !== false ) {
			$currentMMR+= 150;
			$response.=( "<tr class='text-muted'><td>OP Hero (Troll Warlord)</td><td>" . $currentMMR . '</td></tr>' );
		}
		else if( strstr( $html, "Meepo" ) !== false ) {
			$currentMMR+= 500;
		}
		else if( strstr( $html, "Storm Spirit" ) !== false ) {
			$currentMMR+= 300;
		}

		$mostPlayed = explode( "</time></a></div></td><td>", $html );
		if( empty( $mostPlayed[1] ) ) {
			$this->error = '<div class="alert alert-warning">Could not find most played hero.</div>';
			return false;
		}

		$gamp = "";
		$gamp = substr( $mostPlayed[1], 0, 3 );
		$gamp = str_replace( "<", "", $gamp );
		$gamp = str_replace( "d", "", $gamp );
		if( strstr( $gamp, "," ) !== false )$gamp = str_replace( ',', '', substr( $mostPlayed[1], 0, 5 ) );

		$response.=( "<tr class='text-muted'><td>Most Matches</td><td>" . $gamp . '</td></tr>' );
		$gamesAsMostPlayed = intval( $gamp );
		$currentMMR+= $gamesAsMostPlayed;

		// Amount of games
		$gamesSplit[] = explode( "Stats Recorded</td><td>", $html );
		$gamesString = substr( $gamesSplit[0][1], 0, 5 );
		$gamesString = str_replace( "<", "", $gamesString );
		$gamesString = str_replace( "i", "", $gamesString );
		$gamesString = str_replace( "d", "", $gamesString );
		$games = intval( str_replace( ",", "", $gamesString ) );

		$response.=( "<tr class='text-muted'><td>Total Matches</td><td>" . $games . '</td></tr>' );
		$currentMMR+= $games / 4;

		$response.= "<tr class='text-success'><td>Your MMR is</td><td><strong>" . $currentMMR . "</strong></td></tr>";
		$response.= '</table>';

		$this->results = $response;
	}

	/**
	 *
	 *
	 * @param unknown $url
	 * @return unknown
	 */
	public function getHTML( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		$html = curl_exec( $ch );
		curl_close( $ch );

		return $html;
	}
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>MMR Guesser</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
  </head>
  <body>
  	<div class="container">
  		<h1 class="page-header">Guess my MMR <small><a target="_BLANK" href="http://www.reddit.com/r/DotA2/comments/33cmby/mmr_guess/">reddit thread</a></small></h1>

<?php
if( !empty( $_POST['sauce'] ) ) {
	$mmr = new MMRGuess( $_POST['sauce'] );
}
else if( !empty( $_GET['id'] ) ) {
	$mmr = new MMRGuess( $_GET['id'] );
}

if( !empty( $mmr->error ) ) {
	echo $mmr->error;
}
else if( !empty( $mmr->results ) ) {
	echo $mmr->results;
	echo '<hr>';
}
?>

  		<p>Visit your Dotabuff profile and copy paste the entire HTML source code into this box.</p>

		<form method="POST">

		  <div class="form-group">
		  	<label class="control-label">Dotabuff profile page source</label>
		    <textarea rows="5" class="form-control" id="sauce" name="sauce"></textarea>
		  	<p class="help-block">Eg, from <a target="_BLANK" href="view-source:http://www.dotabuff.com/players/83696920">view-source:http://www.dotabuff.com/players/83696920</a></p>
		  </div>

		  <button type="submit" class="btn btn-primary btn-lg btn-block">Submit</button>
		</form>
	</div>

	<script type="text/javascript">
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', 'UA-62079540-1', 'auto');
	  ga('send', 'pageview');

	</script>
  </body>
</html>
