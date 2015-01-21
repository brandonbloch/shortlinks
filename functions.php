<?php
  ///////////////////////////////////
 /// GLOBAL VARIABLE DEFINITIONS ///
///////////////////////////////////

require_once 'config.php';

// MySQL Connection
$con = mysqli_connect( $SQL_SERVER, $SQL_USERNAME, $SQL_PASSWORD, $SQL_DATABASE ) or die( "Unable to connect" );

function get_current_page() {
	$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
	$url = $_SERVER['HTTP_HOST'] . $uri_parts[0];
	return $url;
}

function valid_url( $url ) {
	$regex  = "#^"; // start of the line
	$regex .= "(https?\\:\\/\\/)?"; // optional http:// or https://
	$regex .= "([0-9a-zA-Z\\-]{1,63}\\.)*"; // 0 or more subdomains with trailing "."
	$regex .= "[0-9a-zA-Z\\-]{1,63}\\.[a-zA-Z]{2,8}"; // mandatory domain name and TLD
	$regex .= "(\\:[0-9]{1,5})?"; // optional port number
	$regex .= "(\\/[0-9a-zA-Z\\$\\-\\_\\.\\+\\!\\*\\'\\(\\)\\%]+)*"; // 0 or more subdirectories with leading "/"
	$regex .= "\\/?"; // optional trailing slash
	$regex .= "([0-9a-zA-Z\\$\\-\\_\\.\\+\\!\\*\\'\\(\\)\\%]+\\.[0-9a-zA-Z]{1,11})?"; // optional file within directory
	$regex .= "(\\?([0-9a-zA-Z]+\\=[0-9a-zA-Z\\$\\-\\_\\.\\+\\'\\(\\)\\%]+\\&?)*)?"; // optional query
	$regex .= "(\\#[0-9a-zA-Z\\-\\_\\.\\+\\%]*)?"; // optional fragment
	$regex .= "$#"; // end of the line
	return preg_match( $regex, $url );
}

function force_protocol( $url ) {
	if ( ! preg_match( '#^https?\\:\\/\\/#', $url ) ) {
		$url = "http://" . $url;
	}
	return $url;
}

function get_protocol( $url ) {
	if ( preg_match( '#^http\\:\\/\\/#', $url ) ) {
		return "http";
	} elseif ( preg_match( '#^https\\:\\/\\/#', $url ) ) {
		return "https";
	} else {
		return "";
	}
}

function remove_protocol ( $url ) {
	if ( get_protocol( $url ) == "" ){
		return $url;
	} else {
		return str_replace( get_protocol( $url ), "", $url );
	}
}

function add_link( $url ) {
	global $con;
	global $SQL_TABLE;
	$referrer = $_SERVER['HTTP_REFERER'];
	$url = trim( $url );
	// Return home if the form is submitted with only whitespace
	if ( strlen( $url ) == 0 ) {
		header( "Location: $referrer" );
	}
	// Validate the URL before progressing to add it to the database
	// If it fails, return to the original page and repopulate the input
	if ( ! valid_url( $url ) ) {
		$protocol = get_protocol( $url );
		if ( $protocol == "" ) {
			header( "Location: ". $referrer . "e=$url" );
		} else {
			$url = remove_protocol( $url );
			$url = rawurlencode( $url );
			header( "Location: " . $referrer . "e=$url&p=$protocol" );
		}
	}
	// If it passes, proceed
	else {
		// Add the protocol if not provided, ensuring absolute URLs are stored
		$url = force_protocol( $url );
		$pid = 0;
		// Check to see if the link is already in the database
		$sql = "SELECT pid, url FROM $SQL_TABLE";
		$result = mysqli_query( $con, $sql );
		if ( ! $result ) {
			echo mysqli_error( $con );
		} else {
			// Check entries in the database for matching links
			$matchFound = false;
			while ( $row = $result->fetch_assoc() ) {
				if ( $row['url'] == $url ) {
					$matchFound = true;
					$pid = $row['pid'];
				}
			}
			// If the link already exists, use the PID of the existing one
			if ( ! $matchFound ) {
				$sql = "INSERT INTO $SQL_TABLE (url) VALUES ('$url')";
				$sql_result = mysqli_query( $con, $sql );
				if ( ! $sql_result ) {
					echo mysqli_error( $con );
				} else {
					$pid = mysqli_insert_id( $con );
				}
			}
			$pid = base31_encode( $pid );
			header( "Location: $referrer?d=$pid" );
		}
	}
}

function base31_encode( $pid ) {
	$pid = base_convert($pid, 10, 31); // returns a number from 0 to U
	$pid = str_replace("0", "v", $pid); // convert all I to V
	$pid = str_replace("1", "w", $pid); // convert all I to W
	$pid = str_replace("i", "x", $pid); // convert all I to X
	$pid = str_replace("l", "y", $pid); // convert all L to Y
	$pid = str_replace("o", "z", $pid); // convert all O to Z
	return $pid;
}

function base31_decode( $pid ) {
	$pid = str_replace("v", "0", $pid);
	$pid = str_replace("w", "1", $pid);
	$pid = str_replace("x", "i", $pid);
	$pid = str_replace("y", "l", $pid);
	$pid = str_replace("z", "o", $pid);
	$pid = base_convert($pid, 31, 10);
	return $pid;
}

function the_header() {
	// Connect to the database
	global $con;
	global $SQL_TABLE;
	// If a PID is passed in the URL, look up the long URL and redirect to it
	// (also increments the click count and updates the timestamp)
	if ( isset( $_GET['u'] ) ) {
		$pid = $_GET['u'];
		$pid = strtolower( $pid );
		$pid = base31_decode( $pid );
		$sql = "SELECT url, clicks FROM $SQL_TABLE WHERE pid = '$pid'";
		$result = mysqli_query($con, $sql);
		if ( $result ) {
			$link_data = array();
			while ( $row = $result->fetch_assoc() ) {
				array_push( $link_data, $row );
			}
			$result->free();
			// If no matching PID was found in the database, go to the homepage
			if ( sizeof( $link_data ) == 0 ) {
				header( 'Location: $referrer' );
			} else {
				$url = $link_data[0]['url'];
				$clicks = $link_data[0]['clicks'] + 1;
				$sql = "UPDATE " . $SQL_TABLE . " SET clicks = $clicks WHERE pid = '$pid'";
				$sql_result = mysqli_query($con, $sql);
				if (!$sql_result) {
					echo mysqli_error($con);
				} else {
					header( "Location: $url" );
				}
			}
		}
	}
}

function the_title() {
	global $TITLE;
	echo $TITLE;
}

function get_title() {
	global $TITLE;
	return $TITLE;
}

function the_form() {
	$displayMode = false;
	$errorMode = false;
	$url = "";
	$shortUrl = "";
	// If the form was submitted with invalid input and the input is sent back to retry
	if ( isset( $_GET['e'] ) ) {
		$errorMode = true;
		$url = rawurldecode( $_GET['e'] );
		if ( isset( $_GET['p'] ) ) {
			$url = $_GET['p'] . $url;
		}
	}
	// If a successful entry was created and the shortened link is displayed
	elseif ( isset( $_GET['d'] ) ) {
		$displayMode = true;
		$pid = $_GET['d'];
		$shortUrl = get_current_page() . $pid;
	}
	echo '<form action="add.php" method="post">' .
	     '<label for="url">Link to Shorten</label>' .
	     '<input type="text" name="url" id="url" placeholder="http://" ';
	if ( $errorMode )
		echo 'value="' . $url . '" class="retry" ';
	echo 'autocorrect="off" autocapitalize="off" autocomplete="off" ';
	if ( ! $displayMode )
		echo 'autofocus ';
	echo 'oninput="clearButton()">' .
	     '<input type="submit" name="submit" value="Shorten" tabindex="-1" onclick="return empty()">' .
	     '<span id="clear-button" title="Clear input" onclick="clearInput()" ontouchstart>' .
	     '</span>' .
	     '</form>';
	if ( $displayMode )
		echo '<div class="success"><a href="http://' . $shortUrl . '">' . $shortUrl . '</a></div>';
}