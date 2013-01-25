<?php

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
            'header'    => Mage::helper('customer')->__('Kreirano'),
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('parent_entity_type', array(
            'header' => Mage::helper('inchoo_fiskalizacija')->__('Vrsta računa'),
            'sortable' => true,
            'index' => 'parent_entity_type',
            'type'  => 'options',
            'options' => array(
                'invoice' => Mage::helper('inchoo_fiskalizacija')->__('Invoice'),
                'creditmemo' => Mage::helper('inchoo_fiskalizacija')->__('Credit Memo')
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

//        $this->addColumn('total_request_attempts', array(
//            'header' => Mage::helper('inchoo_fiskalizacija')->__('Total Request Attempts'),
//            'sortable' => true,
//            'index' => 'total_request_attempts',
//        ));

//        $this->addColumn('jir_obtained_at', array(
//            'header'    => Mage::helper('customer')->__('JIR Obtained At'),
//            'index'     => 'jir_obtained_at',
//            'type'      => 'datetime',
//        ));

//        $this->addColumn('blagajnik', array(
//            'header' => Mage::helper('inchoo_fiskalizacija')->__('Cashier'),
//            'sortable' => true,
//            'index' => 'blagajnik',
//        ));



        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('inchoo_fiskalizacija');

//        $this->getMassactionBlock()->addItem('jir_from_cis', array(
//            'label'=> Mage::helper('inchoo_fiskalizacija')->__('Get JIR from CIS'),
//            'url'  => $this->getUrl('*/*/massResend'),
//            'confirm' => Mage::helper('inchoo_fiskalizacija')->__('Are you sure?')
//        ));

        $this->getMassactionBlock()->addItem('email', array(
            'label'=> Mage::helper('inchoo_fiskalizacija')->__('Email Customer'),
            'url'  => $this->getUrl('*/*/massEmail'),
            'confirm' => Mage::helper('inchoo_fiskalizacija')->__('Are you sure?')
        ));

//        $this->getMassactionBlock()->addItem('email_pdf', array(
//            'label'=> Mage::helper('inchoo_fiskalizacija')->__('PDF Email Customer'),
//            'url'  => $this->getUrl('*/*/massEmailPdf'),
//            'confirm' => Mage::helper('inchoo_fiskalizacija')->__('Are you sure?')
//        ));

        Mage::dispatchEvent('inchoo_fiskalizacija_grid_prepare_massaction', array('block' => $this));
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
