<?php

/**
 *
 */
class DBuff
{
	public $winRates = array();
	public $links = array();
	public $counters = array();

	public $isServerLog = false;

	public $gameModes = array(
		'DOTA_GAMEMODE_NONE' => '-',
		'DOTA_GAMEMODE_AP' => 'All Pick',
		'DOTA_GAMEMODE_CM' => 'Captains Mode',
		'DOTA_GAMEMODE_RD' => 'Random Draft',
		'DOTA_GAMEMODE_SD' => 'Single Draft',
		'DOTA_GAMEMODE_AR' => 'All Random',
		'DOTA_GAMEMODE_INTRO' => '-',
		'DOTA_GAMEMODE_HW' => 'Diretide',
		'DOTA_GAMEMODE_REVERSE_CM' => 'Reverse Captains Mode',
		'DOTA_GAMEMODE_XMAS' => 'The Greeviling',
		'DOTA_GAMEMODE_TUTORIAL' => 'Tutorial',
		'DOTA_GAMEMODE_MO' => 'Mid Only',
		'DOTA_GAMEMODE_LP' => 'Least Played',
		'DOTA_GAMEMODE_POOL1' => 'Limited Heroes',
		'DOTA_GAMEMODE_FH' => 'Compendium',
		'DOTA_GAMEMODE_CUSTOM' => 'Custom',
		'DOTA_GAMEMODE_CD' => 'Captains Draft',
		'DOTA_GAMEMODE_BD' => 'Balanced Draft',
		'DOTA_GAMEMODE_ABILITY_DRAFT' => 'Ability Draft',
		'DOTA_GAMEMODE_EVENT' => '-',
		'DOTA_GAMEMODE_ARDM' => 'All Random Deathmatch',
		'DOTA_GAMEMODE_1V1MID' => '1v1 Solo Mid',
		'DOTA_GAMEMODE_ALL_DRAFT' => 'All Pick',
	);

	public $gameModesDesc = array(
		"DOTA_GAMEMODE_AP" => "-",
		"DOTA_GAMEMODE_CM" => "Each player selects a hero from the entire hero pool.",
		"DOTA_GAMEMODE_RD" => "Each team is assigned a Captain, who makes all the hero selections for their team. Captains also ban heroes from the pool.",
		"DOTA_GAMEMODE_SD" => "Players take turns selecting a hero from a shared pool of 20 random heroes. You will be told when it's your turn to select.",
		"DOTA_GAMEMODE_AR" => "Each player selects a hero from a set of three heroes randomly chosen for them.",
		"DOTA_GAMEMODE_INTRO" => "Each player is randomly assigned a hero.",
		"DOTA_GAMEMODE_HW" => "-",
		"DOTA_GAMEMODE_REVERSE_CM" => "Diretide",
		"DOTA_GAMEMODE_XMAS" => "Same as Captain's mode except team's pick for each other",
		"DOTA_GAMEMODE_TUTORIAL" => "Thanks to an infestation of wild Greevils, Frostivus has been cancelled! It's up to you and your tame pet Greevils to reclaim the holiday!",
		"DOTA_GAMEMODE_MO" => "Tutorial mode.",
		"DOTA_GAMEMODE_LP" => "Shuts off side lanes and allows the same hero to be picked.",
		"DOTA_GAMEMODE_POOL1" => "Players can only choose from a list of their least played heroes! This mode is great for learning new heroes since everyone will be on equal footing.",
		"DOTA_GAMEMODE_FH" => "Play with heroes suitable for new players.",
		"DOTA_GAMEMODE_CUSTOM" => "Play using the heroes picked in a featured match.",
		"DOTA_GAMEMODE_CD" => "Each team is assigned a Captain, who bans and selects heroes from a limited pool.",
		"DOTA_GAMEMODE_BD" => "Each team is given 5 heroes that are automatically selected with an attempt to balance roles.",
		"DOTA_GAMEMODE_ABILITY_DRAFT" => "Create a unique Hero by drafting from a pool of abilities.",
		"DOTA_GAMEMODE_EVENT" => "-",
		"DOTA_GAMEMODE_ARDM" => "Players become a new hero every time they respawn.  Each team gets a total of 40 respawns.",
		"DOTA_GAMEMODE_1V1MID" => "Two players compete in the middle lane.",
		"DOTA_GAMEMODE_ALL_DRAFT" => "Each team takes turns selecting a hero from the entire hero pool.",
	);

	function __construct() {
		require_once 'classes/simple_html_dom.php';

		if( !isset( $_SESSION ) )session_start();

		if(!empty($_GET['counterupdate'])) {
			$this->scrapeAdvantages();
		}

		if( !empty( $_POST['nameList'] ) ) {
			$this->doPingList();
		}
		else if( !empty( $_POST['serverLog'] ) || !empty( $_GET['serverLog'] ) ) {
			$this->doDetailList();
		}
	}

	private function doPingList() {

		// http://www.dotabuff.com/search?utf8=%E2%9C%93&q=
		$k = explode( 'Client ping times:', trim( $_POST['nameList'] ) );
		$k = trim( end( $k ) );

		$k = array_map( 'trim', explode( PHP_EOL, $k ) );
		foreach( $k as $key => $value ) {
			if( strstr( $value, 'ms : ' ) !== false ) {
				$value = substr( $value, strrpos( $value, ':' ) + 2 );
				$url = 'http://www.dotabuff.com/search?utf8=%E2%9C%93&q=' . $value;
				$this->links[] = $url;
			}
		}
	}

	private function doDetailList() {
		$this->counters = json_decode( file_get_contents( 'counters.json' ) );

		$log = !empty( $_GET['serverLog'] ) ? $_GET['serverLog'] : $_POST['serverLog'];

		if( strstr( $log, 'Lobby' ) === false ) {
			$this->error = '<div class="alert alert-danger">Input data invalid</div>';
			return;
		}

		$this->isServerLog = true;

		$k = explode( PHP_EOL, trim( $log ) );
		$k = trim( end( $k ) );
		$k = explode( '(', $k );

		$this->serverStats = strstr( $k[0], ': ', true );
		$this->gameType = @end( explode( ' ', trim( current( explode( '0:[', $k[1] ) ) ) ) );

		$lobbyPlayers = $k[1];
		$partyPlayers = $k[2];

		preg_match_all( "/\[([^\]]*)\]/", $lobbyPlayers, $matches );
		$lobbyPlayers = $matches[1];

		preg_match_all( "/\[([^\]]*)\]/", $partyPlayers, $matches );
		$partyPlayers = $matches[1];

		foreach( $lobbyPlayers as $key => $value ) {
			$playerID = substr( $value, strrpos( $value, ':' ) + 1 );
			$url = 'http://www.dotabuff.com/players/' . $playerID . '/heroes?date=3month&skill_bracket=&lobby_type=&game_mode=&faction=&duration=&metric=played';
			$this->winRates[] = $this->getRates( $url );
			$this->links[] = $url;
		}
	}

	/**
	 *
	 *
	 * @param unknown $url
	 * @return unknown
	 */

	private function curlHTML( $url ) {
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

		return str_get_html( $html );
	}

	/**
	 *
	 *
	 * @param unknown $url
	 * @return unknown
	 */
	private function getRates( $url ) {
		if( !empty( $_SESSION['dbuff0.1'][ $url ] ) ) {
			return $_SESSION['dbuff0.1'][ $url ];
		}

		$html = $this->curlHTML( $url );
		$winRates = array();

		if( strstr( $html, 'Too Many Requests' ) || empty( $html ) ) {
			$this->error = '<div class="alert alert-warning">Dotabuff has limited my server. Too many requests! Come back later...</div>';
			return array(
				$name => array(),
			);
		}

		$mostPlayed = $html->find( 'table tbody', 0 );
		$name = strstr( $html->find( 'h1', 0 )->innertext() , '<small>', true );

		if( empty( $mostPlayed ) ) {
			return array(
				$name => array(),
			);
		}

		// Get most played
		foreach( $mostPlayed->find( 'tr' ) as $key => $tr ) {
			if( $key == 0 )continue;

			$winRate = $tr->find( 'td', 3 );
			if( empty( $winRate ) ) {
				continue;
			}

			$matchesPlayed = strstr( $tr->find( 'td', 2 )->innerText() , '<div', true );
			$hero = strstr( $tr->find( 'td', 1 )->innertext() , '</a>', true );
			$hero = ltrim( strstr( $hero, '">' ), '">' );
			$winrate = strstr( $winRate->innertext() , '%', true );
			$winRates[ $hero ] = array(
				$matchesPlayed,
				$winrate
			);
		}

		/*
		foreach ( $winRates as $key => $value ) {
		 if ( $value < 51 ) {
		  unset( $winRates[$key] );
		 }
		}
		*/

		$winRates = array_slice( $winRates, 0, 3 );
		$response = array(
			$name => $winRates
		);
		$_SESSION['dbuff0.1'][ $url ] = $response;

		return $response;
	}

	/**
	 *
	 *
	 * @param unknown $team
	 * @param unknown $winRates
	 */
	public function displayRates( $team ) {
		$count =( $team == 'Radiant' ) ? 5 : 10;
		$start =( $team == 'Radiant' ) ? 0 : 5;
		echo '<ol>';
		for( $i = $start; $i < $count; $i++ ) {

			echo '<li>';

			$heroes = '';
			if( !empty( $this->winRates[ $i ] ) )foreach( $this->winRates[ $i ] as $name => $rates ) {
				echo '<caption><a title="Dotabuff" target="_BLANK" href="' . strstr( $this->links[ $i ], '/heroes?', true ) . '"><span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a> ' . $name . '</caption>';
				echo '<table class="table table-condensed table-bordered">';
				echo '<tr>
					<th>Hero</th>
					<th>Win Rate</th>
					<th>Matches</th>
					<th class="text-muted">Counter</th>
				</tr>';

				if( empty( $rates ) ) {
					echo "<tr><td colspan='100%' class='text-center text-muted'>Sorry, there's no data for this player.</td></tr>";
				}
				else {
					foreach( $rates as $hero => $rate ) {

						if( empty( $hero ) ) {
							continue;
						}

						$counter = '<ol>';
						foreach( $this->counters->{$hero} as $key => $value ) {
							$counter.= '<li>' . implode( $value, ' (' ) . '%)</li>';
						}
						$counter.= '</ol>';

						echo '<tr>';
						echo '
						<td>' . $hero . '</td>
						<td>' . $rate[1] . '%</td>
						<td>' . $rate[0] . '</td>
						<td class="text-muted">' . $this->counters->{$hero}[1][0] . ' (' . $this->counters->{$hero}[1][1] . ') <a tabindex="0" class="" role="button" data-html="true" data-toggle="popover" data-placement="bottom" data-trigger="focus" title="More counters" data-content="' . $counter . '">more...</a></td>
					';
						echo '</tr>';
					}
				}
			}

			echo '</table>';
			echo '</li>';
		}
		echo '</ol>';
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	private function scrapeAdvantages() {
		$html = $this->curlHTML( 'http://www.dotabuff.com/heroes/' );

		$advantages = array();

		$heroes = $html->find( 'div.hero-grid', 0 );
		if( empty( $heroes ) ) {
			return false;
		}

		// Get most played
		foreach( $heroes->find( 'a' ) as $key => $a ) {
			$name = $a->find( 'div.name', 0 )->innertext();
			$url = $a->href;
			$heroList[ $name ] = $a->href;
		}

		foreach( $heroList as $heroName => $url ) {
			$html = $this->curlHTML( 'http://www.dotabuff.com' . $url );
			$worstVersus = $html->find( 'div.col-8 table', 3 );

			foreach( $worstVersus->find( 'tr' ) as $key => $tr ) {
				if( $key == 0 )continue;

				$adv = $tr->find( 'td', 2 );
				if( empty( $adv ) ) {
					continue;
				}

				$adv = abs( strstr( $adv->innertext() , '%', true ) );
				$hero = $tr->find( 'td a', 1 )->innertext();

				$advantages[ $heroName ][] = array(
					$hero,
					$adv,
				);
			}
		}

		$json = json_encode( $advantages );
		$file = fopen( 'counters.json', 'w' );
		fwrite( $file, $json );
		fclose( $file );
	}
}

$dbuff = new DBuff();
