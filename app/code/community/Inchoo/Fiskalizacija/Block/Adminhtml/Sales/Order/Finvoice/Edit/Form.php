<?php
/**
 * Inchoo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Please do not edit or add to this file if you wish to upgrade
 * Magento or this extension to newer versions in the future.
 * Inchoo developers (Inchooer's) give their best to conform to
 * "non-obtrusive, best Magento practices" style of coding.
 * However, Inchoo does not guarantee functional accuracy of
 * specific extension behavior. Additionally we take no responsibility
 * for any possible issue(s) resulting from extension usage.
 * We reserve the full right not to provide any kind of support for our free extensions.
 * Thank you for your understanding.
 *
 * @category    Inchoo
 * @package     Inchoo_Fiskalizacija
 * @author      Branko Ajzele <ajzele@gmail.com>
 * @copyright   Copyright (c) Inchoo (http://inchoo.net/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
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
