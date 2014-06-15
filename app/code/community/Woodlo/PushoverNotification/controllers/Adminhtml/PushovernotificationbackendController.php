<?php
class Woodlo_PushoverNotification_Adminhtml_PushovernotificationbackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Pushover Notification"));
	   $this->renderLayout();
    }
}