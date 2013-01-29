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
class Inchoo_Fiskalizacija_Block_Adminhtml_Edit_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('inchoo_fiskalizacija');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('inchoo_fiskalizacija/invoice')
                                ->getCollection();

        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('ID'),
            'sortable' => true,
            'index' => 'entity_id',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('customer')->__('Vrijeme kreiranja'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'renderer'  => 'inchoo_fiskalizacija/adminhtml_edit_renderer_createdat'
        ));

        $this->addColumn('parent_entity_type', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('Vrsta računa'),
            'sortable' => true,
            'index' => 'parent_entity_type',
            'type'  => 'options',
            'options' => array(
                'invoice' => Mage::helper('inchoo_fiskalizacija')->__('Invoice (redovan)'),
                'creditmemo' => Mage::helper('inchoo_fiskalizacija')->__('Credit Memo (storni)')
            ),
        ));

        $this->addColumn('parent_entity_id', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('Račun ID'),
            'sortable' => true,
            'index' => 'parent_entity_id',
            'renderer'  => 'inchoo_fiskalizacija/adminhtml_edit_renderer_parentid'
        ));

        $resource = Mage::getSingleton('core/resource');
        $conn = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('inchoo_fiskalizacija/invoice');

        $poslProstori = $conn->fetchCol("SELECT DISTINCT posl_prostor FROM {$tableName}");


        $_poslProstori = array();

        foreach ($poslProstori as $poslProstor) {
            $_poslProstori[$poslProstor] = $poslProstor;
        }

        $this->addColumn('posl_prostor', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('Poslovni prostor'),
            'sortable' => true,
            'index' => 'posl_prostor',
            'type'  => 'options',
            'options' => $_poslProstori
        ));

        $this->addColumn('br_rac', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('Broj računa'),
            'sortable' => true,
            'index' => 'br_rac',
        ));

        $this->addColumn('jir', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('JIR'),
            'sortable' => true,
            'index' => 'jir',
            'width' => '250px',
            'renderer'  => 'inchoo_fiskalizacija/adminhtml_edit_renderer_jir'
        ));

        $this->addColumn('zast_kod', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('Zaštitni kod'),
            'sortable' => true,
            'index' => 'zast_kod',
        ));

        $this->addColumn('customer_notified', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('Kupac obavješten'),
            'sortable' => true,
            'index' => 'customer_notified',
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('inchoo_fiskalizacija');

        $this->getMassactionBlock()->addItem('email', array(
            'label'=> Mage::helper('inchoo_fiskalizacija')->__('Email Customer'),
            'url'  => $this->getUrl('*/*/massEmail'),
            'confirm' => Mage::helper('inchoo_fiskalizacija')->__('Are you sure?')
        ));

        Mage::dispatchEvent('inchoo_fiskalizacija_grid_prepare_massaction', array('block' => $this));
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
