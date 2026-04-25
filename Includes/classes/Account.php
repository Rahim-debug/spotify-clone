<?php 
	class Account 
	{
		private $con;
		private $errorArray;

		public function __construct($con) {
			$this->con = $con;
			$this->errorArray = array();
		}

		public function login($username, $password) {
			$password = md5($password);

			// SECURITY: Using prepared statements
			$stmt = $this->con->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
			$stmt->bind_param("ss", $username, $password);
			$stmt->execute();
			$result = $stmt->get_result();

			if ($result->num_rows == 1) {
				return true;
			}
			else {
				array_push($this->errorArray, Constants::$loginFailed);
				return false;
			}
		}

		public function register($username, $firstName, $lastName, $email, $email2, $password, $password2) {
    		$this->validateUsername($username);
			$this->validateFistName($firstName);
			$this->validateLastName($lastName);
			$this->validateEmails($email, $email2);
			$this->validatePasswords($password, $password2);
			if(empty($this->errorArray))
			{
				//insert into database 
				return $this->insertUserDetails($username, $firstName, $lastName, $email, $password);
			}
			else
			{
				return false;
			}
		}

		public function getError($error) {
			if(!in_array($error, $this->errorArray)) {
				$error = "";
			}
			return "<span class='errorMessage'> $error </span>";
		}

		private function insertUserDetails($username, $firstName, $lastName, $email, $password) {
			$encryptedPassword = md5($password);
			$profilePic = "assets/images/profile-pics/user.jpg";
			$date = date("Y-m-d H:i:s");

			// SECURITY: Using prepared statements
			$stmt = $this->con->prepare("INSERT INTO users (username, firstName, lastName, email, password, signUpDate, profilePic) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("sssssss", $username, $firstName, $lastName, $email, $encryptedPassword, $date, $profilePic);
			$result = $stmt->execute();
			return $result;
		}

		private function validateUsername($un) {
			if (strlen($un) > 25 || strlen($un) < 5) {
				array_push($this->errorArray, Constants::$usernameCharacters);
				return;
			}
			
			// SECURITY: Using prepared statements
			$stmt = $this->con->prepare("SELECT username FROM users WHERE username = ?");
			$stmt->bind_param("s", $un);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows != 0) {
				array_push($this->errorArray, Constants::$usernameTaken);
				return;
			}
		}

		private function validateFistName($fn) {
			if (strlen($fn) > 25 || strlen($fn) < 2) {
				array_push($this->errorArray, Constants::$firstNameCharacters);
				return;
			}
		}

		private function validateLastName($ln) {
			if (strlen($ln) > 25 || strlen($ln) < 2) {
				array_push($this->errorArray, Constants::$lastNameCharacters);
				return;
			}
		}

		private function validateEmails($em, $em2) {
			if($em != $em2) {
				array_push($this->errorArray, Constants::$emailsDoNotMatch);
				return; 
			}
			if(!filter_var($em, FILTER_VALIDATE_EMAIL)) {
				array_push($this->errorArray, Constants::$emailInvalid);
				return;
			}
			
			// SECURITY: Using prepared statements
			$stmt = $this->con->prepare("SELECT email FROM users WHERE email = ?");
			$stmt->bind_param("s", $em);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows != 0) {
				array_push($this->errorArray, Constants::$emailTaken);
				return;
			}
		}

		private function validatePasswords($pw, $pw2) {	
			if($pw != $pw2) {
				array_push($this->errorArray, Constants::$passwordsDoNoMatch);
				return; 
			}
			if(preg_match("/[^a-zA-Z0-9_$!@%&*]/", $pw)) {
				array_push($this->errorArray, Constants::$passwordNotAlphanumeric);
				return;	
			}
			if(strlen($pw) > 30 || strlen($pw) < 4) {
				array_push($this->errorArray, Constants::$passwordCharacters);
				return;
			}		
		}
	}
?>