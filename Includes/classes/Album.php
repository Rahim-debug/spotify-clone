<?php 
	class Album 
	{
		private $con;
        private $id;
        private $title;
        private $artistId;
        private $genre;
        private $artworkPath;

		public	function __construct($con, $id) {
			$this->con = $con;
        $this->id = $id;
        
        // SECURITY & PERFORMANCE: Using prepared statements
        $stmt = $this->con->prepare("SELECT * FROM albums WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $album = $result->fetch_array(MYSQLI_ASSOC);

        $this->title = $album['title'];
        $this->artistId = $album['artist'];
        $this->genre = $album['genre'];
        $this->artworkPath = $album['artworkPath'];
    }
        
        public function getTitle() {
            return $this->title;
        }

        public function getArtist() {
            return new Artist($this->con, $this->artistId);
        }

        public function getGenre() {
            return $this->genre;
        }

        public function getArtworkPath() {
            // Convert relative paths to absolute paths from webroot
            if (!empty($this->artworkPath)) {
                return getAssetPath($this->artworkPath);
            }
            return $this->artworkPath;
        }

        public function getNumberOfSongs() {
            // SECURITY & PERFORMANCE: Using COUNT() instead of SELECT * with row count
            $stmt = $this->con->prepare("SELECT COUNT(*) as count FROM songs WHERE album = ?");
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_array(MYSQLI_ASSOC);
            return $row['count'];
        }

        public function getSongIds() {
            // SECURITY: Using prepared statements
            $stmt = $this->con->prepare("SELECT id FROM songs WHERE album = ? ORDER BY albumOrder ASC");
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