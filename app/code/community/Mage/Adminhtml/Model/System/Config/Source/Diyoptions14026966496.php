<?php
class Mage_Adminhtml_Model_System_Config_Source_Diyoptions14026966496
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
		
            array('value' => -2, 'label'=>Mage::helper('adminhtml')->__('Lowest Priority')),
            array('value' => -1, 'label'=>Mage::helper('adminhtml')->__('Low Priority')),
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Normal Priority')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('High Priority')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Emergency Priority')),
        );
    }

}
