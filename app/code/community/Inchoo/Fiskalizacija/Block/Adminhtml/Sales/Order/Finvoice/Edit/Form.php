<?php

class Inchoo_Fiskalizacija_Block_Adminhtml_Sales_Order_Finvoice_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Init form
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('block_form');
        $this->setTitle(Mage::helper('cms')->__('Informacije'));
    }

    /**
     * Load Wysiwyg on demand and Prepare layout
     */
//    protected function _prepareLayout()
//    {
//        parent::_prepareLayout();
//        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
//            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
//        }
//    }

    protected function _prepareForm()
    {
        $model = Mage::registry('current_finvoice');

        $form = new Varien_Data_Form(
            array('id' => 'edit_form', 'action' => $this->getUrl('adminhtml/inchoo_fiskalizacija/postResignAndSubmit'), 'method' => 'post')
        );

        $form->setHtmlIdPrefix('block_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('cms')->__('RacunZahtjev'), 'class' => 'fieldset-wide'));

        if ($model && $model->getId()) {
            $fieldset->addField('entity_id', 'hidden', array(
                'name' => 'entity_id',
            ));
        }

        $model->setData('finvoice_id', $model->getId());
        $fieldset->addField('finvoice_id', 'hidden', array(
            'name' => 'finvoice_id',
        ));        
        
        $model->setData('parent_entity_id', $this->getRequest()->getParam('parent_entity_id', 0));
        $fieldset->addField('parent_entity_id', 'hidden', array(
            'name' => 'parent_entity_id',
        ));

        $model->setData('parent_entity_type', $this->getRequest()->getParam('parent_entity_type', 0));
        $fieldset->addField('parent_entity_type', 'hidden', array(
            'name' => 'parent_entity_type',
        ));
        
        $model->setData('order_id', $this->getRequest()->getParam('order_id', 0));
        $fieldset->addField('order_id', 'hidden', array(
            'name' => 'order_id',
        ));
        
        $fieldset->addField('xml_request_raw_body', 'textarea', array(
            'name'      => 'xml_request_raw_body',
            'label'     => Mage::helper('cms')->__('XML zahtjev'),
            'title'     => Mage::helper('cms')->__('XML zahtjev'),
            'style'     => 'height:36em',
            'required'  => true,
        ));

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
