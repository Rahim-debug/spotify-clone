<?php 
	class Constants
	{
		// REGISTER ERRORS
		public static $passwordsDoNoMatch = "Your passwords don't match";
		public static $passwordNotAlphanumeric = "Your password can only contain numbers, letters and these special characters[_$!@%&*]";
		public static $passwordCharacters = "Your password must be between 4 and 30 characters";
		public static $emailInvalid = "Email is invalid";
		public static $emailsDoNotMatch = "Your emails don't match";
		public static $emailTaken = "This email is already in use";
		public static $lastNameCharacters = "Your last name must be between 2 and 25 characters";
		public static $firstNameCharacters = "Your first name must be between 2 and 25 characters";
		public static $usernameCharacters = "Your username must be between 5 and 25 characters";
		public static $usernameTaken = "This username already exists";

		// LOGIN ERRORS
		public static $loginFailed = "Your username and/or password was incorrect";

		// JAMENDO API CONFIGURATION
		// Before using the Jamendo API integration, you must:
		// 1. Get a free API key at https://www.jamendo.com/api/v3.0/
		// 2. Set JAMENDO_CLIENT_ID below or in your .env file
		public static $jamendoClientId = "YOUR_JAMENDO_CLIENT_ID_HERE";
	}
?>