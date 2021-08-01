</div>
<div id="sidebar">

<!--<div id="log-in-section">-->

<?php
// If user is logged in, show sidebar navigation menu
if ($session->logged_in) {
?>


<?php
if ($session->status == FROZEN_MEMBER) {
// If user is frozen, display warning message.
	echo "<p><strong>Your account has been frozen. Please pay your dues.</strong></p>\n";
}
?>

<ul id="sideNav">

	
<div class='highlighter central'><a href='../members/events.php?span=soon'>Events</a></div>
<!-- <div class='highlighter central'><a href='https://docs.google.com/forms/d/1WPuEn5Ugs8KoZP6G2HFXmvuTBXBI4qovEXDigOYv2-M/viewform'>Midsemester Evaluations</a></div> -->
<!--<div class='highlighter central'><a href='https://docs.google.com/forms/d/150rJ-P-9wlTcwxY2VvjzdXrHLvZmp5KEykqfun0wLUU/viewform'>Evaluations</a></div>  -->
<!-- If you want to add a big red button, create new div with class 'highlighter central' -->
<!-- Election time links -->


 <div class='highlighter central'>

<!--      <li><a href="../election/nominations.php">Nominations</a></li> -->
<!--      <li><a href="../election/election_documents.php">Candidate Documents</a></li> -->
</div>
		<li>
		<?php
			echo "<h5><a href='../members/userinfo.php?user=" . $session->username . "' title='View/edit user info'>" . $session->fname . " " . $session->lname . "</a></h5>" ?>
			<!-<ul>
				<!--<li><a href="useredit.php">Edit My Info</a></li>
				<li><a href="process.php">Log Out</a></li>
			</ul>-->
		</li>

		<li>
			<h5><a href="../members/home.php">Home Page</a></h5>
		</li>

		
<?php
// If Webmaster is using admin account, show additional options
if ($session->isAdmin()) {
	echo <<< HERE
		<li>
			<h5>Admin Tools</h5>
			<ul>
				<li><a href="../admin/admincpanel.php">Admin Control Panel</a></li>
				<li><a href="../admin/csv_tools/add_biglittle.php">Add Bigs and Littles</a></li>
				<li><a href="../admin/csv_tools/add_excomm.php">Add Excomm</a></li>
				<li><a href="../admin/csv_tools/add_pledge_excomm.php">Add Pledge Excomm</a></li>
				<li><a href="../admin/csv_tools/add_pledges.php">Add Pledges</a></li>
				<li><a href="../admin/csv_tools/cross_pledges.php">Cross Pledges</a></li>
				<li><a href="../admin/admin.php">Admin Center</a></li>
				<li><a href="../admin/register.php">Add Member</a></li>
				<li><a href="../admin/admin_manual.php">Website User Manual</a></li>
				<li><a href="../admin/webmaster_info.php">Webmaster Info</a></li>
			</ul>
		</li>
HERE;
}
?>

<?php
// If user is an officer, show Officer Tools
if ($session->isOfficer()) {
	echo <<< HERE
		<li>
			<h5>Eboard Tools</h5>
			<ul>
				<li><a href="../eboard/add_event.php">Add Event</a></li>
				<li><a href="../eboard/add_announcement.php">Add Announcement</a></li>
				<!-- <li><a href="../eboard/add_pic.php">Add Picture of the Week</a></li> -->
				<!--<li><a href='../eboard/add_poll.php'>Add Poll</a></li>-->
			</ul>
		</li>
HERE;
}
?>

<li>
	<h5>Information</h5>
	<ul>
		<!--Only needed during Officer Nominations period -->
<!-- 		<li><a href="../election/nominations.php"><strong>Nominations</strong></a></li> -->
<!-- 		<li><a href="../election/election_documents.php"><strong>Candidate Documents</strong></a></li>  -->
<!-- 		<li><a href="https://docs.google.com/document/d/1TP5goDQeJ1hpN6Z9H2wlY6DAevSg_3r2_hMZnui6f6k/edit?usp=sharing"><strong>A-Board Applications</strong></a></li> -->
		<!--Always Keep-->
		</b><li><a href="https://docs.google.com/spreadsheets/d/1TG97SIIQFumIAWo5SEQAretQbvu1ahjOHoHLSt5_pTA/edit#gid=0">Master Doc</a></li>
		<li><a href="../information/finance.php">Finance</a></li>
		<!--<li><a href="https://drive.google.com/file/d/0B2ce5YDkshNjQzVwMGFWQURkVF9telZiNzJoaGpvT05kU0Mw/view?usp=sharing">Intern Application</a></li>-->	
		<li><a href="../information/announcements.php">Announcements</a></li>
		<li><a href="../information/documents.php">Documents</a></li>
		<li><a href="../information/forms.php">Forms</a></li>
		<li><a href="../information/academic_resources.php">Academic Resources</a></li>
		<!--<li><a href="requirements.php">Requirements</a></li>-->
		<!-- <li><a href="../information/archives.php">Archives</a></li> -->
		<!--<li><a href="beyond.php">Beyond Alpha Kappa</a></li>-->
		<!-- <li><li><a href='polls.php'>Polls</a></li>-->
		<li><a href="../information/support_systems.php">Support Systems</a></li>
		<li><a href="https://docs.google.com/spreadsheets/d/1jfhuzV2HDxURCe9_0iO9MG2JGiwk3wevOd2ezXmaT-M/edit?usp=sharing"><strong>!!Fellowship Reccuring Series</strong></a></li>
		<li><a href="https://www.facebook.com/apoakcompliments/"><strong>!!APO Compliments</strong></a></li>
		<li><a href="https://www.youtube.com/watch?v=M0Kf1X7gTIg&feature=youtu.be"><strong>!!APO Discord Tutorial</strong></a></li>
		<li><a href="https://linktr.ee/apousc"><strong>!!APO Linktree</strong></a></li>
	</ul>
</li>

<li>
	<h5>Rosters</h5>
	<ul>
		<li><a href="../rosters/roster.php">All Members</a></li>
		<li><a href="../rosters/excomm_a.php">Executive Committee</a></li>
<!-- 		<li><a href="../rosters/excomm_p.php">New Member Ex Comm</a></li> -->
		<li><a href="../rosters/alumni.php">Alumni</a></li>
		<!--<li><a href="https://airtable.com/shrl0bWZ6eINW28Ec" target="_blank">Alumni Networking Database</a></li>-->
<!-- 		<li><a href="../rosters/family_tree.php?user=all">Family Trees</a></li> -->
	</ul>
</li>

<?php
// If user is an alumni, show alumni-specific information
if ($session->status == ALUMNI_MEMBER || $session->isAdmin()) {
	echo <<< HERE
		<li>
			<h5>Alumni</h5>
			<ul>
				<li><a href="../members/alumni_newsletter.php">Alumni Newsletter</a></li>
			</ul>
		</li>
HERE;
}
?>
 
<li>
    <h5><a href="../members/process.php">Log Out</a></h5>
</li>
 

<li>
	<h5>Social</h5>
	<ul id="social">
		<li class="icon top">
		<a href="https://instagram.com/apousc/" rel="external" title="Check out our #insta"><img src="img/instagram2019.png" height="32" width="32" alt="Instagram" /></a>
		<a href="https://www.facebook.com/apousc/" rel="external" title="Join us on Facebook"><img src="img/facebook2019.png" height="32" width="32" alt="Facebook" /></a>
		<a href="https://discord.com/invite/TB5HAeSsW5" rel="external" title="Join our Discord"><img src="img/DiscordLogo2021.png" height="32" width="32" alt="Discord" /></a>
			<a href="http://twitter.com/apousc/" rel="external" title="Follow us on Twitter"><img src="img/twitter2019.png" height="32" width="32" alt="Twitter" /></a>
			<!--<a href="https://www.google.com/calendar/embed?src=j2gk9k44u7q6qfegvnjs20f5pvj9qflg%40import.calendar.google.com&ctz=America/Los_Angeles" rel="external" title="View our Google Calendar"><img src="img/google.png" height="32" width="32" alt="Google Calendar" /></a>-->
		<a href="http://www.youtube.com/user/apousc/" rel="external" title="Check out our YouTube channel"><img src="../img/youtube2019.png" height="32" width="32" alt="YouTube" /></a>
	    <a href="http://www.apousc.org/main/" rel="external" title="View our official website"><img src="../img/APOlogo2019.png" height="32" width="32" alt="Website" /></a>
		</li>
	</ul>
</li>

</ul>

<?php
}
// Or else, if user is not logged in, show sign in form
else {
?>
	<form action="../members/process.php" method="post" id="loginform" class="inField">
		<fieldset>
			<h4>Member Login</h4>
				<ol>
					<li>Username &nbsp;
						<input type="text" id="user" name="user" maxlength="30" placeholder="Username" />
						<?php if ($form->num_errors > 0) { echo "<p>".$form->error("user")."</p>"; } ?>
					</li>
					<li>Password &nbsp;
						<input type="password" id="pass" name="pass" maxlength="30" placeholder="Password" />
						<?php if ($form->num_errors > 0) { echo "<p>".$form->error("pass")."</p>"; } ?>
					</li>
					<li>
						<input type="checkbox" id="remember" name="remember" checked="checked" />
						<label for="remember">Remember me</label>
					</li>
				</ol>
			<input type="hidden" name="sublogin" value="1" />
			
			<button type="submit" class="buttonSmaller">Go!</button>
			<br><br>

<?php
// If there are errors, display Forgot Password? link
if ($form->num_errors > 0) {
// 	echo "<p><a href=\"forgotpass.php\">Forgot Password?</a></p>";
    echo "<p>* Contact webdaddy if you forgot your password smh</p><br>";
}
?>

		</fieldset>
	</form> 
	
<ul id="sideNav">
	<li><h5>Stay Connected</h5>
	<ul id="social">
		<li class="icon top">
		<a href="https://instagram.com/apousc/" rel="external" title="Check out our #insta"><img src="img/instagram2019.png" height="32" width="32" alt="Instagram" /></a>
		<a href="https://www.facebook.com/apousc/" rel="external" title="Join us on Facebook"><img src="img/facebook2019.png" height="32" width="32" alt="Facebook" /></a>
		<a href="https://discord.com/invite/TB5HAeSsW5" rel="external" title="Join our Discord"><img src="img/DiscordLogo2021.png" height="32" width="32" alt="Discord" /></a>
			<a href="http://twitter.com/apousc/" rel="external" title="Follow us on Twitter"><img src="img/twitter2019.png" height="32" width="32" alt="Twitter" /></a>
			<!--<a href="https://www.google.com/calendar/embed?src=j2gk9k44u7q6qfegvnjs20f5pvj9qflg%40import.calendar.google.com&ctz=America/Los_Angeles" rel="external" title="View our Google Calendar"><img src="img/google.png" height="32" width="32" alt="Google Calendar" /></a>-->
		<a href="http://www.youtube.com/user/apousc/" rel="external" title="Check out our YouTube channel"><img src="../img/youtube2019.png" height="32" width="32" alt="YouTube" /></a>
	    <a href="http://www.apousc.org/main/" rel="external" title="View our official website"><img src="../img/APOlogo2019.png" height="32" width="32" alt="Website" /></a>
		</li>
	</ul>
	</li>
</ul>

<?php }//close else ?>
		</div>
<div id="bottomCap">
	<p class="small"><strong>National Disclaimer:</strong> This electronic document is intended for public viewing and is solely for personal reference. It should not be considered an authoritative source nor an official publication of <a href="http://www.apo.org/" rel="external">Alpha Phi Omega</a>. Inquiries regarding Alpha Phi Omega and its official publications may be directed to: Alpha Phi Omega, 14901 E. 42nd Street, Independence, <abbr title="Missouri">MO</abbr>, 64055 &ndash; USA. &quot;Alpha Phi Omega&quot; is a copyrighted, registered trademark in the USA. All rights reserved.</p>
</div>
</div>
</div>

<div id="footer">
	<p>&copy; 2018, Alpha Phi Omega&mdash;Alpha Kappa</p>
	<p>Design adapted from "Time Manager" template by <a href="http://www.templateworld.com/" rel="external">Template World</a>.</p>
</div>

</body>
</html>
