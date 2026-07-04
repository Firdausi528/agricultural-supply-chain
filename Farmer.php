<?php
require_once __DIR__ . '/User.php';

class Farmer extends User
{
    private $farm_name;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->user_type = 'farmer';
    }

    public function getFarmName()
    {
        return $this->farm_name;
    }

    public function setFarmName($farm_name)
    {
        $this->farm_name = $farm_name;
    }

    public function getCropCount()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM crops WHERE farmer_id = ?";
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchColumn();
    }

    public function getOrderCount()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders o JOIN crops c ON o.crop_id = c.id WHERE c.farmer_id = ?";
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchColumn();
    }

    public function getPendingOrders()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders o JOIN crops c ON o.crop_id = c.id WHERE c.farmer_id = ? AND o.order_status = 'pending'";
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchColumn();
    }

    public function getDeliveredOrders()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders o JOIN crops c ON o.crop_id = c.id WHERE c.farmer_id = ? AND o.order_status = 'delivered'";
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
            
            if (!empty($user['farm_name'])) {
                $this->farm_name = $user['farm_name'];
            }
            return true;
        }
        return false;
    }

    public function getDashboard()
    {
        return array(
            'crops' => $this->getCropCount(),
            'orders' => $this->getOrderCount(),
            'pending' => $this->getPendingOrders(),
            'delivered' => $this->getDeliveredOrders(),
            'farm_name' => $this->farm_name
        );
    }
}
?>