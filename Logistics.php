<?php
require_once __DIR__ . '/User.php';

class Logistics extends User
{
    public function __construct($db)
    {
        parent::__construct($db);
        $this->user_type = 'logistics';
    }

    public function getDeliveryCount()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders WHERE logistics_id = ?";
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchColumn();
    }

    public function getInTransitCount()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders WHERE logistics_id = ? AND order_status = 'in_transit'";
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchColumn();
    }

    public function getDeliveredCount()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders WHERE logistics_id = ? AND order_status = 'delivered'";
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchColumn();
    }

    public function load($id)
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        $user = $stmt->fetch();

        if ($user) {
            $this->id = $user['id'];
            $this->user_type = $user['user_type'];
            $this->full_name = $user['full_name'];
            $this->email = $user['email'];
            $this->phone = $user['phone'];
            $this->location = $user['location'];
            $this->profile_photo = $user['profile_photo'];
            return true;
        }
        return false;
    }

    public function getDashboard()
    {
        return array(
            'total' => $this->getDeliveryCount(),
            'in_transit' => $this->getInTransitCount(),
            'delivered' => $this->getDeliveredCount()
        );
    }
}
?>