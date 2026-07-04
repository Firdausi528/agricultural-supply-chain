<?php
require_once __DIR__ . '/User.php';

class Buyer extends User
{
    private $business_name;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->user_type = 'buyer';
    }

    public function getBusinessName()
    {
        return $this->business_name;
    }

    public function setBusinessName($business_name)
    {
        $this->business_name = $business_name;
    }

    public function getOrderCount()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders WHERE buyer_id = ?";
        $stmt = $this->db->query($sql, array($this->id));
        return $stmt->fetchColumn();
    }

    public function getPendingOrders()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND order_status = 'pending'";
        $stmt = $this->db->query($sql, array($this->id));
        return $stmt->fetchColumn();
    }

    public function getDeliveredOrders()
    {
        if (empty($this->id)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND order_status = 'delivered'";
        $stmt = $this->db->query($sql, array($this->id));
        return $stmt->fetchColumn();
    }

    public function load($id)
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->query($sql, array($id));
        $user = $stmt->fetch();

        if ($user) {
            $this->id = $user['id'];
            $this->user_type = $user['user_type'];
            $this->full_name = $user['full_name'];
            $this->email = $user['email'];
            $this->phone = $user['phone'];
            $this->location = $user['location'];
            $this->profile_photo = $user['profile_photo'];
            
            if (!empty($user['business_name'])) {
                $this->business_name = $user['business_name'];
            }
            return true;
        }
        return false;
    }

    public function getDashboard()
    {
        return array(
            'orders' => $this->getOrderCount(),
            'pending' => $this->getPendingOrders(),
            'delivered' => $this->getDeliveredOrders(),
            'business_name' => $this->business_name
        );
    }
}
?>