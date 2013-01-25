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
class Inchoo_Fiskalizacija_Adminhtml_Inchoo_FiskalizacijaController extends Mage_Adminhtml_Controller_Action 
{
    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('sales/inchoo_fiskalizacija');
        $this->_addContent($this->getLayout()->createBlock('inchoo_fiskalizacija/adminhtml_edit'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('inchoo_fiskalizacija/adminhtml_edit_grid')->toHtml()
        );
    }

    protected function _initAction()
    {
        $finvoice = Mage::getModel('inchoo_fiskalizacija/invoice')
                        ->load($this->getRequest()->getParam('finvoice_id', 0));
        
        Mage::register('current_finvoice', $finvoice);
        
        $this->loadLayout()
            ->_setActiveMenu('sales/invoice');
        
        return $this;
    }    
    public function resignAndSubmitAction()
    {
        $this->_title($this->__('Fiskalizacija'))->_title($this->__('Pregledaj/uredi RacunZahtjev'));

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('inchoo_fiskalizacija/adminhtml_sales_order_finvoice_edit'))
            ->renderLayout();        
    }

    public function postResignAndSubmitAction()
    {
        $helper = Mage::helper('inchoo_fiskalizacija');

        //Zend_Debug::dump($this->getRequest()->getParams()); exit;

        if ($this->getRequest()->isPost()) {
            $this->_initAction();

            $XML_RacunZahtjev = $this->getRequest()->getParam('xml_request_raw_body');
            //$entityType = $this->getRequest()->getParam('parent_entity_type');

            $RacunZahtjevDOMDocument = new DOMDocument();
            $RacunZahtjevDOMDocument->loadXML($XML_RacunZahtjev);
            $RacunZahtjevDOMDocument->encoding = 'UTF-8';
            $RacunZahtjevDOMDocument->version = '1.0';

            $RacunZahtjevNode = $RacunZahtjevDOMDocument->getElementsByTagName('RacunZahtjev')->item(0);
            $UriId = $RacunZahtjevNode->getAttribute('Id');

            $fiscalInvoice = Mage::registry('current_finvoice');
            $fiscalInvoice->setXmlRequestRawBody($XML_RacunZahtjev);
            $fiscalInvoice->save();

            $entity = Mage::getModel('sales/order_'.$fiscalInvoice->getParentEntityType())
                            ->load($fiscalInvoice->getParentEntityId());

            if ($helper->isModuleEnabled($entity->getStoreId()) == false) {
                $this->_redirectReferer();
                return;
            }

            //$XML_RacunZahtjev = $helper->generateRacunZahtjevXML($fiscalInvoice, $entity, $entity->getStoreId());

            /* Digitally sign XML */
            Mage::getModel('inchoo_fiskalizacija/XmlSigner')
                ->sign($RacunZahtjevDOMDocument, $UriId, $entity->getStoreId());

            /* Wrap signed XML document into SOAP envelope */
            $helper->wrapIntoSoapEnvelope($RacunZahtjevDOMDocument, 'RacunZahtjev');

            $fiscalInvoice->setSignedXmlRequestRawBody($RacunZahtjevDOMDocument->saveXML());
            /* Save in order to update $fiscalInvoice with SignedXmlRequestRawBody */
            $fiscalInvoice->save();

            /* Send signed request to Fiskalizacija SOAP service */
            $response = null;

            try {
                $client = new Zend_Http_Client();
                $client->setUri($helper->getFiskalizacijaSOAPServerEndpoint($entity->getStoreId()));
                $client->setMethod(Zend_Http_Client::POST);
                $client->setRawData($RacunZahtjevDOMDocument->saveXML());

                $response = $client->request();
            } catch(Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addWarning($helper->__('CIS server nedostupan.'));
                $this->_redirectReferer();
                return;
            }

            $fiscalInvoice->setLastServiceResponseBody($response->getBody());
            $fiscalInvoice->setLastServiceResponseStatus($response->getStatus());

            $DOMDocument = new DOMDocument();
            $DOMDocument->loadXML($response->getBody());

            $errorLogFile = sprintf('fiscalInvoice_%s_%s.log', $fiscalInvoice->getId(), time());

            $jir = null;

            if ($response->isSuccessful() && $response->getStatus() === 200) {

                $jirNode = $DOMDocument->getElementsByTagName('Jir')->item(0);

                if ($jirNode) {
                    $jir = $jirNode->nodeValue;
                    $fiscalInvoice->setJir($jir);
                    Mage::getSingleton('adminhtml/session')->addSuccess($helper->__('JIR %s.', $jir));
                    $dt = new DateTime('now', new DateTimeZone(Mage::app()->getStore($fiscalInvoice->getStoreId())->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE)));
                    $fiscalInvoice->setJirObtainedAt($dt->format('Y-m-d H:i:s'));
                    $fiscalInvoice->setModifiedAt($dt->format('Y-m-d H:i:s'));
                } else {

                    Mage::log($response->getRawBody(), null, $errorLogFile, true);

                    $PorukaGreskeNode = $DOMDocument->getElementsByTagName('PorukaGreske')->item(0);

                    if ($PorukaGreskeNode) {
                        $PorukaGreske = $PorukaGreskeNode->nodeValue;
                        Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: %s.', $response->getStatus(), $PorukaGreske));
                    } else {
                        Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: -- N/A --.', $response->getStatus()));
                    }

                    Mage::log($response->getRawBody(), null, sprintf('fiscalInvoice_%s_%s.log', $fiscalInvoice->getId(), time()), true);
                }
            } else {
                $PorukaGreskeNode = $DOMDocument->getElementsByTagName('PorukaGreske')->item(0);
                if ($PorukaGreskeNode) {
                    $PorukaGreske = $PorukaGreskeNode->nodeValue;
                    Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: %s.', $response->getStatus(), $PorukaGreske));
                } else {
                    $PorukaGreske = $PorukaGreskeNode->nodeValue;
                    Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: -- N/A --.', $response->getStatus()));
                }

                Mage::log($response->getRawBody(), null, $errorLogFile, true);
            }

            $DOM_RacunZahtjev = new DOMDocument();
            $DOM_RacunZahtjev->loadXML($XML_RacunZahtjev);
            $ZastKod = $DOM_RacunZahtjev->getElementsByTagName('ZastKod')->item(0);

            /* BrRac */
            $BrRac = null;
            $BrOznRac = $DOM_RacunZahtjev->getElementsByTagName('BrOznRac')->item(0);
            $OznPosPr = $DOM_RacunZahtjev->getElementsByTagName('OznPosPr')->item(0);
            $OznNapUr = $DOM_RacunZahtjev->getElementsByTagName('OznNapUr')->item(0);

            if ($BrOznRac && $OznPosPr && $OznNapUr) {
                $BrRac = sprintf("%s/%s/%s", $BrOznRac->nodeValue, $OznPosPr->nodeValue, $OznNapUr->nodeValue);
                $fiscalInvoice->setBrRac($BrRac);
            }

            if ($ZastKod && $jir && $BrRac) {
                $resource = Mage::getSingleton('core/resource');
                $conn = $resource->getConnection('core_write');

                $data = array(
                    'inchoo_fiskalizacija_jir' => $jir,
                    'inchoo_fiskalizacija_zast_kod' => $ZastKod->nodeValue,
                    'inchoo_fiskalizacija_br_rac' => $BrRac,
                    'inchoo_fiskalizacija_oib' => $fiscalInvoice->getOib(),
                    'inchoo_fiskalizacija_blagajnik' => $fiscalInvoice->getBlagajnik(),
                );

                try {
                    $conn->update($resource->getTableName('sales/'.$entityType), $data, 'entity_id = '.$entity->getId());
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            $fiscalInvoice->setTotalRequestAttempts((int)$fiscalInvoice->getTotalRequestAttempts() + 1);
            /* Save in order to update $fiscalInvoice with SignedXmlRequestRawBody */
            $fiscalInvoice->save();
        }

        if ($fiscalInvoice->getParentEntityType() == Inchoo_Fiskalizacija_Model_Observer::FISCAL_EVENT_TYPE_INVOICE) {
            $this->_redirect('*/sales_order_invoice/view', array('invoice_id' => $fiscalInvoice->getParentEntityId(), 'order_id' => $this->getRequest()->getParam('order_id', 0)));
        } else {
            $this->_redirect('*/sales_creditmemo/view', array('creditmemo_id' => $fiscalInvoice->getParentEntityId()));
        }

    }

    public function businessUnitAction()
    {           
        $website = Mage::getModel('core/website')
                        ->load($this->getRequest()->getParam('website'), 'code');

        $store = $website->getDefaultStore()->getId();
        
        $helper = Mage::helper('inchoo_fiskalizacija');

        $UriId = uniqid();
        
        if ($this->getRequest()->getParam('OznakaZatvaranja')) {
            $PoslovniProstorZahtjevDOMDocument = Mage::getModel('inchoo_fiskalizacija/PoslovniProstorZahtjev')
                                                        ->generate($UriId, $store, true);
        } else {
            $PoslovniProstorZahtjevDOMDocument = Mage::getModel('inchoo_fiskalizacija/PoslovniProstorZahtjev')
                                                        ->generate($UriId, $store);
        }

        /* Digitally sign XML */
        Mage::getModel('inchoo_fiskalizacija/XmlSigner')
            ->sign($PoslovniProstorZahtjevDOMDocument, $UriId, $store);
        /* Wrap signed XML document into SOAP envelope */
        $helper->wrapIntoSoapEnvelope($PoslovniProstorZahtjevDOMDocument, 'PoslovniProstorZahtjev');

        /* Send signed request to Fiskalizacija SOAP service */
        $client = new Zend_Http_Client();
        $client->setUri($helper->getFiskalizacijaSOAPServerEndpoint($store));
        $client->setMethod(Zend_Http_Client::POST);
        $client->setRawData($PoslovniProstorZahtjevDOMDocument->saveXML());


        $response = $client->request();

        $DOMDocument = new DOMDocument();
        $DOMDocument->loadXML($response->getBody());    
        
        $errorLogFile = sprintf('PoslovniProstorZahtjev_%s.log', time());
        
        if ($response->isSuccessful() && $response->getStatus() === 200) {
            Mage::getSingleton('adminhtml/session')->addSuccess($helper->__('PoslovniProstorZahtjev usjpešno obrađen.'));
        } else {
            $PorukaGreskeNode = $DOMDocument->getElementsByTagName('PorukaGreske')->item(0);
                if ($PorukaGreskeNode) {
                    $PorukaGreske = $PorukaGreskeNode->nodeValue;
                    Mage::getSingleton('adminhtml/session')->addWarning($helper->__('CIS server status odgovora: %s. Poruka odgovora: %s.', $response->getStatus(), $PorukaGreske));
                } else {
                    Mage::getSingleton('adminhtml/session')->addWarning($helper->__('CIS server status odgovora: %s. Poruka odgovora: -- nepoznato --.', $response->getStatus()));
                }            
            Mage::log($response->getRawBody(), null, $errorLogFile, true);
        }        
        
        $this->_redirectReferer();            
    }
    
    public function businessUnitCloseAction()
    {
        $params = $this->getRequest()->getParams();
        $params['OznakaZatvaranja'] = 'Z';
        
        $this->_forward('businessUnit', null, null, $params);
    }    
    
    public function businessUnitReportAction()
    {
        $this->_forward('businessUnit');
    }

    public function massEmailAction()
    {
        $helper = Mage::helper('inchoo_fiskalizacija');

        if (($entities = $this->getRequest()->getParam('inchoo_fiskalizacija'))) {

            foreach ($entities as $entityId) {
                $entity = Mage::getModel('inchoo_fiskalizacija/invoice')
                                ->load($entityId);

                if (!$entity->getId()) {
                    continue;
                }

                if ($entity->getParentEntityType() == Inchoo_Fiskalizacija_Model_Observer::FISCAL_EVENT_TYPE_INVOICE) {
                    $invoice = Mage::getModel('sales/order_invoice')
                                    ->load($entity->getParentEntityId());

                    /* ->sendEmail($notifyCustomer = true, $comment = ''); */
                    $comment = $helper->__('Naknadno poslan račun.');
                    try {
                        $invoice->sendEmail(true, $comment);
                        $entity->setCustomerNotified($entity->getCustomerNotified() + 1);
                        $entity->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }

                if ($entity->getParentEntityType() == Inchoo_Fiskalizacija_Model_Observer::FISCAL_EVENT_TYPE_CREDITMEMO) {
                    $creditmemo = Mage::getModel('sales/order_creditmemo')
                                    ->load($entity->getParentEntityId());

                    /* ->sendEmail($notifyCustomer = true, $comment = ''); */
                    $comment = $helper->__('Naknadno poslan stornirani račun.');
                    try {
                        $creditmemo->sendEmail(true, $comment);
                        $entity->setCustomerNotified($entity->getCustomerNotified() + 1);
                        $entity->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }

        }

        $this->_redirectReferer();
    }
}
