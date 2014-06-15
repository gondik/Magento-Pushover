<?php
class Woodlo_PushoverNotification_Model_Observer
{
	private $pushoverApiUrl = 'https://api.pushover.net/1/messages.json';
	private $isEnabled = 0;
	private $notificationDevice = null;
	
	private $store;


	public function __construct() 
	{
		$this->store = Mage::app()->getStore();

		$this->isEnabled = Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_general/isenabled', $this->store);
		$this->notificationDevice = Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_general/notification_device', $this->store);
	}

	private function notificate($message, $title=null, $priority=0)
    {
        $post = array();
        $post['token'] = Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_general/application_key', $this->store);
        $post['user'] = Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_general/user_key', $this->store);
        $post['message'] = $message;
        $post['priority'] = $priority;

        if ($priority == 2)
        {
        	// need to supply two additional parameters: retry and expire
        	$post['retry'] = 60;
        	$post['expire'] = 600;
        }

        if ($this->notificationDevice) 
        {
        	$post['device'] = $this->notificationDevice;
        }
        if ($title) 
        {
        	$post['title'] = $title;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->pushoverApiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ret = curl_exec($ch);
            curl_close($ch);
        }
        else {
            $ret = $this->postRequest($this->pushoverApiUrl, http_build_query($post));
        }

        if (!$ret)
        {
        	return;
        }

        $result = json_decode($ret);

        if ($result->status == 1)
        {
        	// TODO: create log of all sent requests
        }
        else
        {
        	Mage::log($result);
        }
    }

    private function postRequest($url, $data)
	{
		$params = array('http' => array(
		          'method' => 'POST',
		          'content' => $data
		        ));
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (!$fp) {
			throw new Exception("Problem with $url, $php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if ($response === false) {
			throw new Exception("Problem reading data from $url, $php_errormsg");
		}
		return $response;
	}

	private function genericNotificaton($eventName, $variableMap)
	{
		if (!$this->isEnabled or !Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_notifications/'.$eventName.'_enabled', $this->store))
		{
			return;
		}

		$notificationMessageTemplate = Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_notifications/'.$eventName.'_message', $this->store);
        $notificationTitle = Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_notifications/'.$eventName.'_title', $this->store);
        $notificationPriority = Mage::getStoreConfig('woodlo_pushovernotification_settings/pushovernotification_notifications/'.$eventName.'_priority', $this->store);

        $notificationMessage = strtr($notificationMessageTemplate, $variableMap);

        $this->notificate($notificationMessage, $notificationTitle, $notificationPriority);
	}

    // Event handlers

	public function salesOrderPlaceAfter(Varien_Event_Observer $observer)
	{
		$_helper = Mage::helper('core');
        $order = $observer->getEvent()->getOrder();

        $variableMap = array(
            '{customer_name}' => $order->getCustomerName(),
            '{base_subtotal}' => $_helper->currency($order->getBaseSubtotal(), true, false),
            '{base_ground_total}' => $_helper->currency($order->getBaseGrandTotal(), true, false),
            '{grand_total}' => $_helper->currency($order->getGrandTotal(), true, false),
            '{shipping_method}' => $order->getShippingMethod(),
            '{shipping_amount}' => $_helper->currency($order->getShippingAmount(), true, false),
            '{order_id}' => $order->getRealOrderId(),
            '{order_state}' => $order->getState(),
            '{total_qty_ordered}' => $order->getTotalQtyOrdered(),
            '{order_currency_code}' => $order->getOrderCurrencyCode(),
            '{store_name}' => $this->store->getName()
        );

        $this->genericNotificaton('new_order_notification', $variableMap);
	}

	public function customerRegisterSuccess(Varien_Event_Observer $observer)
	{
		$c = $observer->getEvent()->getCustomer();		
		$customer = Mage::getModel('customer/customer')->load($c->getId());
		
		$primaryAddresses = $customer->getPrimaryAddresses();
		$address = array_shift($primaryAddresses);

		$countryName = Mage::getModel('directory/country')
               ->loadByCode($address->getCountryId())
               ->getName();

		$variableMap = array(
			'{prefix}' => $address->getPrefix(),
            '{name}' => $customer->getName(),
            '{city}' => $address->getCity(),
            '{region}' => $address->getRegion(),
            '{country}' => $countryName,
            '{company}' => $address->getCompany(),
            '{telephone}' => $address->getTelephone(),
            '{store_name}' => $this->store->getName()
        );

        $this->genericNotificaton('new_customer_notification', $variableMap);
	}
		
}
