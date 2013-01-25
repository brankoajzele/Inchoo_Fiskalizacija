<?php

class Inchoo_Fiskalizacija_Block_Adminhtml_Sales_Order_Finvoice_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'fiscal_RacunZahtjev';
        $this->_blockGroup = 'inchoo_fiskalizacija';
        $this->_controller = 'adminhtml_sales_order_finvoice';

        parent::__construct();
        

        $this->_updateButton('save', 'label', Mage::helper('cms')->__('Sign & Re-Send'));
        $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/sales_order_invoice/view', array('invoice_id' => $this->getRequest()->getParam('invoice_id', 0), 'order_id' => $this->getRequest()->getParam('order_id', 0))) . '\');');
        
        $this->_removeButton('reset');
    }

    public function getHeaderText()
    {
        if (Mage::registry('current_finvoice') && Mage::registry('current_finvoice')->getId()) {
            $finvoice = Mage::registry('current_finvoice');
            
            $invoice = Mage::getModel('sales/order_invoice')
                            ->load($finvoice->getInvoiceEntityId());
            
            return Mage::helper('cms')->__("Edit RacunZahtjev for Invoice #%s, Fiscal Invoice #%s", $invoice->getIncrementId(), $finvoice->getId());
        }
        else {
            return Mage::helper('cms')->__('Error...');
        }
    }

}
