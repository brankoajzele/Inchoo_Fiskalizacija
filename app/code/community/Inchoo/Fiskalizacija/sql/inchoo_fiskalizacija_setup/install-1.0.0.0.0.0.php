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

$installer = $this;
/* @var $installer Inchoo_Fiskalizacija_Model_Resource_Setup */

$installer->startSetup();

/* START Cleanup version 0.9.2.0.0.0, full remove */
$installer->run("
DROP TABLE IF EXISTS {$installer->getTable('inchoo_fiskalizacija/cert')};
DROP TABLE IF EXISTS {$installer->getTable('inchoo_fiskalizacija/invoice')};
");
/* END Cleanup version 0.9.2.0.0.0, full remove */

$table = $installer->getConnection()
    ->newTable($installer->getTable('inchoo_fiskalizacija/cert'))
    ->addColumn('cert_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Created At')     
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        ), 'Website Id')
    ->addColumn('pfx_cert', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Original Certificate in PFX format')        
    ->addColumn('pem_private_key', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Private Key in PEM format')
    ->addColumn('pem_private_key_passphrase', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Passphrase for Private Key in PEM format')        
    ->addColumn('pem_public_cert', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Public Certificate in PEM format')
    ->addColumn('pem_public_cert_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Name of the Public Certificate in PEM format')
    ->addColumn('pem_public_cert_serial_number', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        ), 'Serial Number of the Public Certificate in PEM format')
    ->addColumn('pem_public_cert_hash', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Hash of the Public Certificate in PEM format')        
    ->addColumn('pem_public_cert_info', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Info from Public Certificate in PEM format')        
    ->addColumn('valid_from', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Valid From')
    ->addColumn('valid_to', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Valid To')        
    ->addIndex(
        $installer->getIdxName(
            'inchoo_fiskalizacija/cert',
            array('website_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('website_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex(
        $installer->getIdxName(
            'inchoo_fiskalizacija/cert',
            array('pem_public_cert_serial_number'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('pem_public_cert_serial_number'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))         
    ->setComment('Fiscalization Certificates');
$installer->getConnection()->createTable($table);

$table = $installer->getConnection()
    ->newTable($installer->getTable('inchoo_fiskalizacija/invoice'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Created At')     
    ->addColumn('parent_entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => true,
        ), 'Parent (invoice/creditmemo) Id')
    ->addColumn('parent_entity_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 32, array(
    'nullable'  => true,
    ), 'String like invoice or creditmemo')
    ->addColumn('br_ozn_rac', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'nullable'  => false,
    ), 'BrOznRac')
    ->addColumn('posl_prostor', Varien_Db_Ddl_Table::TYPE_VARCHAR, 32, array(
    'nullable'  => false,
    ), 'OznPoslProstora')
    ->addColumn('xml_request_raw_body', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Final XML request, non-signed ready to be signed')
    ->addColumn('signed_xml_request_raw_body', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Final signed XML request, ready to be sent to fiscalization SOAP service')       
    ->addColumn('total_request_attempts', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        ), 'Number of attempts sending the request')
    ->addColumn('br_rac', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => true,
    ), 'Broj racuna')
    ->addColumn('oib', Varien_Db_Ddl_Table::TYPE_CHAR, 11, array(
    'nullable'  => true,
    ), 'OIB firme')
    ->addColumn('blagajnik', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable'  => true,
    ), 'Blagajnik')
    ->addColumn('zast_kod', Varien_Db_Ddl_Table::TYPE_CHAR, 32, array(
        'nullable'  => true,
        ), 'Zastitni kod')        
    ->addColumn('jir', Varien_Db_Ddl_Table::TYPE_CHAR, 36, array(
        'nullable'  => true,
        ), 'Time when requested passed and JIR was obtained')        
    ->addColumn('jir_obtained_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
        ), 'Time when requested passed and JIR was obtained')
    ->addColumn('last_service_response_body', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        ), 'Last service response body')
    ->addColumn('last_service_response_status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => true,
        ), 'Last service response status')        
    ->addColumn('modified_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Time when entry was last modified')
    ->addColumn('customer_notified', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'nullable'  => true,
    'default' => '0'
    ), 'Number of time customer has been notified via email')
    ->addIndex(
        $installer->getIdxName(
            'inchoo_fiskalizacija/invoice',
            array('parent_entity_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('parent_entity_type', 'parent_entity_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->setComment('Fiscalization Invoice')
    ->addIndex(
        $installer->getIdxName(
            'inchoo_fiskalizacija/invoice',
            array('br_ozn_rac'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('posl_prostor', 'br_ozn_rac'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->setComment('Br.racuna po poslovnom prostoru');
$installer->getConnection()->createTable($table);

/* START Add columns to invoice table */
$installer->getConnection()
    ->addColumn($installer->getTable('sales/invoice'), 'inchoo_fiskalizacija_jir', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 36,
        'nullable'  => true,
        'comment'   => 'Shows what entity history is bind to.'
    ));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/invoice'), 'inchoo_fiskalizacija_zast_kod', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 32,
        'nullable'  => true,
        'comment'   => 'Shows what entity history is bind to.'
    ));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/invoice'), 'inchoo_fiskalizacija_br_rac', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 128,
    'nullable'  => true,
    'comment'   => 'Shows the fiscal invoice number.'
));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/invoice'), 'inchoo_fiskalizacija_oib', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 11,
    'nullable'  => true,
    'comment'   => 'Company OIB'
));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/invoice'), 'inchoo_fiskalizacija_blagajnik', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 128,
    'nullable'  => true,
    'comment'   => 'Blagajnik'
));
/* END Add columns to invoice table */



/* START Add trigger to inchoo_fiskalizacija_invoice table */
$invoiceTableName = $installer->getTable('inchoo_fiskalizacija/invoice');
$invoiceTableTriggerName = 'before_'.$invoiceTableName;

$sql = "CREATE
TRIGGER {$invoiceTableTriggerName}
BEFORE INSERT ON {$invoiceTableName}
FOR EACH ROW
BEGIN
	SET NEW.br_ozn_rac = (SELECT IFNULL(MAX(x.br_ozn_rac), 0) + 1
						   FROM {$invoiceTableName} x
						   WHERE x.posl_prostor = NEW.posl_prostor);
END";

/**
 * NOTE: Using $installer->run() does not work!!!
 */

Mage::getSingleton('core/resource')
    ->getConnection('core_write')
    ->query($sql);
/* END Add trigger to inchoo_fiskalizacija_invoice table */



/* START Add columns to creditmemo table */
$installer->getConnection()
    ->addColumn($installer->getTable('sales/creditmemo'), 'inchoo_fiskalizacija_jir', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 36,
    'nullable'  => true,
    'comment'   => 'Shows what entity history is bind to.'
));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/creditmemo'), 'inchoo_fiskalizacija_zast_kod', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 32,
    'nullable'  => true,
    'comment'   => 'Shows what entity history is bind to.'
));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/creditmemo'), 'inchoo_fiskalizacija_br_rac', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 128,
    'nullable'  => true,
    'comment'   => 'Shows the fiscal invoice number.'
));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/creditmemo'), 'inchoo_fiskalizacija_oib', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 11,
    'nullable'  => true,
    'comment'   => 'Company OIB'
));

$installer->getConnection()
    ->addColumn($installer->getTable('sales/creditmemo'), 'inchoo_fiskalizacija_blagajnik', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 128,
    'nullable'  => true,
    'comment'   => 'Blagajnik'
));
/* END Add columns to creditmemo table */

$installer->endSetup();