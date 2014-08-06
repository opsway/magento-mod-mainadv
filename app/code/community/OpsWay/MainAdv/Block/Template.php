<?php

class OpsWay_MainAdv_Block_Template extends Mage_Core_Block_Template
{

    private $pagetype = null;

    public function getToken() 
    {
        return Mage::getStoreConfig("dev/mainadv/token");
    } 

    public function getPageType()
    {
        if (!$this->pagetype) {
            $handles = $this->getLayout()->getUpdate()->getHandles();
            if (in_array("cms_page", $handles) 
                && stristr(Mage::getSingleton('cms/page')->getIdentifier(),"home")) {
                $this->pagetype = "home";
            } elseif (in_array("catalog_product_view", $handles)) {
                $this->pagetype = "product";
            } elseif (in_array("catalog_category_view", $handles)) {
                $this->pagetype = "category";
            } elseif (in_array("checkout_cart_index", $handles)) {
                $this->pagetype = "basket";
            } elseif (in_array("onepagecheckout_index_success", $handles)) {
                $this->pagetype = "checkout";
            } 
        }
        return $this->pagetype;
    }

    public function getPageTypeSpecifics() {
        $additionalInfo = array();
        switch ($this->getPageType()) {
            case 'category':
                $additionalInfo = $this->getSpecifics_Category();
                break;
            case 'product':
                $additionalInfo = $this->getSpecifics_Product();
                break;
            case 'basket':
                $additionalInfo = $this->getSpecifics_Basket();
                break;
            case 'checkout':
                $additionalInfo = $this->getSpecifics_Checkout();
                break;
            default:
                break;
        }
        foreach ($additionalInfo as $key => $param) {
            echo "'$key': ". json_encode($param) .",\n";
        }
    }

    private function getSpecifics_Category() {
        $currentCategory = Mage::registry('current_category');
        $collection = $currentCategory->getProductCollection()->addAttributeToSelect('sku');
        $result = array();
        foreach ($collection as $product) {
            $sku = Mage::getModel('catalog/product')->load($product->getId())->getSku();
            $result[] = $product->getSku();
        }
        return array('pdt_category_list' => implode("|",$result));
    } 

    private function getSpecifics_Product() {
        $currentProduct = Mage::registry('current_product');
        $info = array();
        $info['pdt_id'] = $currentProduct->getId();
        $info['pdt_sku'] = $currentProduct->getSku();
        $info['pdt_name'] = $currentProduct->getName();
        $info['pdt_price'] = $currentProduct->getPrice();
        $info['pdt_smalldescription'] = $currentProduct->getShortDescription();
        $info['pdt_photo'] = Mage::helper('catalog/image')->init($currentProduct, 'thumbnail');;
        return $info;
    }

    private function getSpecifics_Basket() {
        $info = array();
        $info['ty_orderamt'] = $this->helper('checkout/cart')->getQuote()->getGrandTotal();;        
        return $info;
    }

    private function getSpecifics_Checkout() {
        $info = array();
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        if (!$orderId) {
            Mage::log("No order id found - can not create tags for confirmation page");
            return array();
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $orderData = $order->getData();
       
        $customer = Mage::getModel("customer/customer"); 
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId()); 
        $customer->loadByEmail($orderData['customer_email']); 

        $info['ty_orderamt'] = $orderData['grand_total'];
        $info['ty_orderstatus'] = $orderData['status'];
        $info['ty_orderdate'] = date("m.d.y");
        $info['ty_cusname'] = $orderData['customer_firstname'] . " " . $orderData['customer_lastname'] ;
        $info['ty_cusid'] = $customer->getId();
        return $info;
    }
}