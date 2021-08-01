<?php
/**
 * Session.php
 * 
 * The Session class is meant to simplify the task of keeping
 * track of logged in users and also guests.
 *
 * Written by: Jpmaster77 a.k.a. The Grandmaster of C++ (GMC)
 * Last Updated: August 19, 2004
 *
 * Modified by: Brad Ramos (bradleyRamos@gmail.com)
 * Last Updated: November 2011
 */
include_once("database.php");
include_once("mailer.php");
include_once("form.php");
include_once("email_validator.php");
include_once("constants.php");
include_once("ereg-wrapper.php");
class Session
{
	var $username; //Username given on sign-up
	var $userid; //Random value generated on current login
	var $userlevel; //The level to which the user pertains
	var $time; //Time user was last active (page loaded)
	var $logged_in; //True if user is logged in, false otherwise
	var $userinfo = array(); //The array holding all user info
	var $url; //The page url current being viewed
	var $referrer; //Last recorded site page viewed

	/**
	 * Note: referrer should really only be considered the actual
	 * page referrer in process.php, any other time it may be
	 * inaccurate.
	 */

	/* Class constructor */
	function Session()
	{
		$this->time = time();
		$this->startSession();
	}

	/**
	 * startSession - Performs all the actions necessary to 
	 * initialize this session object. Tries to determine if the
	 * the user has logged in already, and sets the variables 
	 * accordingly. Also takes advantage of this page load to
	 * update the active visitors tables.
	 */
	function startSession()
	{
		global $database; //The database connection
		//Tell PHP to start the session if it wasn't already started
		if (!isset($_SESSION)) {
		    session_start();
		}

		/* Determine if user is logged in */
		$this->logged_in = $this->checkLogin();

		/**
		 * Set guest value to users not logged in, and update
		 * active guests table accordingly.
		 */
		if (!$this->logged_in) {
			$this->username  = $_SESSION['username'] = GUEST_NAME;
			$this->userlevel = GUEST_LEVEL;
			$database->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);
		}
		/* Update users last active timestamp */
		else {
			$database->addActiveUser($this->username, $this->time);
		}

		/* Remove inactive visitors from database */
		$database->removeInactiveUsers();
		$database->removeInactiveGuests();

		/* Set referrer page */
		if (isset($_SESSION['url'])) {
			$this->referrer = $_SESSION['url'];
		} else {
			$this->referrer = "/";
		}

		/* Set current url */
		$queryString = "";
		if (strlen($_SERVER['QUERY_STRING']) > 0) {
			$queryString = "?" . $_SERVER['QUERY_STRING'];
		}
		$this->url = $_SESSION['url'] = $_SERVER['PHP_SELF'] . $queryString;
	}

	/**
	 * checkLogin - Checks if the user has already previously
	 * logged in, and a session with the user has already been
	 * established. Also checks to see if user has been remembered.
	 * If so, the database is queried to make sure of the user's 
	 * authenticity. Returns true if the user has logged in.
	 */
	function checkLogin()
	{
		global $database; //The database connection

		/* Check if user has been remembered */
		if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookid'])) {
			$this->username = $_SESSION['username'] = $_COOKIE['cookname'];
			$this->userid   = $_SESSION['userid'] = $_COOKIE['cookid'];
		}

		/* Username and userid have been set and not guest */
		if (isset($_SESSION['username']) && isset($_SESSION['userid']) && $_SESSION['username'] != GUEST_NAME) {
			/* Confirm that username and userid are valid */
			if ($database->confirmUserID($_SESSION['username'], $_SESSION['userid']) != 0) {
				/* Variables are incorrect, user not logged in */
				unset($_SESSION['username']);
				unset($_SESSION['userid']);
				return false;
			}

			/* User is logged in, set class variables */
			
			$this->userinfo  = $database->getUserInfo($_SESSION['username']);
			
			$this->username  = $this->userinfo['username'];
			$this->userid    = $this->userinfo['userid'];
			$this->userlevel = $this->userinfo['userlevel'];
			$this->fname     = $this->userinfo['fname'];
			$this->lname     = $this->userinfo['lname'];
			$this->position  = $this->userinfo['position'];

			$this->status    = $this->userinfo['status'];
			if($this->username ==  "jasperch" ||  $this->username ==  "nnoh") {
					echo '<script language="javascript">';
					echo 'alert("PLEASE PAY YOUR DUES OR YOU WILL NOT BE ABLE TO SIGN IN. \nPlease email or notify the VP of Membership for any concerns you may have.")';
					echo '</script>';
					return false;
			}
			else return true;
		}
		/* User not logged in */
		else {
			return false;
		}
	}

	/**
	 * login - The user has submitted his username and password
	 * through the login form, this function checks the authenticity
	 * of that information in the database and creates the session.
	 * Effectively logging in the user if all goes well.
	 */
	function login($subuser, $subpass, $subremember)
	{
		global $database, $form; //The database and form object

		/* Username error checking */
		$field = "user"; //Use field name for username
		if (!$subuser || strlen($subuser = trim($subuser)) == 0) {
			$form->setError($field, "* Username not entered");
		} else {
			/* Check if username is not alphanumeric */
			if (!eregi("^([0-9a-z])*$", $subuser)) {
				$form->setError($field, "* Username not alphanumeric");
			}
		}

		/* Password error checking */
		$field = "pass"; //Use field name for password
		if (!$subpass || strlen($subpass = trim($subpass)) == 0) {
			$form->setError($field, "* Password not entered");
		}

		/* Return if form errors exist */
		if ($form->num_errors > 0) {
			return false;
		}

		/* Checks that username is in database and password is correct */
		if (@get_magic_quotes_gpc()) {
			$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
		}
		$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
		$subuser = (string) $subuser; // Force $subuser to be a string
		if (@get_magic_quotes_gpc()) {
			$subpass = stripslashes($subpass); // Removes magic_quotes_gpc slashes
		}
		$subpass = mysql_real_escape_string($subpass); // Prepends backslashes to special MySQL characters
		$subpass = (string) $subpass; // Force $subpass to be a string
		$result  = $database->confirmUserPass($subuser, md5(sha1($subpass . PASSWORD_SALT)));

		/* Check error codes */
		if ($result == 1) {
			$field = "user";
			$form->setError($field, "* Username not found");
		} else if ($result == 2) {
			$field = "pass";
			$form->setError($field, "* Invalid password");
		}

		/* Return if form errors exist */
		if ($form->num_errors > 0) {
			return false;
		}

		/* Username and password correct, register session variables */
		$this->userinfo  = $database->getUserInfo($subuser);
		$this->username  = $_SESSION['username'] = $this->userinfo['username'];
		$this->userid    = $_SESSION['userid'] = $this->generateRandID();
		$this->userlevel = $this->userinfo['userlevel'];

		/* Insert userid into database and update active users table */
		$database->updateUserField($this->username, "userid", $this->userid);
		$database->addActiveUser($this->username, $this->time);
		$database->removeActiveGuest($_SERVER['REMOTE_ADDR']);

		/**
		 * This is the cool part: the user has requested that we remember that
		 * he's logged in, so we set two cookies. One to hold his username,
		 * and one to hold his random value userid. It expires by the time
		 * specified in constants.php. Now, next time he comes to our site, we will
		 * log him in automatically, but only if he didn't log out before he left.
		 */
		if ($subremember) {
			setcookie("cookname", $this->username, time() + COOKIE_EXPIRE, COOKIE_PATH);
			setcookie("cookid", $this->userid, time() + COOKIE_EXPIRE, COOKIE_PATH);
		}

		/* Login completed successfully */
		return true;
	}

	/**
	 * logout - Gets called when the user wants to be logged out of the
	 * website. It deletes any cookies that were stored on the users
	 * computer as a result of him wanting to be remembered, and also
	 * unsets session variables and demotes his user level to guest.
	 */
	function logout()
	{
		global $database; //The database connection
		
		/**
		 * Delete cookies - the time must be in the past,
		 * so just negate what you added when creating the
		 * cookie.
		 */
		if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookid'])) {
			setcookie("cookname", "", time() - COOKIE_EXPIRE, COOKIE_PATH);
			setcookie("cookid", "", time() - COOKIE_EXPIRE, COOKIE_PATH);
		}

		/* Unset PHP session variables */
		unset($_SESSION['username']);
		unset($_SESSION['userid']);

		/* Reflect fact that user has logged out */
		$this->logged_in = false;

		/**
		 * Remove from active users table and add to
		 * active guests tables.
		 */
		$database->removeActiveUser($this->username);
		$database->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);

		/* Set user level to guest */
		$this->username  = GUEST_NAME;
		$this->userlevel = GUEST_LEVEL;
	}

	/**
	 * register - Gets called when the user has just submitted the
	 * registration form. Determines if there were any errors with
	 * the entry fields, if so, it records the errors and returns
	 * 1. If no errors were found, it registers the new user and
	 * returns 0. Returns 2 if registration failed.
	 */
	function register($subuser, $substatus, $subemail, $subfname, $sublname, $subfamily, $subsemester, $subyear, $subposition, $subbig, $subphone, $subuscid, $subaddress, $subshirt)
	{
		global $database, $form, $mailer; //The database, form and mailer object
		global $officers, $families, $memberStatus; //Links to constants.php file

		/* Username error checking */
		$field = "txt_User"; // Use field name for username
		if (!$subuser || strlen($subuser = trim($subuser)) == 0) {
			$form->setError($field, "* Username not entered");
		} else {
			/* Spruce up username, check length */
			if (@get_magic_quotes_gpc()) {
				$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
			}
			$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
			$subuser = (string) $subuser; // Force $subuser to be a string
			if (strlen($subuser) < 1) {
				$form->setError($field, "* Username must be more than 0 characters");
			} else if (strlen($subuser) > 30) {
				$form->setError($field, "* Username must be less than 30 characters");
			}
			/* Check if username is not alphanumeric */
			else if (!eregi("^([0-9a-z])+$", $subuser)) {
				$form->setError($field, "* Username not alphanumeric");
			}
			/* Check if username is reserved */
			else if (strcasecmp($subuser, GUEST_NAME) == 0) {
				$form->setError($field, "* Username reserved word");
			}
			/* Check if username is already in use */
			else if ($database->usernameTaken($subuser)) {
				$form->setError($field, "* Username already in use");
			}
			/* Check if username is banned */
			else if ($database->usernameBanned($subuser)) {
				$form->setError($field, "* Username banned");
			}
		}

		/* Member Status checking */
		$field = "sel_Status"; // Use field name for status
		if ($substatus == "" || !is_numeric($substatus) || !array_key_exists((int) $substatus, $memberStatus)) {
			$form->setError($field, "* You must select a member status");
		}
		$substatus = (int) $substatus; // Force $substatus to be an integer

		/* Email error checking */
		$field = "txt_Email"; //Use field name for email
		if (!$subemail || strlen(trim($subemail)) == 0) {
			$form->setError($field, "* You must enter an e-mail address");
		} else {
			/* Check if valid email address */
			$validator = new EmailAddressValidator;
			if (!$validator->check_email_address($subemail)) {
				$form->setError($field, "* Please enter a valid e-mail address");
			}
			$subemail = trim($subemail);
			if (@get_magic_quotes_gpc()) {
				$subemail = stripslashes($subemail); // Removes magic_quotes_gpc slashes
			}
			$subemail = mysql_real_escape_string($subemail); // Prepends backslashes to special MySQL characters
			$subemail = (string) $subemail; // Force $subemail to be a string
		}

		/* First name error checking */
		$field    = "txt_Fname"; // Use field name for fname
		$subfname = trim($subfname);
		if (!$subfname || strlen($subfname) == 0) {
			$form->setError($field, "* First name not entered");
		} else if (strlen($subfname) > 20) {
			$form->setError($field, "* First name must be less than 21 characters");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subfname = stripslashes($subfname); // Removes magic_quotes_gpc slashes
			}
			$subfname = mysql_real_escape_string($subfname); // Prepends backslashes to special MySQL characters
			$subfname = (string) $subfname; // Force $subfname to be a string
		}

		/* Last name error checking */
		$field    = "txt_Lname"; // Use field name for lname
		$sublname = trim($sublname);
		if (!$sublname || strlen($sublname) == 0) {
			$form->setError($field, "* Last name not entered");
		} else if (strlen($sublname) > 20) {
			$form->setError($field, "* Last name must be less than 31 characters");
		} else {
			if (@get_magic_quotes_gpc()) {
				$sublname = stripslashes($sublname); // Removes magic_quotes_gpc slashes
			}
			$sublname = mysql_real_escape_string($sublname); // Prepends backslashes to special MySQL characters
			$sublname = (string) $sublname; // Force $sublname to be a string
		}

		/* Family error checking */
		if (trim($subfamily) == "") {
			$subfamily = "NULL"; // If family is not selected, set family value to NULL
		} else if (!is_numeric($subfamily) || !array_key_exists((int) $subfamily, $families)) {
			return 1; // Return error if format of family data is not as expected
		} else {
			$subfamily = (int) $subfamily; // Force $subfamily to be an integer
		}

		/* Semester error checking */
		$field = "sel_Semester"; // Use field name for semester
		if (!is_numeric($subsemester) || (int) $subsemester < 0 || (int) $subsemester > 1) {
			$form->setError($field, "* You must select a semester");
		}
		$subsemester = (int) $subsemester; // Force $subsemester to be an integer
		
		/* Year error checking */
		$field   = "txt_Year"; // Use field name for year
		$subyear = trim($subyear);
		if (!is_numeric($subyear) || (int) $subyear < 1900 || (int) $subyear > 3000 || strlen($subyear) != 4) {
			$form->setError($field, "* Please enter a valid 4-digit year");
		}
		$subyear = (int) $subyear; // Force $subyear to be an integer
		

		/* Position error checking */
		if (!is_numeric($subposition)) {
			return 1; // Return error if format of position data is not as expected
		} else if ($subposition != "0" && !array_key_exists((int) $subposition, $officers)) {
			return 1; // Return error if format of position data is not as expected
		}
		$subposition = (int) $subposition; // Force $subposition to be an integer

		/* Big error checking */
		$subbig = trim($subbig);
		if (@get_magic_quotes_gpc()) {
			$subbig = stripslashes($subbig); // Removes magic_quotes_gpc slashes
		}
		$subbig = mysql_real_escape_string($subbig); // Prepends backslashes to special MySQL characters
		$subbig = (string) $subbig; // Force $subbig to be a string
		if ($subbig != "" && !$database->usernameTaken($subbig)) {
			return 1; // Return error if big's username does not exist
		}

		/* Phone number error checking */
		$field    = "tel_Phone"; // Use field name for phone
		$subphone = preg_replace("/\D/", "", $subphone); // Strip all non-numeric characters
		if ($subphone != "") {
			if (!is_numeric($subphone) || strlen($subphone) !== 10) {
				$form->setError($field, "* Please enter a valid 10-digit phone number");
			}
		}
		$subphone = (string) $subphone; // Force $subphone to be a string

		/* USC ID Number error checking */
		$field    = "txt_USCID"; // Use field name for USCID
		$subuscid = preg_replace("/\D/", "", $subuscid); // Strip all non-numeric characters
		if ($subuscid != "") {
			if (!is_numeric($subuscid) || strlen($subuscid) !== 10) {
				$form->setError($field, "* Please enter a valid 10-digit USC ID number");
			}
		}
		$subuscid = (string) $subuscid; // Force $subuscid to be a string

		/* Mailing Address error checking */
		$field      = "txtarea_Address"; // Use field name for mailing address
		$subaddress = trim($subaddress);
		if (@get_magic_quotes_gpc()) {
			$subaddress = stripslashes($subaddress); // Removes magic_quotes_gpc slashes
		}
		$subaddress = mysql_real_escape_string($subaddress); // Prepends backslashes to special MySQL characters
		if (strcmp($subaddress,"") !== 0) {
			if (strlen($subaddress) < 21) {
				$form->setError($field, "* Please enter a valid mailing address");
			}
		}
		$subaddress = (string) $subaddress; // Force $subaddress to be a string

		/* T-shirt Size error checking */
		$field       = "sel_Shirt"; // Use field name for shirt size
		$subshirt    = (string) trim($subshirt);
		$shirt_sizes = array("XXS","XS","S","M","L","XL","2XL","3XL");
		if (strlen($subshirt) > 0) {
			if (!in_array($subshirt,$shirt_sizes)) {
				return 1; // Return error. No error message needed because user is attempting to submit values not possible from form.
			}
		}

		/* Default password "recipe" */
		$subpass           = $subuser."@apousc4lfs"; // Generate default password
		$subpass_hash_salt = md5(sha1($subpass.PASSWORD_SALT)); // Hash and salt password before inputting into database

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new account to the */
		else {
			if ($database->addNewUser($subuser, $substatus, $subemail, $subfname, $sublname, $subfamily, $subsemester, $subyear, $subposition, $subbig, $subphone, $subuscid, $subaddress, $subshirt, $subpass_hash_salt)) {
				if (EMAIL_WELCOME) {
					$mailer->sendWelcome($subuser, $subemail, $subpass, $subfname, $sublname);
				}
				return 0; //New user added succesfully
			} else {
				return 2; //Registration attempt failed
			}
		}
	}

	/**
	 * editAccount - Attempts to edit the user's account information
	 * including the password, which it first makes sure is correct
	 * if entered, if so and the new password is in the right
	 * format, the change is made. All other fields are changed
	 * automatically.
	 */
	function editAccount($subcurpass, $subnewpass, $subnewpass2, $subemail, $subphone, $subaddress, $subshirt, $subgraduation, $reminder, $calendar)
	{
		global $database, $form; //The database and form object
		$password_minimum_length = 8; // Change minimum password length here

		/* New password entered */
		if ($subnewpass) {
			/* Current Password error checking */
			$field = "pass_Curpass"; //Use field name for current password
			if (!$subcurpass) {
				$form->setError($field, "* Current Password not entered");
			} else {
				/* Check if password too short */
				$subcurpass = trim($subcurpass); // Trims whitespace from beginning and end
				if (@get_magic_quotes_gpc()) {
					$subcurpass = stripslashes($subcurpass); // Removes magic_quotes_gpc slashes
				}
				$subcurpass = mysql_real_escape_string($subcurpass); // Prepends backslashes to special MySQL characters
				$subcurpass = (string) $subcurpass; // Force $subcurpass to be a string
				if (strlen($subcurpass) < $password_minimum_length) {
					$form->setError($field, "* Current Password incorrect");
				}
				/* Password entered is incorrect */
				if ($database->confirmUserPass($this->username, md5(sha1($subcurpass . PASSWORD_SALT))) != 0) {
					$form->setError($field, "* Current Password incorrect");
				}
			}

			/* New Password error checking */
			$field      = "pass_Newpass"; //Use field name for new password
			
			/* Spruce up password and check length*/
			$subnewpass = trim($subnewpass); // Trims whitespace from beginning and end
			if (@get_magic_quotes_gpc()) {
				$subnewpass = stripslashes($subnewpass); // Removes magic_quotes_gpc slashes
			}
			$subnewpass = mysql_real_escape_string($subnewpass); // Prepends backslashes to special MySQL characters
			$subnewpass = (string) $subnewpass; // Force $subnewpass to be a string
			if (strlen($subnewpass) < $password_minimum_length) {
				$form->setError($field, "* New Password must be at least $password_minimum_length characters long");
			}

			/* New Password2 error checking */
			$field  = "pass_Newpass"; //Use field name for new password
			$field2 = "pass_Newpass2"; // Second field for new password2
			if (!$subnewpass2) {
				$form->setError($field2, "* Password not entered");
			} else {
				$subnewpass2 = trim($subnewpass2); // Trims whitespace from beginning and end
				if (@get_magic_quotes_gpc()) {
					$subnewpass2 = stripslashes($subnewpass2); // Removes magic_quotes_gpc slashes
				}
				$subnewpass2 = mysql_real_escape_string($subnewpass2); // Prepends backslashes to special MySQL characters
				$subnewpass2 = (string) $subnewpass2; // Force $subnewpass2 to be a string
				if (strcmp($subnewpass, $subnewpass2) != 0) {
					$form->setError($field2, "* Passwords do not match");
				}
			}
		}
		/* Change password attempted */
		else if ($subcurpass) {
			/* New Password error reporting */
			$field = "pass_Newpass"; //Use field name for new password
			$form->setError($field, "* New Password not entered");
		}

		/* Email error checking */
		$field = "txt_Email"; //Use field name for email
		if ($subemail && strlen($subemail = trim($subemail)) > 0) {
			/* Check if valid email address */
			$validator = new EmailAddressValidator;
			if (!$validator->check_email_address($subemail)) {
				$form->setError($field, "* Please enter a valid e-mail address");
			}
			if (@get_magic_quotes_gpc()) {
				$subemail = stripslashes($subemail); // Removes magic_quotes_gpc slashes
			}
			$subemail = mysql_real_escape_string($subemail); // Prepends backslashes to special MySQL characters
			$subemail = (string) $subemail; // Force $subemail to be a string
		}

		/* Phone number error checking */
		$field    = "tel_Phone"; // Use field name for phone
		$subphone = preg_replace("/\D/", "", $subphone); // Strip all non-numeric characters
		if (strlen($subphone) > 0) {
			if (!is_numeric($subphone) || strlen($subphone) !== 10) {
				$form->setError($field, "* Please enter a valid 10-digit phone number");
			}
		}
		$subphone = (string) $subphone; // Force $subphone to be a string

		/* Mailing Address error checking */
		$field      = "txtarea_Address"; // Use field name for mailing address
		$subaddress = trim($subaddress);
		if (@get_magic_quotes_gpc()) {
			$subaddress = stripslashes($subaddress); // Removes magic_quotes_gpc slashes
		}
		$subaddress = mysql_real_escape_string($subaddress); // Prepends backslashes to special MySQL characters
		if (strcmp($subaddress,"") !== 0) {
			if (strlen($subaddress) < 21) {
				$form->setError($field, "* Please enter a valid mailing address");
			}
		}
		$subaddress = (string) $subaddress; // Force $subaddress to be a string

		/* T-shirt Size error checking */
		$field       = "sel_Shirt"; // Use field name for shirt size
		$subshirt    = (string) trim($subshirt);
		$shirt_sizes = array("XXS","XS","S","M","L","XL","2XL","3XL");
		if (strlen($subshirt) > 0) {
			if (!in_array($subshirt,$shirt_sizes)) {
				return 1; // Return error. No error message needed because user is attempting to submit values not possible from form.
			}
		}

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return false; //Errors with form
		} else {
			/* Update password since there were no errors */
			if ($subcurpass && $subnewpass) {
				$database->updateUserField($this->username, "password", md5(sha1($subnewpass . PASSWORD_SALT)));
			}

			/* Change Email */
			if ($subemail) {
				$database->updateUserField($this->username, "email", $subemail);
			}
			
			/* Change Phone Number */
			if ($subphone) {
				$database->updateUserField($this->username, "phone", $subphone);
			}

			/* Change Address */
			if ($subaddress) {
				$database->updateUserField($this->username, "address", $subaddress);
			}

			/* Change T-shirt Size */
			if ($subshirt) {
				$database->updateUserField($this->username, "shirt_size", $subshirt);
			}
			
			if ($subgraduation) {
				$database->updateGraduation($this->username, $subgraduation);
			}
			
			$database->updateReminder($this->username, $reminder);

			$database->updateCalendarSettings($this->username, $calendar);

			/* Success! */
			return true;
		}
	}

	/**
	 * adminEditAccount - Admin's ability to edit all of a user's info except password. The password is fully controlled by the user.
	 * If the user forgets their password, they can simply use the "Forgot Password" routine, thus limiting the admin user's ability
	 * to learn users' private passwords.
	 */
	function adminEditAccount($subuser, $subfamily, $subsemester, $subyear, $subposition, $subbig, $subuscid, $subemail, $subphone, $subaddress, $subshirt)
	{
		global $database, $form; //The database and form object
		global $officers, $families, $memberStatus; //Links to constants.php file

		/* Family error checking */
		if (trim($subfamily) == "") {
			$subfamily = "NULL"; // If family is not selected, set family value to NULL
		} else if (!is_numeric($subfamily) || !array_key_exists((int) $subfamily, $families)) {
			return 1; // Return error if format of family data is not as expected
		} else {
			$subfamily = (int) $subfamily; // Force $subfamily to be an integer
		}

		/* Semester error checking */
		$field = "sel_Semester"; // Use field name for semester
		if (!is_numeric($subsemester) || (int) $subsemester < 0 || (int) $subsemester > 1) {
			$form->setError($field, "* You must select a semester");
		}
		$subsemester = (int) $subsemester; // Force $subsemester to be an integer

		/* Year error checking */
		$field   = "txt_Year"; // Use field name for year
		$subyear = trim($subyear);
		if (!is_numeric($subyear) || (int) $subyear < 1900 || (int) $subyear > 3000 || strlen($subyear) != 4) {
			$form->setError($field, "* Please enter a valid 4-digit year");
		}
		$subyear = (int) $subyear; // Force $subyear to be an integer

		/* Position error checking */
		if (!is_numeric($subposition)) {
			return 1; // Return error if format of position data is not as expected
		} else if ($subposition != "0" && !array_key_exists((int) $subposition, $officers)) {
			return 1; // Return error if format of position data is not as expected
		}
		$subposition = (int) $subposition; // Force $subposition to be an integer

		/* Big error checking */
		$subbig = trim($subbig);
		if (@get_magic_quotes_gpc()) {
			$subbig = stripslashes($subbig); // Removes magic_quotes_gpc slashes
		}
		$subbig = mysql_real_escape_string($subbig); // Prepends backslashes to special MySQL characters
		$subbig = (string) $subbig; // Force $subbig to be a string
		if ($subbig != "" && !$database->usernameTaken($subbig)) {
			return 1; // Return error if big's username does not exist
		}

		/* USC ID Number error checking */
		$field    = "txt_USCID"; // Use field name for USCID
		$subuscid = preg_replace("/\D/", "", $subuscid); // Strip all non-numeric characters
		if ($subuscid != "") {
			if (!is_numeric($subuscid) || strlen($subuscid) !== 10) {
				$form->setError($field, "* Please enter a valid 10-digit USC ID number");
			}
		}
		$subuscid = (string) $subuscid; // Force $subuscid to be a string

		/* Email error checking */
		$field = "txt_Email"; //Use field name for email
		if ($subemail && strlen($subemail = trim($subemail)) > 0) {
			/* Check if valid email address */
			$validator = new EmailAddressValidator;
			if (!$validator->check_email_address($subemail)) {
				$form->setError($field, "* Please enter a valid e-mail address");
			}
			if (@get_magic_quotes_gpc()) {
				$subemail = stripslashes($subemail); // Removes magic_quotes_gpc slashes
			}
			$subemail = mysql_real_escape_string($subemail); // Prepends backslashes to special MySQL characters
			$subemail = (string) $subemail; // Force $subemail to be a string
		}

		/* Phone number error checking */
		$field    = "tel_Phone"; // Use field name for phone
		$subphone = preg_replace("/\D/", "", $subphone); // Strip all non-numeric characters
		if (strlen($subphone) > 0) {
			if (!is_numeric($subphone) || strlen($subphone) !== 10) {
				$form->setError($field, "* Please enter a valid 10-digit phone number");
			}
		}
		$subphone = (string) $subphone; // Force $subphone to be a string

		/* Mailing Address error checking */
		$field      = "txtarea_Address"; // Use field name for mailing address
		$subaddress = trim($subaddress);
		if (@get_magic_quotes_gpc()) {
			$subaddress = stripslashes($subaddress); // Removes magic_quotes_gpc slashes
		}
		$subaddress = mysql_real_escape_string($subaddress); // Prepends backslashes to special MySQL characters
		if (strcmp($subaddress,"") !== 0) {
			if (strlen($subaddress) < 21) {
				$form->setError($field, "* Please enter a valid mailing address");
			}
		}
		$subaddress = (string) $subaddress; // Force $subaddress to be a string

		/* T-shirt Size error checking */
		$field       = "sel_Shirt"; // Use field name for shirt size
		$subshirt    = (string) trim($subshirt);
		$shirt_sizes = array("XXS","XS","S","M","L","XL","2XL","3XL");
		if (strlen($subshirt) > 0) {
			if (!in_array($subshirt,$shirt_sizes)) {
				return 1; // Return error. No error message needed because user is attempting to submit values not possible from form.
			}
		}

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return false; //Errors with form
		} else {
			/* Change Family */
			if ($subfamily) {
				$database->updateUserField($subuser, "family", $subfamily);
			}

			/* Change Semester */
			if ($subsemester) {
				$database->updateUserField($subuser, "semester", $subsemester);
			}

			/* Change Year */
			if ($subyear) {
				$database->updateUserField($subuser, "year", $subyear);
			}

			/* Change Position */
			if ($subposition) {
				$database->updateUserField($subuser, "position", $subposition);
			}

			/* Change Big */
			if ($subbig) {
				$database->updateUserField($subuser, "big", $subbig);
			}

			/* Change USC ID */
			if ($subuscid) {
				$database->updateUserField($subuser, "uscid", $subuscid);
			}

			/* Change Email */
			if ($subemail) {
				$database->updateUserField($subuser, "email", $subemail);
			}
			
			/* Change Phone Number */
			if ($subphone) {
				$database->updateUserField($subuser, "phone", $subphone);
			}

			/* Change Address */
			if ($subaddress) {
				$database->updateUserField($subuser, "address", $subaddress);
			}

			/* Change T-shirt Size */
			if ($subshirt) {
				$database->updateUserField($subuser, "shirt_size", $subshirt);
			}

			/* Success! */
			return true;
		}
	}

	/**
	 * addEvent - Adds an event to the database
	 */
	function addEvent($subtitle, $subtype, $subeventdesc, $substartmonth, $substartday, $substartyear, $substarthour, $substartminute, $substartampm, $subendmonth, $subendday, $subendyear, $subendhour, $subendminute, $subendampm, $subhours, $submax, $subdiffday, $sublimited, $subwalk, $submeet, $sublocation, $subaddress, $checkedrepeat, $substartmonth2, $substartday2, $substartmonth3, $substartday3, $substartmonth4, $substartday4, $substartmonth5, $substartday5)
	{
		global $database, $form; // The database and form object

		/* Check Event Name */
		$subtitle = trim($subtitle); // Trims whitespace from the beginning and end of string
		if (strlen($subtitle) < 1) {
			$form->setError("txt_Name", "* You must enter an event name");
		} else if (strlen($subtitle) > 50) { // Database is only set to hold 50 characters
			$form->setError("txt_Name", "* Your event name is too long! (<50 characters, please)");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subtitle = stripslashes($subtitle); // Removes magic_quotes_gpc slashes
			}
			$subtitle = mysql_real_escape_string($subtitle); // Prepends backslashes to special MySQL characters
			$subtitle = (string) $subtitle; // Force $subtitle to be a string
		}

		/* Check Event Type */
		global $eventType; // Use $eventType variable from constants.php
		if ((int) $subtype < 0 || !is_numeric($subtype) || (int) $subtype > count($eventType)) {
			$form->setError("sel_Type", "* You must select an event type");
		} else {
			$subtype = (int) $subtype; // Force $subtype to be an integer
		}

		/* Check Event Description */
		$subeventdesc = trim($subeventdesc); // Trims whitespace from beginning and end of string
		if (strlen($subeventdesc) < 1) {
			$form->setError("txtarea_eventDesc", "* Please enter an Event Description");
		} else if (strlen($subeventdesc) > 21845) { // Database can only hold ~64KB of data
			$form->setError("txtarea_eventDesc", "* Your Event Description is too long!");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subeventdesc = stripslashes($subeventdesc); // Removes magic_quotes_gpc slashes
			}
			$subeventdesc = mysql_real_escape_string($subeventdesc); // Prepends backslashes to special MySQL characters
			$subeventdesc = (string) $subeventdesc; // Force $subtitle to be a string
		}
// without the following code, error messages  about the time show up
date_default_timezone_set('PST');
		/* Check Start Month, Day, and Year */
		if (!is_numeric($substartmonth) || !is_numeric($substartday) || !is_numeric($substartyear)) {
			return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
		} else {
			date_default_timezone_set('America/Los_Angeles');
			$stamp = strtotime($substartmonth . "-" . $substartday . "-" . $substartyear);
			$month = date("m", $stamp);
			$day   = date("d", $stamp);
			$year  = date("Y", $stamp);
			if (!checkdate($month, $day, $year)) {
				$form->setError("startDate", "* You must enter a valid start date");
			}
			//REPEAT  DIDNT DO IT FOR 4
			/*
			if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday2 <= 31))   {
				$stamp2 = strtotime($substartmonth2 . "-" . $substartday2 . "-" . $substartyear);
			}
			if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday3 <= 31))   {
				$stamp3 = strtotime($substartmonth3 . "-" . $substartday3 . "-" . $substartyear);
			}
			*/
		}

		/* Check Start Time */
		if (!is_numeric($substarthour) || (int) $substarthour > 12 || (int) $substarthour < 1 || !is_numeric($substartminute) || (int) $substartminute > 55 || (int) $substartminute < 0 || !is_numeric($substartampm) || (int) $substartampm > 1 || (int) $substartampm < 0) {
			return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
		}
		if ((int)$substarthour === 12) { // Accounts for 12AM = 0:00 and 12PM = 12:00
			$substarthour = (int) $substarthour - 12;
		}
		$substarthour = (int) $substarthour + (12 * (int) $substartampm); // Change 12hr format to 24hr

		/* Combine and Sanitize Start Date and Time */
		$stamp         = strtotime($substartyear . "-" . $substartmonth . "-" . $substartday . " " . $substarthour . ":" . $substartminute . ":00");
		$subeventstart = date("Y-m-d H:i:s", $stamp);
		//REPEAT
		if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday2 <= 31))   {
			$stamp2         = strtotime($substartyear . "-" . $substartmonth2 . "-" . $substartday2 . " " . $substarthour . ":" . $substartminute . ":00");
			$subeventstart2 = date("Y-m-d H:i:s", $stamp2);
		}
		if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday3 <= 31))   {
			$stamp3         = strtotime($substartyear . "-" . $substartmonth3 . "-" . $substartday3 . " " . $substarthour . ":" . $substartminute . ":00");
			$subeventstart3 = date("Y-m-d H:i:s", $stamp3);
		}
		if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday4 <= 31))   {
			$stamp4         = strtotime($substartyear . "-" . $substartmonth4 . "-" . $substartday4 . " " . $substarthour . ":" . $substartminute . ":00");
			$subeventstart4 = date("Y-m-d H:i:s", $stamp4);
		}
		if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday5 <= 31))   {
			$stamp5         = strtotime($substartyear . "-" . $substartmonth5 . "-" . $substartday5 . " " . $substarthour . ":" . $substartminute . ":00");
			$subeventstart5 = date("Y-m-d H:i:s", $stamp5);
		}

		// escaping SQL special characters for some parameters
		$sublocation = mysql_real_escape_string($sublocation);
		$submeet = mysql_real_escape_string($submeet);
		$subaddress = mysql_real_escape_string($subaddress);
		
		/* Check End Time */
		if (!is_numeric($subendhour) || (int) $subendhour > 12 || (int) $subendhour < 1 || !is_numeric($subendminute) || (int) $subendminute > 55 || (int) $subendminute < 0 || !is_numeric($subendampm) || (int) $subendampm > 1 || (int) $subendampm < 0) {
			return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
		}
		if ((int)$subendhour === 12) { // Accounts for 12AM = 0:00 and 12PM = 12:00
			$subendhour = (int) $subendhour - 12;
		}
		$subendhour = (int) $subendhour + (12 * (int) $subendampm); // Change 12hr format to 24hr

		/* Check End Month, Day, and Year */
		if (strcmp($subdiffday,"TRUE") === 0) { // IF event ends on different day than it starts on
			if (!is_numeric($subendmonth) || !is_numeric($subendday) || !is_numeric($subendyear)) {
				return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
			} else {
				$stamp = strtotime($subendmonth . "-" . $subendday . "-" . $subendyear);
				$month = date("m", $stamp);
				$day   = date("d", $stamp);
				$year  = date("Y", $stamp);
				if (!checkdate($month, $day, $year)) {
					$form->setError("endDate", "* You must enter a valid end date");
				}
			}
			/* Combine and Sanitize End Date and Time */
			$stamp       = strtotime($subendyear . "-" . $subendmonth . "-" . $subendday . " " . $subendhour . ":" . $subendminute . ":00");
			$subeventend = date("Y-m-d H:i:s", $stamp);
		} else { // IF event ends on the same day that it begins on
			/* Combine and Sanitize Start Date and  End Time */
			$stamp       = strtotime($substartyear . "-" . $substartmonth . "-" . $substartday . " " . $subendhour . ":" . $subendminute . ":00");
			$subeventend = date("Y-m-d H:i:s", $stamp);
			if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday2 <= 31))   {
				$stamp2         = strtotime($substartyear . "-" . $substartmonth2 . "-" . $substartday2 . " " . $subendhour . ":" . $subendminute . ":00");
				$subeventend2 = date("Y-m-d H:i:s", $stamp2);
			}
			if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday3 <= 31))   {
				$stamp3         = strtotime($substartyear . "-" . $substartmonth3 . "-" . $substartday3 . " " . $subendhour . ":" . $subendminute . ":00");
				$subeventend3 = date("Y-m-d H:i:s", $stamp3);
			}
			if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday4 <= 31))   {
				$stamp4         = strtotime($substartyear . "-" . $substartmonth4 . "-" . $substartday4 . " " . $subendhour . ":" . $subendminute . ":00");
				$subeventend4 = date("Y-m-d H:i:s", $stamp4);
			}
			if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday5 <= 31))   {
				$stamp5         = strtotime($substartyear . "-" . $substartmonth5 . "-" . $substartday5 . " " . $subendhour . ":" . $subendminute . ":00");
				$subeventend5 = date("Y-m-d H:i:s", $stamp5);
			}
		}

		/* Check that Event End Come AFTER Event Start */
		$start = strtotime($subeventstart);
		$end   = strtotime($subeventend);
		if ($start - $end >= 0) {
			$form->setError("endTime", "* The \"Event End\" must come <strong>after</strong> the \"Event Start\"");
		}

		/* Check Hours */
		if ($subtype == 0 || $subtype == 13) { // If event is a service event, set hours as selected number of hours
			$subhours = trim($subhours); // Trims whitespace from beginning and end of string
			if (!is_numeric($subhours) || (float) $subhours < 0 || (float) $subhours > 24) {
				return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
			} else {
				$subhours = (float) $subhours; // Force $subhours to be a float
			}
		} else { // If event is not a service event
			$subhours = (int) 0;
		}

		/* Check if Limited Space */
		if (strcmp($sublimited,"TRUE") === 0) {
			$submax = trim($submax); // Trims whitespace from beginning and end of string
			if (!is_numeric($submax) || $submax < 0 || $submax > 999) {
				$form->setError("txt_Spaces", "* Please enter a valid integer");
			} else {
				$submax = (int) $submax; // Force $submax to be an integer
			}
		} else {
			$submax = (int) 0; // If "Limited spaces" box is not checked, assume there is no maximum (0)
		}
		
		
		
		
		/*
		$submeet = trim($submeet); // Trims whitespace from the beginning and end of string
		if (strlen($submeet) < 1) {
			$form->setError("txt_Meet_Location", "* You must enter an event name");
		} else if (strlen($submeet) > 50) { // Database is only set to hold 50 characters
			$form->setError("txt_Meet_Location", "* Your event name is too long! (<50 characters, please)");
		} else {
			if (@get_magic_quotes_gpc()) {
				$submeet = stripslashes($submeet); // Removes magic_quotes_gpc slashes
			}
			$submeet = mysql_real_escape_string($submeet); // Prepends backslashes to special MySQL characters
			$submeet = (string) $submeet; // Force $subtitle to be a string
		}
		*/
		
		
		
		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new event to the database */
		else {
			if ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday2 <= 31) && ($substartday3 <= 31) && ($substartday4 <= 31) && ($substartday5 <= 31))   {
				if (($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart, $subeventend, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart2, $subeventend2, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart3, $subeventend3, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart4, $subeventend4, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart5, $subeventend5, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress))) {
					return 0; //New user added succesfully
				}
				else {
					return 2; //Registration attempt failed
				}
			}
			elseif ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday2 <= 31) && ($substartday3 <= 31) && ($substartday4 <= 31))   {
				if (($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart, $subeventend, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart2, $subeventend2, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart3, $subeventend3, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart4, $subeventend4, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress))) {
					return 0; //New user added succesfully
				}
				else {
					return 2; //Registration attempt failed
				}
			}
			elseif ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday2 <= 31) && ($substartday3 <= 31))   {
				if (($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart, $subeventend, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart2, $subeventend2, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart3, $subeventend3, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress))) {
					return 0; //New user added succesfully
				}
				else {
					return 2; //Registration attempt failed
				}
			}
			elseif ((strcmp($checkedrepeat,"TRUE") === 0) && ($substartday2 <= 31))   {
				if (($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart, $subeventend, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) && ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart2, $subeventend2, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress))) {
					return 0; //New user added succesfully
				}
				else {
					return 2; //Registration attempt failed
				}
			}
			else	{
				if ($database->addNewEvent($subtitle, $subtype, $subeventdesc, $subeventstart, $subeventend, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) {
					return 0; //New user added succesfully
				} else {
					return 2; //Registration attempt failed
				}
			}
		}

		// Insert calendar 
	}

	/**
	 * editEvent - Edits an event in the database
	 */
	function editEvent($subeventid, $subtitle, $subtype, $subeventdesc, $substartmonth, $substartday, $substartyear, $substarthour, $substartminute, $substartampm, $subendmonth, $subendday, $subendyear, $subendhour, $subendminute, $subendampm, $subhours, $submax, $subdiffday, $sublimited, $subwalk, $submeet, $sublocation, $subaddress)
	{
		global $database, $form; // The database and form object

		/* Check Event ID */
		if (!$database->eventExists(preg_replace('/\D/', '', $subeventid))) {
			return 1; // Errors with form
		}

		/* Check Event Name */
		$subtitle = trim($subtitle); // Trims whitespace from the beginning and end of string
		if (strlen($subtitle) < 1) {
			$form->setError("txt_Name", "* You must enter an event name");
		} else if (strlen($subtitle) > 50) { // Database is only set to hold 50 characters
			$form->setError("txt_Name", "* Your event name is too long!");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subtitle = stripslashes($subtitle); // Removes magic_quotes_gpc slashes
			}
			$subtitle = mysql_real_escape_string($subtitle); // Prepends backslashes to special MySQL characters
			$subtitle = (string) $subtitle; // Force $subtitle to be a string
		}

		/* Check Event Type */
		global $eventType; // Use $eventType variable from constants.php
		if ((int) $subtype < 0 || !is_numeric($subtype) || (int) $subtype > count($eventType)) {
			$form->setError("sel_Type", "* You must select an event type");
		} else {
			$subtype = (int) $subtype; // Force $subtype to be an integer
		}

		/* Check Event Description */
		$subeventdesc = trim($subeventdesc); // Trims whitespace from beginning and end of string
		if (strlen($subeventdesc) < 1) {
			$form->setError("txtarea_eventDesc", "* Please enter an Event Description");
		} else if (strlen($subeventdesc) > 21845) { // Database can only hold ~64KB of data
			$form->setError("txtarea_eventDesc", "* Your Event Description is too long!");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subeventdesc = stripslashes($subeventdesc); // Removes magic_quotes_gpc slashes
			}
			$subeventdesc = mysql_real_escape_string($subeventdesc); // Prepends backslashes to special MySQL characters
			$subeventdesc = (string) $subeventdesc; // Force $subtitle to be a string
		}
date_default_timezone_set('America/Los_Angeles');
		/* Check Start Month, Day, and Year */
		if (!is_numeric($substartmonth) || !is_numeric($substartday) || !is_numeric($substartyear)) {
			return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
		} else {
			date_default_timezone_set('America/Los_Angeles');
			$stamp = strtotime($substartmonth . "-" . $substartday . "-" . $substartyear);
			$month = date("m", $stamp);
			$day   = date("d", $stamp);
			$year  = date("Y", $stamp);
			if (!checkdate($month, $day, $year)) {
				$form->setError("startDate", "* You must enter a valid start date");
			}
		}

		// escaping SQL special characters for some parameters
		$sublocation = mysql_real_escape_string($sublocation);
		$submeet = mysql_real_escape_string($submeet);
		$subaddress = mysql_real_escape_string($subaddress);

		/* Check Start Time */
		if (!is_numeric($substarthour) || (int) $substarthour > 12 || (int) $substarthour < 1 || !is_numeric($substartminute) || (int) $substartminute > 55 || (int) $substartminute < 0 || !is_numeric($substartampm) || (int) $substartampm > 1 || (int) $substartampm < 0) {
			return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
		}
		if ((int)$substarthour === 12) { // Accounts for 12AM = 0:00 and 12PM = 12:00
			$substarthour = (int) $substarthour - 12;
		}
		$substarthour = (int) $substarthour + (12 * (int) $substartampm); // Change 12hr format to 24hr

		/* Combine and Sanitize Start Date and Time */
		$stamp         = strtotime($substartyear . "-" . $substartmonth . "-" . $substartday . " " . $substarthour . ":" . $substartminute . ":00");
		$subeventstart = date("Y-m-d H:i:s", $stamp);


		
		/* Check End Time */
		if (!is_numeric($subendhour) || (int) $subendhour > 12 || (int) $subendhour < 1 || !is_numeric($subendminute) || (int) $subendminute > 55 || (int) $subendminute < 0 || !is_numeric($subendampm) || (int) $subendampm > 1 || (int) $subendampm < 0) {
			return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
		}
		if ((int)$subendhour === 12) { // Accounts for 12AM = 0:00 and 12PM = 12:00
			$subendhour = (int) $subendhour - 12;
		}
		$subendhour = (int) $subendhour + (12 * (int) $subendampm); // Change 12hr format to 24hr

		/* Check End Month, Day, and Year */
		if (strcmp($subdiffday,"TRUE") === 0) { // IF event ends on different day than it starts on
			if (!is_numeric($subendmonth) || !is_numeric($subendday) || !is_numeric($subendyear)) {
				return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
			} else {
				date_default_timezone_set('PST');
				$stamp = strtotime($subendmonth . "-" . $subendday . "-" . $subendyear);
				$month = date("m", $stamp);
				$day   = date("d", $stamp);
				$year  = date("Y", $stamp);
				if (!checkdate($month, $day, $year)) {
					$form->setError("endDate", "* You must enter a valid end date");
				}
			}
			/* Combine and Sanitize End Date and Time */
			$stamp       = strtotime($subendyear . "-" . $subendmonth . "-" . $subendday . " " . $subendhour . ":" . $subendminute . ":00");
			$subeventend = date("Y-m-d H:i:s", $stamp);
		} else { // IF event ends on the same day that it begins on
			/* Combine and Sanitize Start Date and  End Time */
			$stamp       = strtotime($substartyear . "-" . $substartmonth . "-" . $substartday . " " . $subendhour . ":" . $subendminute . ":00");
			$subeventend = date("Y-m-d H:i:s", $stamp);
		}

		

		/* Check that Event End Comes AFTER Event Start */
		date_default_timezone_set('PST');
		$start = strtotime($subeventstart);
		$end   = strtotime($subeventend);
		if ($start - $end >= 0) {
			$form->setError("endTime", "* The \"Event End\" must come <strong>after</strong> the \"Event Start\"");
		}

		/* Check Hours */
		if ($subtype == 0 || $subtype == 13) { // If event is a service event, set hours as selected number of hours
			$subhours = trim($subhours); // Trims whitespace from beginning and end of string
			if (!is_numeric($subhours) || (float) $subhours < 0 || (float) $subhours > 24) {
				return 1; // If any of these are true, user is submitting values not possible from the form, so setError message not needed
			} else {
				$subhours = (float) $subhours; // Force $subhours to be a float
			}
		} else { // If event is not a service event
			$subhours = (int) 0;
		}

		/* Check if Limited Space */
		if (strcmp($sublimited,"TRUE") === 0) {
			$submax = trim($submax); // Trims whitespace from beginning and end of string
			if (!is_numeric($submax) || $submax < 0 || $submax > 999) {
				$form->setError("txt_Spaces", "* Please enter a valid integer");
			} else {
				$submax = (int) $submax; // Force $submax to be an integer
			}
		} else {
			$submax = (int) 0; // If "Limited spaces" box is not checked, assume there is no maximum (0)
		}

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new event to the database */
		else {
			if ($database->editExistingEvent($subeventid, $subtitle, $subtype, $subeventdesc, $subeventstart, $subeventend, $subhours, $submax, $subwalk, $submeet, $sublocation, $subaddress)) {
				return 0; //New user added succesfully
			} else {
				return 2; //Registration attempt failed
			}
		}
	}

	/**
	 * eventSignup - Adds a user signup to the signups table
	 */
	function eventSignup($subuser, $subeventid, $subdrive, $sublead, $subweight, $subguest)
	{
		global $database, $form; // The database and form object

		/* Username error checking */
		$subuser = (string) ereg_replace("[^A-Za-z0-9]", "", $subuser); // Strip all non-alphanumeric characters and force to string
		if ($database->usernameTaken($subuser) !== TRUE) {
			return 1; // Errors with form
		}

		/* EventID error checking */
		$subeventid = (int) preg_replace("/\D/", "", $subeventid); // Strip all non-numeric characters and force to int
		if ($database->eventExists($subeventid) !== TRUE) {
			return 1; // Errors with form
		}

		/* Drive? error checking */
		$field    = "txt_Drive"; // Use field name for Drive?
		$subdrive = preg_replace("/\D/", "", $subdrive); // Strip all non-numeric characters
		if ($subdrive == "" || (int)$subdrive < 0 || (int)$subdrive > 99) {
			$form->setError($field, "Please enter a valid number of people you can drive");
		}
		
		/* Lead error checking */
		if ($sublead !== TRUE && $sublead !== FALSE) {
			return 1; // Errors with form
		} else if ($sublead == TRUE) {
			$sublead = (int) 1;
		} else {
			$sublead = (int) 0;
		}

		/* Set signup timestamp */
		$subtime = time();

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the signup to the database */
		else {
			if ($database->addEventSignup($subuser, $subeventid, $subdrive, $sublead, $subweight, $subguest, $subtime)) {
				return 0; //Event signup added succesfully
			} else {
				return 2; //Event signup attempt failed
			}
		}
	}

	function delEvent($subevent)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		$database->rmEvent($subevent);
	}
	
	function changeDrive($subevent, $subuser, $subdrive)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		$database->diffDrive($subevent, $subuser, $subdrive);
	}

	function guestAllow($subevent, $suballowguest)
	{
		global $database;
		$database->guestAllowance($subevent, $suballowguest);
	}

	function changeGuest($subevent, $subuser, $subguest)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		$database->diffGuest($subevent, $subuser, $subguest);
	}

	function changeLead($subevent, $subuser, $sublead)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		$database->diffLead($subevent, $subuser, $sublead);
	}

	/**
	 * flake - Assigns a weight of -0.5 to a SERVICE event sign up
	 */
	function flake($subevent, $subuser)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		if (@get_magic_quotes_gpc()) {
			$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
		}
		$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
		$subuser = (string) $subuser; // Force $subuser to be a string
		$database->flakeUser($subevent, $subuser);
	}

	/**
	 * flakeOther - Assigns a weight of -1 to a NON-SERVICE event sign up
	 */
	function flakeOther($subevent, $subuser)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		if (@get_magic_quotes_gpc()) {
			$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
		}
		$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
		$subuser = (string) $subuser; // Force $subuser to be a string
		$database->flakeOtherUser($subevent, $subuser);
	}

	/**
	 * deleteSignup - Removes a user's entry from the `signups` table
	 */
	function deleteSignup($subevent, $subuser)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		if (@get_magic_quotes_gpc()) {
			$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
		}
		$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
		$subuser = (string) $subuser; // Force $subuser to be a string
		$database->removeSignup($subevent, $subuser);
	}

	/**
	 * weightHalf - Assigns a weight of 0.5 to a sign up
	 */
	function weightHalf($subevent, $subuser)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		if (@get_magic_quotes_gpc()) {
			$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
		}
		$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
		$subuser = (string) $subuser; // Force $subuser to be a string
		$database->assignWeightHalf($subevent, $subuser);
	}

	/**
	 * weightNormal - Assigns a weight of 1.0 to a sign up
	 */
	function weightNormal($subevent, $subuser)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		if (@get_magic_quotes_gpc()) {
			$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
		}
		$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
		$subuser = (string) $subuser; // Force $subuser to be a string
		$database->assignWeightNormal($subevent, $subuser);
	}

	/**
	 * weightDouble - Assigns a weight of 2.0 to a sign up
	 */
	function weightDouble($subevent, $subuser)
	{
		global $database;
		$subevent = preg_replace('/\D/', '', $subevent); // Remove all non-numeric characters
		if (@get_magic_quotes_gpc()) {
			$subuser = stripslashes($subuser); // Removes magic_quotes_gpc slashes
		}
		$subuser = mysql_real_escape_string($subuser); // Prepends backslashes to special MySQL characters
		$subuser = (string) $subuser; // Force $subuser to be a string
		$database->assignWeightDouble($subevent, $subuser);
	}

	/**
	 * addAnnouncement() - Adds an announcement to the `announcements` table
	 */
	function addAnnouncement($subtitle, $subbody)
	{
		global $database, $form;
		
		/* Announcement Title Error Checking */
		$subtitle = trim($subtitle); // Trims whitespace from the beginning and end of string
		if (strlen($subtitle) < 1) {
			$form->setError("txt_Title", "* You must enter an announcement title");
		} else if (strlen($subtitle) > 60) { // Database is only set to hold 60 characters
			$form->setError("txt_Title", "* Your announcement title is too long! (<60 characters, please)");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subtitle = stripslashes($subtitle); // Removes magic_quotes_gpc slashes
			}
			$subtitle = mysql_real_escape_string($subtitle); // Prepends backslashes to special MySQL characters
			$subtitle = (string) $subtitle; // Force $subtitle to be a string
		}

		/* Announcement Body Error Checking */
		$subbody = trim($subbody); // Trims whitespace from beginning and end of string
		if (strlen($subbody) < 1) {
			$form->setError("txtarea_Body", "* Please enter an announcement");
		} else if (strlen($subbody) > 21845) { // Database can only hold ~64KB of data
			$form->setError("txtarea_Body", "* Your announcement is too long!");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subbody = stripslashes($subbody); // Removes magic_quotes_gpc slashes
			}
			//$subbody = mysql_real_escape_string($subbody); // Prepends backslashes to special MySQL characters
			$subbody = (string) $subbody; // Force $subtitle to be a string
		}
		
		/* Set Announcement Post Date */
		
		
		date_default_timezone_set('America/Los_Angeles');
		 $subtime = date("Y-m-d",time()); //Formats current time to the format YYYY-MM-DD
		
		file_put_contents('php://stderr', print_r("ERROR DEBUG: subtitle is " . $subtitle . ", subbody is " . $subbody . ", subtime is " . $subtime, TRUE));
		
		
		
		
		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new announcement to the database */
		else {
			if ($database->addNewAnnouncement($subtitle,$subbody,$subtime)) {
				return 0; //Event signup added succesfully
			} else {
				return 2; //Event signup attempt failed
			}
		}
	}

	/**
	 * Edit announcement - Edits existing announcement in the `announcements` table
	 */
	function editAnnouncement($subtitle,$subbody,$subid)
	{
		global $database, $form;
		
		/* Announcement Title Error Checking */
		$subtitle = trim($subtitle); // Trims whitespace from the beginning and end of string
		if (strlen($subtitle) < 1) {
			$form->setError("txt_Title", "* You must enter an announcement title");
		} else if (strlen($subtitle) > 60) { // Database is only set to hold 60 characters
			$form->setError("txt_Title", "* Your announcement title is too long! (<60 characters, please)");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subtitle = stripslashes($subtitle); // Removes magic_quotes_gpc slashes
			}
			$subtitle = mysql_real_escape_string($subtitle); // Prepends backslashes to special MySQL characters
			$subtitle = (string) $subtitle; // Force $subtitle to be a string
		}

		/* Announcement Body Error Checking */
		$subbody = trim($subbody); // Trims whitespace from beginning and end of string
		if (strlen($subbody) < 1) {
			$form->setError("txtarea_Body", "* Please enter an announcement");
		} else if (strlen($subbody) > 21845) { // Database can only hold ~64KB of data
			$form->setError("txtarea_Body", "* Your announcement is too long!");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subbody = stripslashes($subbody); // Removes magic_quotes_gpc slashes
			}
			$subbody = mysql_real_escape_string($subbody); // Prepends backslashes to special MySQL characters
			$subbody = (string) $subbody; // Force $subtitle to be a string
		}

		/* Announcement ID Error Checking */
		$subid = preg_replace('/\D/', '', $subid); // Remove all non-numeric characters

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new announcement to the database */
		else {
			if ($database->editExistingAnnouncement($subtitle,$subbody,$subid)) {
				return 0; //Event signup added succesfully
			} else {
				return 2; //Event signup attempt failed
			}
		}
	}

	/**
	 * deleteAnnouncement - Removes an announcement from the `announcements` table
	 */
	function deleteAnnouncement($subid)
	{
		global $database;
		$subid = preg_replace('/\D/', '', $subid); // Remove all non-numeric characters
		$database->removeAnnouncement($subid);
	}

	/**
	 * addComment - Adds a comment to the `comments` table
	 */
	function addComment($subusername,$subeventid,$subcomment)
	{
		global $database, $form;

		/* Check username */
		$subusername = (string) ereg_replace("[^A-Za-z0-9]", "", $subusername); // Strip all non-alphanumeric characters and force to string
		if ($database->usernameTaken($subusername) !== TRUE) {
			return 1; // Errors with form
		}

		/* Check event id */
		$subeventid = (int) preg_replace("/\D/", "", $subeventid); // Strip all non-numeric characters and force to int
		if ($database->eventExists($subeventid) !== TRUE) {
			return 1; // Errors with form
		}

		/* Check comment text */
		$subcomment = trim($subcomment); // Trims whitespace from beginning and end of string
		if (strlen($subcomment) < 1) {
			$form->setError("txtarea_CommentBox", "* Please enter a comment");
		} else if (strlen($subcomment) > 21845) { // Database can only hold ~64KB of data
			$form->setError("txtarea_CommentBox", "* Your comment is too long!");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subcomment = stripslashes($subcomment); // Removes magic_quotes_gpc slashes
			}
			$subcomment = mysql_real_escape_string($subcomment); // Prepends backslashes to special MySQL characters
			$subcomment = (string) $subcomment; // Force $subtitle to be a string
		}

		/* Set timestamp */
		$subtime = time();

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new announcement to the database */
		else {
			if ($database->addNewComment($subusername,$subeventid,$subcomment,$subtime)) {
				return 0; //Event signup added succesfully
			} else {
				return 2; //Event signup attempt failed
			}
		}
	}

	/**
	 * contactUs - Submits a comment from the contact form
	 */
	function contactUs($subname,$subemail,$subphone,$subsubject,$submessage)
	{
		global $mailer, $form;

		/* Error check name */
		$field = "txt_Name"; // Use field name for Name
		$subname = (string) trim($subname); // Trims whitespace from the beginning and end of string
		if (strlen($subname) < 1) {
			$form->setError($field, "* You must enter your name");
		} else if (strlen($subname) > 40) {
			$form->setError($field, "* Your name is too long (<40 characters, please)");
		}
		
		/* Email error checking */
		$field = "txt_Email"; // Use field name for email
		$subemail = (string) trim($subemail);
		$validator = new EmailAddressValidator;
		if (!$validator->check_email_address($subemail)) {
			$form->setError($field, "* Please enter a valid e-mail address");
		}
		
		/* Phone number error checking */
		$field    = "tel_Phone"; // Use field name for phone
		$subphone = (string) preg_replace("/\D/", "", $subphone); // Strip all non-numeric characters
		if (strcmp($subphone,"") !== 0) {
			if (!is_numeric($subphone) || strlen($subphone) !== 10) {
				$form->setError($field, "* Please enter a valid 10-digit phone number");
			}
		}
		
		/* E-mail subject error check */
		$subsubject = (string) ereg_replace("[^a-z]", "", $subsubject); // Strip all non-lowercase-letter characters
		$subject_prepend = "Web Form Submission";
		if (strcmp($subsubject,"q") == 0) {
			$subsubject = $subject_prepend . ": Quick Question";
		} else if (strcmp($subsubject,"c") == 0) {
			$subsubject = $subject_prepend . ": Quick Comment";
		} else if (strcmp($subsubject,"p") == 0) {
			$subsubject = $subject_prepend . ": Report Problem with Site";
		} else {
			$subsubject = $subject_prepend;
		}
		
		/* Message error check */
		$field = "txtarea_Message"; // Use field name for message
		$submessage = (string) trim($submessage);
		if (strlen($submessage) < 1) {
			$form->setError($field, "* You must enter a message");
		}

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		} else {
			// Load array with comment data
			$comment = array(
				'author'    => $subname,
				'email'     => $subemail,
				'website'   => '',
				'body'      => $submessage,
				'permalink' => 'http://www.apousc.com/contact.php'
			);
			// Instantiate an instance of the class
			$akismet = new Akismet('http://www.apousc.com/', 'c7cd364f2709', $comment);
			// Test for errors
			if ($akismet->errorsExist()) { // Returns true if any errors exist.
				$form->setError("akismet", "Sorry, but there is an error with our spam checker. Please try to send your message again, or send an e-mail directly to one of our <a href=\"officers_a.php\">officers</a>.");
			} else {
				// No errors, check for spam.
				if ($akismet->isSpam()) { // Returns true if Akismet thinks the comment is spam.
					$form->setError("akismet", "Unfortunately, your message has been flagged by our system as spam. Please try to send your message again, or send an e-mail directly to one of our <a href=\"officers_a.php\">officers</a>.");
					return 1; // Error with form
				} else {
					if ($mailer->submitContact($subname,$subemail,$subphone,$subsubject,$submessage)) {
						return 0; //Message sent succesfully
					} else {
						return 2; //Message attempt failed
					}
				}
			}
		}
	}
	
	
	
	
		function reminder($subemail)
	{
		global $mailer, $form;

					if ($mailer->signingUp($subemail)) {
						return 0; //Message sent succesfully
					} else {
						return 2; //Message attempt failed
					};
	}

	/**
	 * isAdmin - Returns true if currently logged in user is
	 * an administrator, false otherwise.
	 */
	function isAdmin()
	{
		return ($this->userlevel == ADMIN_LEVEL || $this->username == ADMIN_NAME || $this->fname == "Administrator");
	}
	function testTRUE()
	{
		return True;
	}
	/**
	 * isOfficer - Returns true if currently logged in user is
	 * an officer, false otherwise.
	 */
	function isOfficer()
	{
		if (($this->position >= 1 && $this->position <= 33)|| $this->position == -1 || $this->isAdmin() ||
		   $this->username == "kjma" || // webmaster
		   $this->username == "jameslta" || // pledge master
		   $this->username == "ipeng" || // puncle
		   $this->username == "jordanjt" || // puncle
		   $this->username == "amarizha" || // pauntie
		   $this->username == "tkyang" || // service
		   $this->username == "ali068" || // membership
		   $this->username == "lian057" || // membership
		   $this->username == "machado" || // fellowship
		   $this->username == "shaniawa" || // finance
		   $this->username == "mgramos" || // finance
		   $this->username == "lindsecy" || // communications
		   $this->username == "chaubria" || // interchapter
		   $this->username == "tejawina" || // sec
		   $this->username == "qquan" || // creative director
		   $this->username == "mwu20216" || // almuni
		   $this->username == "ggenito" || // historian
		   $this->username == "chengea" || // phil
		   $this->username == "jwoon" || // phil
		   $this->username == "ayalam" || // rush
		   $this->username == "garywu" || // rush
		   $this->username == "mlwang" || // d&i
		   //$this->username == "ggenito" ||// omega fam head
		   $this->username == "lntran" || // phi fam head
		   $this->username == "jennaogi" // alpha fam head
		   )
			return true;
		return false;
	}
	/**
	 * isWebmaster - Returns true if currently logged in user is
	 * a webmaster, false otherwise.
	 * -- Nick Chen, Spring 2020
	 */
	function isWebmaster()
	{
		return ($this->position == 16 || $this->isAdmin());
	}
	function isFakeOfficer() {
		return $this->position == 99;
	}
	
	function inCharge()
	{
		return (($this->position == 2 && $req_event_info['type']==7) ||
							    ($this->position == 3 && $req_event_info['type']==0) ||
							    ($this->position == 4 && $req_event_info['type']==1) ||
							    ($this->position == 5 && $req_event_info['type']==4) ||
							    ($this->position == 8 && $req_event_info['type']==2) ||
							    ($this->position == 9 && $req_event_info['type']==3) ||
							    ($this->position == 10 && $req_event_info['type']==9) ||
							    ($this->position == 11 && $req_event_info['type']==6) ||
								($this->position == 15) ||
							    ($this->position == 17 && $req_event_info['type']==0) ||
							    ($this->position == 18) ||
							    ($this->position == 20 && $req_event_info['type']==7) ||
							    ($this->position == 21 && $req_event_info['type']==10) ||
							    ($this->position == 22 && $req_event_info['type']==11) ||
							    ($this->position == 23 && $req_event_info['type']==12) ||
							    (($this->position >= 24 || $this->position <= 28) && $req_event_info['type']==7));
	}

	/**
	 * generateRandID - Generates a string made up of randomized
	 * letters (lower and upper case) and digits and returns
	 * the md5 hash of it to be used as a userid.
	 */
	function generateRandID()
	{
		return md5($this->generateRandStr(16));
	}

	/**
	 * generateRandStr - Generates a string made up of randomized
	 * letters (lower and upper case) and digits, the length
	 * is a specified parameter.
	 */
	function generateRandStr($length)
	{
		$randstr = "";
		for ($i = 0; $i < $length; $i++) {
			$randnum = mt_rand(0, 61);
			if ($randnum < 10) {
				$randstr .= chr($randnum + 48);
			} else if ($randnum < 36) {
				$randstr .= chr($randnum + 55);
			} else {
				$randstr .= chr($randnum + 61);
			}
		}
		return $randstr;
	}

	/**
	 * addNomination
	 */
	function addNomination($subusername, $subposition)
	{
		global $database, $form;

		/* Check username */
		$subusername = (string) ereg_replace("[^A-Za-z0-9]", "", $subusername); // Strip all non-alphanumeric characters and force to string
		if ($database->usernameTaken($subusername) !== TRUE) {
			return 1; // Errors with form
		}
		/* Check position */
		$subposition = (string) preg_replace("/\D/", "", $subposition); // Strip all non-numeric characters
		
		/* Check eligibility */
		if ($subposition == 0 || $subposition == 1 || $subposition == 2 || $subposition == 3) { // if position is President, Pledge Master, VP Service, or VP Membership
			$status_check = $database->getUserInfo($subusername);
			if ($status_check['status'] == PLEDGE_MEMBER) {
				$form->setError("sel_Member", "* Member must have completed one Active semester in good standing previous to running for this position.");
			}
		}
		if ($database->checkNomination($subusername, $subposition)) {
			$form->setError("sel_Position", "* Member has already been nominated for this position.");
		}
		
		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new announcement to the database */
		else {
			if ($database->addNewNomination($subusername,$subposition)) {
				return 0; //Event signup added succesfully
			} else {
				return 2; //Event signup attempt failed
			}
		}
	}
	/**
	 * secondNomination
	 */
	function secondNomination($subusername, $subposition)
	{
		global $database;
		/* Check username */
		$subusername = (string) ereg_replace("[^A-Za-z0-9]", "", $subusername); // Strip all non-alphanumeric characters and force to string
		if ($database->usernameTaken($subusername) !== TRUE) {
			return 1; // Errors with form
		}
		/* Check position */
		$subposition = (string) preg_replace("/\D/", "", $subposition); // Strip all non-numeric characters
		
		$database->editSecondField($subusername, $subposition);
	}
	/**
	 * declineNomination
	 */
	function declineNomination($subusername, $subposition)
	{
		global $database;
		/* Check username */
		$subusername = (string) ereg_replace("[^A-Za-z0-9]", "", $subusername); // Strip all non-alphanumeric characters and force to string
		if ($database->usernameTaken($subusername) !== TRUE) {
			return 1; // Errors with form
		}
		/* Check position */
		$subposition = (string) preg_replace("/\D/", "", $subposition); // Strip all non-numeric characters
		
		$database->editDeclineField($subusername, $subposition);
	}
	
	/**
	 * addPOTW
	 */
	function addPOTW($subweek, $subtitle, $subcaption, $subsubmittedby)
	{
		global $database, $form;

		/* Check Week */
		$submonth = date(m,strtotime($subweek));
		$subday   = date(d,strtotime($subweek));
		$subyear  = date(Y,strtotime($subweek));
		if (!checkdate($submonth,$subday,$subyear)) {
			$form->setError("txt_Week", "* Please enter a valid date. Click on the calendar icon to use the date picker.");
		} else {
			$subweek = date("Y-m-d", strtotime($subweek)); // If submitted week is a valid date, convert to mySQL-formatted date to store in database
		}
		
		/* Check Title */
		$subtitle = trim($subtitle); // Trims whitespace from the beginning and end of string
		if (strlen($subtitle) > 60) { // Database is only set to hold 60 characters
			$form->setError("txt_Title", "* Your title is too long! (<60 characters, please)");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subtitle = stripslashes($subtitle); // Removes magic_quotes_gpc slashes
			}
			$subtitle = mysql_real_escape_string($subtitle); // Prepends backslashes to special MySQL characters
			$subtitle = (string) $subtitle; // Force $subtitle to be a string
		}

		/* Check Caption */
		$subcaption = trim($subcaption); // Trims whitespace from beginning and end of string
		if (strlen($subcaption) > 21845) { // Database can only hold ~64KB of data
			$form->setError("txt_Caption", "* Your caption is too long!");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subcaption = stripslashes($subcaption); // Removes magic_quotes_gpc slashes
			}
			$subcaption = mysql_real_escape_string($subcaption); // Prepends backslashes to special MySQL characters
			$subcaption = (string) $subcaption; // Force $subtitle to be a string
		}

		/* Check Submitted By */
		$subsubmittedby = trim($subsubmittedby); // Trims whitespace from the beginning and end of string
		if (strlen($subsubmittedby) > 60) { // Database is only set to hold 60 characters
			$form->setError("txt_SubmittedBy", "* Your submitter name is too long! (<60 characters, please)");
		} else {
			if (@get_magic_quotes_gpc()) {
				$subsubmittedby = stripslashes($subsubmittedby); // Removes magic_quotes_gpc slashes
			}
			$subsubmittedby = mysql_real_escape_string($subsubmittedby); // Prepends backslashes to special MySQL characters
			$subsubmittedby = (string) $subsubmittedby; // Force $subsubmittedby to be a string
		}

		/* Check File Upload */
		// possible PHP upload errors
		$uploadErrors = array(
						1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
						2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
						3 => 'The uploaded file was only partially uploaded.',
						4 => 'No file was uploaded.',
						6 => 'Missing a temporary folder.',
						7 => 'Failed to write file to disk.',
						8 => 'A PHP extension stopped the file upload.');
		
		// check the upload form was actually submitted else print the form
		if (!isset($_POST['submit_POTW'])) {
			$form->setError("error_Type", "* You must use the provided upload form.");
		}
		// check for PHP's built-in uploading errors
		if ($_FILES['file_POTW']['error'] != 0) {
			$form->setError("error_Type", "* ".$uploadErrors[$_FILES['file_POTW']['error']]);
		}
		// check that the file we are working on really was the subject of an HTTP upload
		$isHTTPUpload = @is_uploaded_file($_FILES['file_POTW']['tmp_name']);
		if (!$isHTTPUpload) {
			$form->setError("error_Type", "* Not an HTTP upload.");
		}
		// validation... since this is an image upload script we should run a check  
		// to make sure the uploaded file is in fact an image. Here is a simple check:
		// getimagesize() returns false if the file tested is not an image.
		$isImage = @getimagesize($_FILES['file_POTW']['tmp_name']);
		if (!$isImage) {
			$form->setError("error_Type", "Only image uploads are allowed");
		}
		// make a unique filename for the uploaded file and check it is not already
		// taken... if it is already taken keep trying until we find a vacant one
		// sample filename: 1140732936-filename.jpg
		$now = time();
		$uploadsDirectory = $_SERVER['DOCUMENT_ROOT'] . '/img/potw/'; // make a note of the directory that will receive the upload
		while (file_exists($uploadFilename = $uploadsDirectory.$now.'-'.basename($_FILES[$fieldname]['name']))) {
			$now++;
		}
		// now let's move the file to its final location and allocate the new filename to it
		$moveUpload = @move_uploaded_file($_FILES['file_POTW']['tmp_name'], $uploadFilename);
		if (!$moveUpload) {
			$form->setError("error_Type", "* Either you forgot to select a photo to upload, or the receiving directory has insufficient permissions");
		}
		// Add slashes to uploaded photo's filepath, if necessary
		if (@get_magic_quotes_gpc()) {
			$subfile = stripslashes($uploadFilename); // Removes magic_quotes_gpc slashes
		}
		$subfile = mysql_real_escape_string($subfile); // Prepends backslashes to special MySQL characters
		$subfile = (string) $subfile; // Force $subfile to be a string
		if (strlen($subfile) > 1024) {
			$form->setError("file_POTW", "* File name is too long. Please rename your photo before uploading."); // Database is set to store up to 1024 characters for filepath
		}

		/* Errors exist, have user correct them */
		if ($form->num_errors > 0) {
			return 1; // Errors with form
		}
		/* No errors, add the new POTW to the database */
		else {
			if ($database->addNewPOTW($subweek, $subtitle, $subcaption, $subsubmittedby, $subfile)) {
				return 0; //Event signup added succesfully
			} else {
				return 2; //Event signup attempt failed
			}
		}
	}
}


/**
 * Initialize session object - This must be initialized before
 * the form object because the form uses session variables,
 * which cannot be accessed unless the session has started.
 */
$session = new Session;

/* Initialize form object */
$form = new Form;
?>
