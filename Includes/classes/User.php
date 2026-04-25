<?php
	class User 
	{
        private $con;
        private $username;

		public	function __construct($con, $username) {
			$this->con = $con;
			$this->username = $username;
        }
        
        public function getUsername() {
            return $this->username;
        }

        public function getName() {
            // SECURITY: Using prepared statements
            $stmt = $this->con->prepare("SELECT concat(firstName, ' ', lastName) AS name FROM users WHERE username = ?");
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_array(MYSQLI_ASSOC);
            return $row['name'];
        }

        public function getEmail() {
            // SECURITY: Using prepared statements
            $stmt = $this->con->prepare("SELECT email FROM users WHERE username = ?");
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_array(MYSQLI_ASSOC);
            return $row['email'];
        }

        public function isAdmin() {
            // Admin column doesn't exist in users table
            // Returning false to disable admin functionality for all users
            return false;
        }

	}

?>