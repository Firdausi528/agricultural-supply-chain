<?php
require_once __DIR__ . '/User.php';

class Admin extends User
{
    public function __construct($db)
    {
        parent::__construct($db);
        $this->user_type = 'admin';
    }

    public function getTotalUsers()
    {
        $sql = "SELECT COUNT(*) FROM users";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getTotalFarmers()
    {
        $sql = "SELECT COUNT(*) FROM users WHERE user_type = 'farmer'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getTotalBuyers()
    {
        $sql = "SELECT COUNT(*) FROM users WHERE user_type = 'buyer'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getTotalLogistics()
    {
        $sql = "SELECT COUNT(*) FROM users WHERE user_type = 'logistics'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getTotalCrops()
    {
        $sql = "SELECT COUNT(*) FROM crops";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getTotalOrders()
    {
        $sql = "SELECT COUNT(*) FROM orders";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getTotalRevenue()
    {
        $sql = "SELECT SUM(total_price) FROM orders WHERE order_status = 'delivered'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn() ?: 0;
    }

    public function getPendingOrders()
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE order_status = 'pending'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getDeliveredOrders()
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE order_status = 'delivered'";
        $stmt = $this->db->query($sql);
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
            'total_users' => $this->getTotalUsers(),
            'farmers' => $this->getTotalFarmers(),
            'buyers' => $this->getTotalBuyers(),
            'logistics' => $this->getTotalLogistics(),
            'crops' => $this->getTotalCrops(),
            'orders' => $this->getTotalOrders(),
            'revenue' => $this->getTotalRevenue(),
            'pending' => $this->getPendingOrders(),
            'delivered' => $this->getDeliveredOrders()
        );
    }
}
?>