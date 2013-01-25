<?php

class Inchoo_Fiskalizacija_Block_Adminhtml_Edit extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'inchoo_fiskalizacija';
        $this->_controller = 'adminhtml_edit';
        $this->_headerText = Mage::helper('adminhtml')->__('Fiscal accounts, year %s', date('Y'));

        parent::__construct();

        $this->_removeButton('add');
    }
}
