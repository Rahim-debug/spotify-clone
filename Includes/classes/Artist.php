<?php 
	class Artist 
	{
		private $con;
        private $id;

		public	function __construct($con, $id) {
			$this->con = $con;
			$this->id = $id;
        }

        public function getId() {
            return $this->id;
        }
        
        public function getName() {
            // SECURITY: Using prepared statements instead of direct query
            $stmt = $this->con->prepare("SELECT name FROM artists WHERE id = ?");
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
            $result = $stmt->get_result();
            $artist = $result->fetch_array(MYSQLI_ASSOC);
            return $artist['name'];
        }

        public function getSongIds() {
            // SECURITY: Using prepared statements
            $stmt = $this->con->prepare("SELECT id FROM songs WHERE artist = ? ORDER BY plays ASC");
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
            $result = $stmt->get_result();
            $array = array();
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                array_push($array, $row['id']);
            }
            return $array;
        }
	}

?>