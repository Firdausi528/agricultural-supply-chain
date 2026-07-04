<?php
abstract class User {
    protected $id;
    protected $full_name;
    protected $email;
    protected $phone;
    protected $user_type;
    protected $location;
    protected $profile_photo;
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getId() { return $this->id; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getPhone() { return $this->phone; }
    public function getUserType() { return $this->user_type; }
    public function getLocation() { return $this->location; }
    public function getProfilePhoto() { return $this->profile_photo; }

    public function setFullName($name) { $this->full_name = $name; }
    public function setEmail($email) { $this->email = $email; }
    public function setPhone($phone) { $this->phone = $phone; }
    public function setLocation($location) { $this->location = $location; }
    public function setProfilePhoto($photo) { $this->profile_photo = $photo; }

    abstract public function getDashboard();

    public function load($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        $user = $stmt->fetch();

        if ($user) {
            $this->id = $user['id'];
            $this->full_name = $user['full_name'];
            $this->email = $user['email'];
            $this->phone = $user['phone'];
            $this->user_type = $user['user_type'];
            $this->location = $user['location'];
            $this->profile_photo = $user['profile_photo'];
            return true;
        }
        return false;
    }

    public function save() {
        if ($this->id) {
            $sql = "UPDATE users SET 
                    full_name = ?, email = ?, phone = ?, 
                    location = ?, profile_photo = ? 
                    WHERE id = ?";
            $this->db->query($sql, [
                $this->full_name,
                $this->email,
                $this->phone,
                $this->location,
                $this->profile_photo,
                $this->id
            ]);
            return true;
        }
        return false;
    }

    public function delete() {
        if ($this->id && $this->user_type != 'admin') {
            $sql = "DELETE FROM users WHERE id = ?";
            $this->db->query($sql, [$this->id]);
            return true;
        }
        return false;
    }

    public static function login($db, $username, $password) {
        $sql = "SELECT * FROM users WHERE email = ? OR full_name = ?";
        $stmt = $db->query($sql, [$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
?>