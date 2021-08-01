<?php
// Google Analytics
include_once("include/analytics.php");

// Initiate connection to database and user login session
include_once("include/session.php");

// Set values for page
$page_title   = "User Info";
$current_page = "home";

// Load header
include_once("include/header.php");

// Below this PHP block, set page-specific JavaScript, CSS, and anything else for the <head>
##################################################
?>
	<style type="text/css">
	<!--
		ul ul.inline {display:inline;margin-left:0;}
		ul.inline li {display:inline;list-style:none;margin-left:0;}
		ul.inline li::after{content:", ";white-space:pre;}
		ul.inline li:last-child::after{content:"";}
	-->
	</style>
<?php
##################################################
// Load top navigation
include_once("include/topnav.php");

// Below this PHP block, enter only the main HTML content of the page. All necessary layout, body, html, etc. tags are included in the PHP includes.
##################################################
?>
<!--form style="margin-left:450px" action="img/profilepics/upload.form.php" method="post" enctype="multipart/form-data">
    
    <input type="submit" value="Upload Your Profile Picture" name="submit">
</form-->

<?php
if (!$session->logged_in) {
	echo ("\t\t\t<h2>Restricted Area</h2>\n");
	echo ("\t\t\t<p>Sorry, but you must be logged in in order to view this page.</p>\n");
} //!$session->logged_in
else {
	/* Requested Username error checking */
	$req_user = trim($_GET['user']);
	if (@get_magic_quotes_gpc()) {
		$req_user = stripslashes($req_user); // Removes magic_quotes_gpc slashes
	} //@get_magic_quotes_gpc()
	$req_user = mysql_real_escape_string($req_user); // Prepends backslashes to special MySQL characters
	$req_user = (string) $req_user; // Force $req_user to be a string
	if (!$req_user || strlen($req_user) === 0 || !eregi("^([0-9a-z])+$", $req_user) || !$database->usernameTaken($req_user)) {
		die("Username not registered");
	} //!$req_user || strlen($req_user) === 0 || !eregi("^([0-9a-z])+$", $req_user) || !$database->usernameTaken($req_user)
	
	/* Logged in user viewing own account */
	if (strcmp($session->username, $req_user) == 0) {
		echo "<h2>My Account</h2>\n";

	} //strcmp($session->username, $req_user) == 0
	
	/* Visitor not viewing own account */
	else {
		echo "<h2>User Info</h2>\n";
	}
	
	

	/* Display requested user information */
	$req_user_info  = $database->getUserInfo($req_user);

/*echo "<span class='profilepics'><a href='/img/profilepics/" . $req_user_info['username'] . ".jpg'><img src='/img/profilepics/" . $req_user_info['username'] . ".jpg' height='200px'></a></span>";*/

	/* Check if user is a transfer from another chapter 
	$q      = "SELECT T.data_type, T.data_value FROM `" . TBL_TRANSFERS . "` AS T WHERE `username` = '" . $req_user_info['username'] . "'";
	$result = $database->query($q);
	$isTransfer = false; // Variable that remembers if the requested user is a transfer from another chapter
	if (mysql_num_rows($result) > 0) {
		$isTransfer = true;
		$littleCounter = 0;
		$req_transfer_info; 
		while ($row = mysql_fetch_array($result)) {
			if (strcmp($row['data_type'],"chapter") === 0) {
				$req_transfer_info["chapter"] = $row["data_value"];
			} else if (strcmp($row['data_type'],"big") === 0) {
				$req_transfer_info["big"] = $row["data_value"];
			} else if (strcmp($row['data_type'],"little") === 0) {
				$req_transfer_info["little"][$littleCounter] = $row["data_value"];
				$littleCounter++;
			}
		}
	}*/

	$semesterJoined = array(
		0 => "Fall",
		1 => "Spring"
	);
	echo "<ul>\n";
	echo "<li><strong>Name</strong>: " . $req_user_info['fname'] . " " . $req_user_info['lname'] . "</li>\n";
	echo "<li><strong>Username</strong>: " . $req_user_info['username'] . "</li>\n";
	echo "<li><strong>E-mail</strong>: <a href=\"mailto:" . $req_user_info['email'] . "\">" . $req_user_info['email'] . "</a></li>\n";
	echo "<li><strong>Secondary E-mail</strong>: <a href=\"mailto:" . $req_user_info['alumail'] . "\">" . $req_user_info['alumail'] . "</a></li>\n";
	echo "<li><strong>Phone</strong>: ";
	if (strlen($req_user_info['phone']) === 10) {
		echo preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $req_user_info['phone']);
	} //strlen($req_user_info['phone']) === 10
	else {
		echo "Unknown";
	}
	
	echo "</li>\n";
	echo "<li><strong>Status</strong>: " . $memberStatus[$req_user_info['status']] . "</li>\n";
// Comment out until pledges get families

// 	echo "<li><strong>Family</strong>: ";
// 	if ($req_user_info['family'] !== NULL && is_numeric($req_user_info['family'])) {
// 		echo $families[$req_user_info['family']];
// 	} //$req_user_info['family'] !== NULL && is_numeric($req_user_info['family'])
// 	else {
// 		echo "Unknown";
// 	}
// 	echo "</li>\n";
	
	// end comment  about families

	echo "<li><strong>Pledge Semester</strong>: ";
	if ($req_user_info['semester'] != 0 && $req_user_info['semester'] != 1) {
		echo "Unknown";
	} else {
		echo $semesterJoined[$req_user_info['semester']] . " " . $req_user_info['year'] . " (";
		if ($isTransfer) {
			if (array_key_exists("chapter",$req_transfer_info)) {
				echo $req_transfer_info["chapter"];
			} else {
				echo "Unknown";
			}
			echo " Chapter";
		} else {
			$year_edit = $req_user_info['year'];
			$semester_edit = $req_user_info['semester'] + 1;
			if ($semester_edit > 1) {
				$semester_edit = 0;
				$year_edit--; 
			}
			if ($req_user_info['year'] <= 2020) {
				echo $pledgeClasses[$req_user_info['year'] . $req_user_info['semester']] . " Class";
			}
			else {
				echo $pledgeClasses[$year_edit . $semester_edit] . " Class";
			}
		}
		echo ")";
	}
	
	echo "</li>\n";
	$query="SELECT * FROM users as U JOIN user_graduation as G ON U.username = G.username JOIN term as T ON G.graduation_term = T.term_id WHERE U.username = '".$req_user."'";
	$result=mysql_query($query);
	while($row=mysql_fetch_array($result)){echo "<li><strong>Graduation Term</strong>: ".$row['term']."</li>";}
	if(date("n")>=0&&date("n")<=5){$current_semester = 0;}else{$current_semester=1;}
	$query=mysql_query("SELECT * FROM officer as O JOIN officer_position as P ON O.position = P.rank JOIN term as T ON O.term = T.term_id WHERE O.username = '".$req_user."' WHERE T.year = YEAR(CURRENT_TIMESTAMP) && T.semester = $current_semester");
	if (mysql_num_rows($query) > 0) {
		while($row=mysql_fetch_array($query)){
		echo "<li><strong>Position</strong>: " . $row['title'] . "</li>\n";
		}
	}
	echo "</ul>\n";
//commented out until pledges get bigs

// 	echo "<h3>Family Line</h3>\n";
// 	$username = $req_user_info['username'];
// 	echo "<li><a href='../rosters/family_tree.php?user=$username'><strong>Family Tree</strong></a></li>";

// 	$q       = "SELECT U.fname, U.lname, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $req_user_info['big'] . "'";
// 	$result  = $database->query($q);
// 	$bigName = mysql_fetch_array($result);
// 	echo "<li><strong>Big</strong>: ";
// 		if($bigName){
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $req_user_info['big'] . "\">" . $bigName['fname'] . " " . $bigName['lname'] ."</a>";
// 	} else {
// 		echo "Unknown";
// 	}
// 	echo "</li>\n";};




// 	$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $bigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$gBigName = mysql_fetch_array($result);
// 	echo "<li><strong>Grand Big</strong>: ";
	
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($bigName['big'])) {
// 		echo "<a href=\"?user=" . $gBigName['username'] . "\">" . $gBigName['fname'] . " " . $gBigName['lname'] . "</a>";
// 	} else {
// 		echo "Unknown";
// 	}
// 	echo "</li>\n";


// 		$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $gBigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$ggBigName = mysql_fetch_array($result);
	
// 	echo "<li><strong>Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $ggBigName['username'] . "\">" . $ggBigName['fname'] . " " . $ggBigName['lname'] . "</a>";
// 	} else {
// 		echo "Unknown";
// 	}
// 	echo "</li>\n";

// echo "<div class=\"slide2\" style=\"cursor: pointer;\"><p><strong>Extended History</strong> (Click to Show)</p></div>
// 	<div class=\"view2\">";
	
// 			$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $ggBigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$gggBigName = mysql_fetch_array($result);
	
// 	echo "<li><strong>Great-Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $gggBigName['username'] . "\">" . $gggBigName['fname'] . " " . $gggBigName['lname'] . "</a>";
// 	} else {
// 		echo "Unkown";
// 	}
// 	echo "</li>\n";

// 			$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $gggBigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$ggggBigName = mysql_fetch_array($result);

// 	echo "<li><strong>Great-Great-Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $ggggBigName['username'] . "\">" . $ggggBigName['fname'] . " " . $ggggBigName['lname'] . "</a>";
// 	} else {
// 		echo "Unkown";
// 	}
// 	echo "</li>\n";

// 			$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $ggggBigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$g4gBigName = mysql_fetch_array($result);
// 	echo "<li><strong>Great-Great-Great-Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $g4gBigName['username'] . "\">" . $g4gBigName['fname'] . " " . $g4gBigName['lname'] . "</a>";
// 	} else {
// 		echo "Unkown";
// 	}
// 	echo "</li>\n";

// 	$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $g4gBigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$g6BigName = mysql_fetch_array($result);
// 	echo "<li><strong>Great-Great-Great-Great-Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $g6BigName['username'] . "\">" . $g6BigName['fname'] . " " . $g6BigName['lname'] . "</a>";
// 	} else {
// 		echo "Unkown";
// 	}
// 	echo "</li>\n";

// 	$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $g6BigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$g7BigName = mysql_fetch_array($result);
// 	echo "<li><strong>Great-Great-Great-Great-Great-Grand-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $g7BigName['username'] . "\">" . $g7BigName['fname'] . " " . $g7BigName['lname'] . "</a>";
// 	} else {
// 		echo "Unkown";
// 	}
// 	echo "</li>\n";

// 	$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $g7BigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$g8BigName = mysql_fetch_array($result);
// 	echo "<li><strong>Great-Great-Great-Great-Great-Great-Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $g8BigName['username'] . "\">" . $g8BigName['fname'] . " " . $g8BigName['lname'] . "</a>";
// 	} else {
// 		echo "Unknown";
// 	}
// 	echo "</li>\n";

// 	$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $g8BigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$g9BigName = mysql_fetch_array($result);
// 	echo "<li><strong>Great-Great-Great-Great-Great-Great-Great-Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $g9BigName['username'] . "\">" . $g9BigName['fname'] . " " . $g9BigName['lname'] . "</a>";
// 	} else {
// 		echo "Unkown";
// 	}
// 	echo "</li>\n";

// 	$q       = "SELECT U.fname, U.lname, U.username, U.big FROM `" . TBL_USERS . "` AS U WHERE U.username = '" . $g9BigName['big'] . "'";
// 	$result  = $database->query($q);
// 	$g10BigName = mysql_fetch_array($result);
// 	echo "<li><strong>Great-Great-Great-Great-Great-Great-Great-Great-Great-Grand Big</strong>: ";
// 	if ($isTransfer && array_key_exists("big",$req_transfer_info)) {
// 		echo $req_transfer_info["big"];
// 	} else if ($database->usernameTaken($req_user_info['big'])) {
// 		echo "<a href=\"?user=" . $g10BigName['username'] . "\">" . $g10BigName['fname'] . " " . $g10BigName['lname'] . "</a>";
// 	} else {
// 		echo "Unkown";
// 	}
// 	echo "</li>\n";

// echo "</div>";



// 	$q      = "SELECT U.username, U.fname, U.lname FROM `" . TBL_USERS . "` AS U WHERE U.big = '" . $req_user_info['username'] . "' ORDER BY U.lname";
// 	$result = $database->query($q);
// 	if (mysql_num_rows($result) > 0) {
// 		echo "<li><strong>Little(s)</strong>: <br /><ul class=\"inline\">";
// 		if ($isTransfer) {
// 			for ($i = 0; $i < count($req_transfer_info["little"]); $i++) {
// 				echo "<li>" . $req_transfer_info["little"][$i] . "</li>";
// 			}
// 		}
// 		while ($row = mysql_fetch_array($result)) {
// 			echo "<li><a href=\"?user=" . $row['username'] . "\">" . $row['fname'] . " " . $row['lname'] . "</a></li>";
// 		} //$row = mysql_fetch_array($result)
// 		echo "</ul></li>\n";
// 	} //mysql_num_rows($result) > 0
// 	echo "</ul>\n"; 
	
//end comment for family line
	
	/* If logged in user viewing own account or Officer, show private info */
	if (strcmp($session->username, $req_user) == 0 || $session->isOfficer()) {
		echo "<h3>Private Info</h3>\n";
		echo "<ul>\n";
		echo "<li><strong>Address</strong>:<br />";
		if ($req_user_info['address'] !== NULL && strlen($req_user_info['address']) > 20) {
			echo nl2br($req_user_info['address']);
		} //$req_user_info['address'] !== NULL && strlen($req_user_info['address']) > 20
		else {
			echo "unknown";
		}
		echo "</li>\n";
		echo "<li><strong><abbr title=\"University of Southern California\">USC</abbr> ID Number</strong>: " . preg_replace("/([0-9]{4})([0-9]{2})([0-9]{4})/", "$1-$2-$3", $req_user_info['uscid']) . "</li>\n";
		echo "<li><strong>T-shirt Size</strong>: ";
		if (strcmp($req_user_info['shirt_size'], "") !== 0) {
			echo $req_user_info['shirt_size'];
		} //strcmp($req_user_info['shirt_size'], "") !== 0
		else {
			echo "unknown";
		}
		echo "</li>\n";
		echo "</ul>\n";
	} //strcmp($session->username, $req_user) == 0 || $session->isOfficer()

	/* If user is viewing own account, show link to edit personal info */
	if (strcmp($session->username, $req_user) === 0) {
		echo "<p>[<a href=\"useredit.php\">Edit Account Information</a>]</p>";
	}
	// $dateSpan = ((strcmp($_GET["span"],"upcoming") === 0) ? "AND E.end >= CURDATE()" : "");
	$queryService  = "SELECT SUM((E.hours*S.weight)) AS weightedHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_EVENTS . "` AS E WHERE S.username = \"" . $req_user_info['username'] . "\" AND S.eventid = E.ID AND E.end < NOW() AND `start` >= '2014-08-22 00:00:00' {$dateSpan} ORDER BY E.start ASC";
	$resultService = $database->query($queryService);

	$dateSpan = ((strcmp($_GET["span"],"upcoming") === 0) ? "AND E.end >= CURDATE()" : "");
	// entire semester, upcoming, all time
	$query  = "SELECT S.username, S.eventid, S.weight, E.id, E.name, E.type, E.start, E.end FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_EVENTS . "` AS E WHERE S.username = \"" . $req_user_info['username'] . "\" AND S.eventid = E.ID AND E.end < NOW() AND `start` >= '2016-08-06 00:00:00' {$dateSpan} ORDER BY E.start ASC";
	if (strcmp($_GET["span"],"upcoming") === 0) {$query  = "SELECT S.username, S.eventid, S.weight, E.id, E.name, E.type, E.start, E.end FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_EVENTS . "` AS E WHERE S.username = \"" . $req_user_info['username'] . "\" AND S.eventid = E.ID AND E.end > NOW() AND `start` >= '2014-08-22 00:00:00' {$dateSpan} ORDER BY E.start ASC";}
	else if (strcmp($_GET["span"],"alltime") === 0) {$query  = "SELECT S.username, S.eventid, S.weight, E.id, E.name, E.type, E.start, E.end FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_EVENTS . "` AS E WHERE S.username = \"" . $req_user_info['username'] . "\" AND S.eventid = E.ID AND E.end < NOW() AND `start` >= '2010-01-01 00:00:00' {$dateSpan} ORDER BY E.start ASC";}
	$retval = $database->query($query);
	 if (mysql_num_rows($retval) >= 0) {
	// REQUIREMENTS SECTION 
		/*echo "<h3>Requirements</h3>\n
		<p><em><strong>Note</strong>: These totals are intended as a guide. Please refer to the Master Doc for exact numbers.</em></p>";
		if ($req_user_info['status'] != ALUMNI_MEMBER) {

			while ($rowService = mysql_fetch_array($resultService)) {
				echo "<p>";
				if($rowService['weightedHours']>=25) {echo "<img src=\"/img/checkmark.png\" height=\"15px\"> ";} 
				else {echo "<img src=\"/img/xmark.png\" height=\"15px\"> ";};				
				echo "<strong>Service</strong>: " . $rowService['weightedHours'] . " Actual Hour";
				if($row['weightedHours']==1) {echo "";} else {echo "s";};
				echo " (25 Required) out of ";
							}
			$q      = "SELECT SUM(E.hours) AS totalHours, E.end FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			while ($row = mysql_fetch_array($result)) {

				echo $row['totalHours'] . " Potential Hour";
				if($row['totalHours']==1) {echo "";} else {echo "s";};
							} //$row = mysql_fetch_array($result)
		}
		if ($req_user_info['status'] != ALUMNI_MEMBER) {
			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 0 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			while ($row = mysql_fetch_array($result)) {
				echo "<strong> @ </strong> " . $row['totalHours'] . " Event";
				if($row['totalHours']==1) {echo "";} else {echo "s";};
				echo "</p>\n";
			} //$row = mysql_fetch_array($result)
		}


			$q      = "SELECT SUM(weight) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 1 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$wgtFel = $row['totalHours'];

			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 1 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$numFel = $row['totalHours'];

			$q      = "SELECT SUM(weight) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 2 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$wgtFun = $row['totalHours'];

			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 2 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$numFun = $row['totalHours'];

			$q      = "SELECT SUM(weight) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 3 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$wgtIch = $row['totalHours'];

			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 3 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$numIch = $row['totalHours'];

		if ($req_user_info['status'] != ALUMNI_MEMBER) {
				echo "<p>";
				if($wgtFel>=5) {echo "<img src=\"/img/checkmark.png\" height=\"15px\"> ";} else {echo "<img src=\"/img/xmark.png\" height=\"15px\"> ";};
				echo "<strong>Fellowship</strong>: ";
				if($wgtFel==0) {echo "0";} else {echo round($wgtFel,1);};				
				echo " Point";
				if($wgtFel==1) {echo "";} else {echo "s";};
				echo " (5 Required) @ ";
				echo $numFel . " Event";
				if($numFel==1) {echo "";} else {echo "s";};
				echo "</p>\n";
		}
		if ($req_user_info['status'] != ALUMNI_MEMBER) {
				echo "<p>";
				if($wgtFun>=3) {echo "<img src=\"/img/checkmark.png\" height=\"15px\"> ";} else {echo "<img src=\"/img/xmark.png\" height=\"15px\"> ";};
				echo "<strong>Fundraising</strong>: ";
				if($wgtFun==0) {echo "0";} else {echo round($wgtFun,1);};				
				echo " Point";
				if($wgtFun==1) {echo "";} else {echo "s";};
				echo " (3 Required) @ ";
				echo $numFun . " Event";
				if($numFun==1) {echo "";} else {echo "s";};
				echo " </p>\n";
		}
		if ($req_user_info['status'] != ALUMNI_MEMBER) {
				echo "<p>";
				if($wgtIch>=2) {echo "<img src=\"/img/checkmark.png\" height=\"15px\"> ";} else {echo "<img src=\"/img/xmark.png\" height=\"15px\"> ";};
				echo "<strong>Interchapter</strong>: ";
				if($wgtIch==0) {echo "0";} else {echo round($wgtIch,1);};				
				echo " Point";
				if($wgtIch==1) {echo "";} else {echo "s";};
				echo " (2 Required) @ ";
				echo $numIch . " Event";
				if($numIch==1) {echo "";} else {echo "s";};
				echo "</p>\n";
		}

			$q      = "SELECT SUM(weight) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 4 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$wgtMemEvs = $row['totalHours'];

			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 4 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$numMemEvs = $row['totalHours'];

			$q      = "SELECT SUM(weight) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND (E.type = 10 OR E.type = 11 OR E.type = 12) AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$wgtFam = $row['totalHours'];

			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND (E.type = 10 OR E.type = 11 OR E.type = 12) AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$numFam = $row['totalHours'];

			$q      = "SELECT SUM(weight) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 6 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$wgtAlu = $row['totalHours'];

			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND E.type = 6 AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$numAlu = $row['totalHours'];

			$q      = "SELECT COUNT(*) AS totalHours FROM `" . TBL_SIGNUPS . "` AS S, `" . TBL_USERS . "` AS U, `" . TBL_EVENTS . "` AS E WHERE E.end < NOW() AND S.eventid = E.ID AND S.username = \"" . $req_user_info['username'] . "\" AND S.username = U.username AND S.lead = 1 AND `start` >= '2013-01-13 00:00:00' GROUP BY S.username LIMIT 1";
			$result = $database->query($q);
			$row = mysql_fetch_array($result);
			$numLea = $row['totalHours'];

$wgtMemPts = $wgtFam + $wgtAlu + $wgtMemEvs;
$numMemPts = $numFam + $numAlu + $numMemEvs;
$numEnr = $numLea;

		if ($req_user_info['status'] != ALUMNI_MEMBER) {
				echo "<p>";
				if($wgtMemPts>=2) {echo "<img src=\"/img/checkmark.png\" height=\"15px\"> ";} else {echo "<img src=\"/img/xmark.png\" height=\"15px\"> ";};				
				echo "<strong>Membership</strong>: " . $wgtMemPts . " Point";
				if($wgtMemPts==1) {echo "";} else {echo "s";};
				echo " (2 Required)</p>\n<ul>";
			 //$row = mysql_fetch_array($result)
		}
		if ($req_user_info['status'] != ALUMNI_MEMBER) {

				echo "<li><strong>Membership</strong>: ";
				if($wgtMemEvs==0) {echo "0";} else {echo round($wgtMemEvs,1);};				
				echo " Point";
				if($wgtMemEvs==1) {echo "";} else {echo "s";};
				echo " @ ";
				if($numMemEvs==0) {echo "0";} else {echo $numMemEvs;};
				echo " Event";
				if($numMemEvs==1) {echo "";} else {echo "s";};
				echo "</li>\n";
		}
		if ($req_user_info['status'] != ALUMNI_MEMBER) {
				echo "<li><strong>Family</strong>: ";
				if($wgtFam==0) {echo "0";} else {echo round($wgtFam,1);};				
				echo " Point";
				if($wgtFam==1) {echo "";} else {echo "s";};
				echo " @ ";
				if($numFam==0) {echo "0";} else {echo $numFam;};
				echo " Event";
				if($numFam==1) {echo "";} else {echo "s";};
				echo "</li>\n";
		}
		if ($req_user_info['status'] != ALUMNI_MEMBER) {
				echo "<li><strong>Alumni</strong>: ";
				if($wgtAlu==0) {echo "0";} else {echo round($wgtAlu,1);};				
				echo " Point";
				if($wgtAlu==1) {echo "";} else {echo "s";};
				echo " @ ";
				if($numAlu==0) {echo "0";} else {echo $numAlu;};				
				echo " Event";
				if($numAlu==1) {echo "";} else {echo "s";};
				echo "</li>\n";
		}
echo "</ul>";
		if ($req_user_info['status'] == PLEDGE_MEMBER) {
				echo "<p>";
				if($numEnr>=4) {echo "<img src=\"/img/checkmark.png\" height=\"15px\"> ";} else {echo "<img src=\"/img/xmark.png\" height=\"15px\"> ";};				
				echo "<strong>Enrichment</strong>: ";
				if($numEnr==0) {echo "0";} else {echo $numEnr;};								
				echo " Point";
				if($numLea==1) {echo "";} else {echo "s";};
				echo " (4 Required)</p>\n<ul>";
				echo "<li><strong>Lead for </strong> ";
				if($numLea==0) {echo "0";} else {echo $numLea;};				
				echo " Event";
				if($numLea==1) {echo "";} else {echo "s";};
				echo "</li>\n";
		echo "</ul>";
		}
		
		//$req_user_info['status'] != ALUMNI_MEMBER
		// $upcomingLink = ((strcmp($_GET["span"],"upcoming") === 0) ? "Upcoming Events" : "<a href=\"userinfo.php?user=".$req_user."&amp;span=upcoming\">Upcoming Events</a>");
		// $semesterLink = ((strcmp($_GET["span"],"semester") === 0) ? "<a href=\"userinfo.php?user=".$req_user."&amp;span=semester\">Entire Semester</a>" : "Entire Semester");
	*/
		echo "<h3>Attendance</h3>\n<ul style= \"margin-left: 0em\">";		
		echo "<p><strong>View</strong>: ";
		if((strcmp($_GET["span"],"semester") === 0)) {echo "Entire Semester";} else {echo "<a href=\"userinfo.php?user=".$req_user."&amp;span=semester\">Entire Semester</a>";}; echo " | ";
		if((strcmp($_GET["span"],"upcoming") === 0)) {echo "All Upcoming";} else {echo "<a href=\"userinfo.php?user=".$req_user."&amp;span=upcoming\">All Upcoming</a>";}; echo " | ";
		if((strcmp($_GET["span"],"alltime") === 0)) {echo "All Events";} else {echo "<a href=\"userinfo.php?user=".$req_user."&amp;span=alltime\">All Events</a>";};
		echo "<table class=\"pretty\">\n";
		echo "\t<thead>\n";
		echo "\t\t<tr>\n";
		echo "\t\t\t<th scope=\"col\">Event Name</th>\n";
		echo "\t\t\t<th scope=\"col\">Type</th>\n";
		echo "\t\t\t<th scope=\"col\">Date</th>\n";
		echo "\t\t\t<th scope=\"col\">Time</th>\n";
		echo "\t\t\t<th scope=\"col\">Credit</th>\n";
		echo "\t\t</tr>\n";
		echo "\t</thead>\n";
		echo "\t<tbody>\n";
		$i = 0; // Counter used for alternating table row colors

		
		
		
		while ($row = mysql_fetch_array($retval)) {
			$zebra = ($i % 2 == 1) ? " class=\"zebra\"" : "";
			echo ("\t\t<tr" . $zebra . ">\n");
			echo ("\t\t\t<td><a href=\"event_page.php?eventid=$row[eventid]\">".htmlspecialchars($row['name'])."</a></td>\n");
			echo ("\t\t\t<td>" . $eventType[$row['type']] . "</td>\n");
			$startDate = date("n/j/y", strtotime($row['start']));
			$endDate   = date("n/j/y", strtotime($row['end']));
			$weight    = $row['weight'];
			if ($startDate == $endDate) {
				$eventDate = $startDate;
			} //$startDate == $endDate
			else {
				$eventDate = $startDate . " &ndash; " . $endDate;
			}
			echo ("\t\t\t<td>$eventDate</td>\n");
			if ($startDate == $endDate) {
				$eventTime = date("g:i A", strtotime($row['start'])) . " &ndash; " . date("g:i A", strtotime($row['end']));
			} //$startDate == $endDate
			else {
				$eventTime = date("g:i A (n/j)", strtotime($row['start'])) . " &ndash; <br />" . date("g:i A (n/j)", strtotime($row['end']));
			}
			echo ("\t\t\t<td>$eventTime</td>\n");
			echo ("\t\t\t<td>$weight</td>\n");
			echo ("\t\t</tr>\n");
			$i++;
		}		
		
		
		
		
		while ($row = mysql_fetch_array($retval)) {
			$zebra = ($i % 2 == 1) ? " class=\"zebra\"" : "";
			echo ("\t\t<tr" . $zebra . ">\n");
			echo ("\t\t\t<td><a href=\"event_page.php?eventid=$row[eventid]\">".htmlspecialchars($row['name'])."</a></td>\n");
			echo ("\t\t\t<td>" . $eventType[$row['type']] . "</td>\n");
			$startDate = date("n/j/y", strtotime($row['start']));
			$endDate   = date("n/j/y", strtotime($row['end']));
			$weight    = $row['weight'];
			if ($startDate == $endDate) {
				$eventDate = $startDate;
			} //$startDate == $endDate
			else {
				$eventDate = $startDate . " &ndash; " . $endDate;
			}
			echo ("\t\t\t<td>$eventDate</td>\n");
			if ($startDate == $endDate) {
				$eventTime = date("g:i A", strtotime($row['start'])) . " &ndash; " . date("g:i A", strtotime($row['end']));
			} //$startDate == $endDate
			else {
				$eventTime = date("g:i A (n/j)", strtotime($row['start'])) . " &ndash; <br />" . date("g:i A (n/j)", strtotime($row['end']));
			}
			echo ("\t\t\t<td>$eventTime</td>\n");
			echo ("\t\t\t<td>$weight</td>\n");
			echo ("\t\t</tr>\n");
			$i++;
		} //$row = mysql_fetch_array($retval)
		echo "\t</tbody>\n";
		echo "</table>\n";
	} //mysql_num_rows($retval) > 0
	
	/**
	 * Note: when you add your own fields to the users table
	 * to hold more information, like homepage, location, etc.
	 * they can be easily accessed by the user info array.
	 *
	 * $session->user_info['location']; (for logged in users)
	 *
	 * ..and for this page,
	 *
	 * $req_user_info['location']; (for any user)
	 */
}
?>
<?php
##################################################
// Load sidebar, footer, and dropdown stats panel
include_once("include/sidebarFooterDropdownpanel.php");
?>
