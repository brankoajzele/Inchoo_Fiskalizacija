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
class Inchoo_Fiskalizacija_Model_Observer
{
    const FISCAL_EVENT_TYPE_INVOICE = 'invoice';
    const FISCAL_EVENT_TYPE_CREDITMEMO = 'creditmemo';

    private $_helper = null;
    
    public function __construct() 
    {
        $this->_helper = Mage::helper('inchoo_fiskalizacija');
    }

    /**
     * Tied to sales_order_invoice_save_after event.
     *
     * @param null $observer
     * @return Inchoo_Fiskalizacija_Model_Observer or null
     */
    public function pushRacunZahtjevToFiscalizationService($observer = null)
    {
        if ($observer == null) { return; }

        $entityType = null;
        $storeId = null;
        $entity = null;

        $invoice = $observer->getEvent()->getInvoice();
        $creditmemo = $observer->getEvent()->getCreditmemo();

        if ($invoice) {
            $entity = $invoice;
            $entityType = self::FISCAL_EVENT_TYPE_INVOICE;
        } elseif ($creditmemo) {
            $entity = $creditmemo;
            $entityType = self::FISCAL_EVENT_TYPE_CREDITMEMO;
        } else {
            return;
        }

        $helper = Mage::helper('inchoo_fiskalizacija');
        $storeId = $entity->getStoreId();

        $websiteId = Mage::getModel('core/store')
            ->load($storeId)
            ->getWebsiteId();


        if ($helper->isModuleEnabled($storeId) == false) {
            return;
        }

        /* Since this is after save event, check if there is already entry in db... */
        $existingFiscalInvoice = Mage::getModel('inchoo_fiskalizacija/invoice')
                                    ->getCollection()
                                    ->addFieldToFilter('parent_entity_id', $entity->getId())
                                    ->addFieldToFilter('parent_entity_type', $entityType)
                                    ->getFirstItem();

        if ($existingFiscalInvoice && $existingFiscalInvoice->getId()) {
            return $this;
        }

        $dt = new DateTime('now'); /* $dt->format('Y-m-d H:i:s') */

        /*
         * Create initial entry to reserve the entity_id flow
         * for RacunZahtjev.Racun.BrRac.BrOznRac.
         *
         * That is => inchoo_fiskalizacija_invoice.entity_id == RacunZahtjev.Racun.BrRac.BrOznRac
         */
        $fiscalInvoice = Mage::getModel('inchoo_fiskalizacija/invoice');

        $fiscalInvoice->setCreatedAt($dt->format('Y-m-d H:i:s'));
        $fiscalInvoice->setStoreId($storeId);
        $fiscalInvoice->setWebsiteId($websiteId);
        $fiscalInvoice->setParentEntityId($entity->getId());
        $fiscalInvoice->setParentEntityType($entityType);
        $fiscalInvoice->setPoslProstor($helper->getPoslovniProstorOznPoslProstora($storeId));
        $fiscalInvoice->setXmlRequestRawBody('');
        $fiscalInvoice->setSignedXmlRequestRawBody('');
        $fiscalInvoice->setTotalRequestAttempts(0);
        $fiscalInvoice->setOib($helper->getOib($storeId));
        $fiscalInvoice->setBlagajnik($helper->getRacunBlagajnik($storeId));

        $creditmemo = Mage::app()->getRequest()->getParam('creditmemo', array());
        $invoice = Mage::app()->getRequest()->getParam('invoice', array());

        if (!empty($creditmemo['send_email']) || !empty($invoice['send_email'])) {
            $fiscalInvoice->setCustomerNotified($fiscalInvoice->getCustomerNotified() + 1);
        }

        try {
            $fiscalInvoice->save();
            $fiscalInvoice->load($fiscalInvoice->getId()); /* Required!!! */
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $UriId = uniqid();

        if ($entityType == self::FISCAL_EVENT_TYPE_INVOICE) {
            $RacunZahtjevDOMDocument = Mage::getModel('inchoo_fiskalizacija/RacunZahtjev')
                                        ->generate($UriId, $fiscalInvoice, $entity, $storeId);
        } else {
            /* Storni racun */
            $RacunZahtjevDOMDocument = Mage::getModel('inchoo_fiskalizacija/RacunZahtjev')
                                        ->generate($UriId, $fiscalInvoice, $entity, $storeId, true);
        }

        $ZastKod = $RacunZahtjevDOMDocument->getElementsByTagName('ZastKod')->item(0);

        /* BrRac */
        $BrRac = null;
        $BrOznRac = $RacunZahtjevDOMDocument->getElementsByTagName('BrOznRac')->item(0);
        $OznPosPr = $RacunZahtjevDOMDocument->getElementsByTagName('OznPosPr')->item(0);
        $OznNapUr = $RacunZahtjevDOMDocument->getElementsByTagName('OznNapUr')->item(0);

        if ($BrOznRac && $OznPosPr && $OznNapUr) {
            $BrRac = sprintf("%s/%s/%s", $BrOznRac->nodeValue, $OznPosPr->nodeValue, $OznNapUr->nodeValue);
            $fiscalInvoice->setBrRac($BrRac);
        }

        if ($ZastKod) {
            $fiscalInvoice->setZastKod($ZastKod->nodeValue);
        }

        $fiscalInvoice->setXmlRequestRawBody($RacunZahtjevDOMDocument->saveXML());
        /* Save in order to update $fiscalInvoice with XmlRequestRawBody */
        $fiscalInvoice->save();

        /* Digitally sign XML */
        Mage::getModel('inchoo_fiskalizacija/XmlSigner')
            ->sign($RacunZahtjevDOMDocument, $UriId, $storeId);

        /* Wrap signed XML document into SOAP envelope */
        $helper->wrapIntoSoapEnvelope($RacunZahtjevDOMDocument, 'RacunZahtjev');

        $fiscalInvoice->setSignedXmlRequestRawBody($RacunZahtjevDOMDocument->saveXML());
        /* Save in order to update $fiscalInvoice with SignedXmlRequestRawBody */
        $fiscalInvoice->save();

        /* Send signed request to Fiskalizacija SOAP service */
        $response = null;

        $CACertPath = $helper->getCertificateCaPemPath($storeId);
        if (empty($CACertPath) || !file_exists($CACertPath)) {
            Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Verifikacijski/root (samopotpisani) certifikat CA nedostaje.'));
            return;
        }

        try {
            $client = new Zend_Http_Client();
            $client->setUri($helper->getFiskalizacijaSOAPServerEndpoint($storeId));
            $client->setConfig(array(
                'adapter' => 'Zend_Http_Client_Adapter_Curl',
                'curloptions' => array(
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CAINFO => $CACertPath,
                ),
            ));
            $client->setMethod(Zend_Http_Client::POST);
            $client->setRawData($RacunZahtjevDOMDocument->saveXML());

            $response = $client->request();
        } catch(Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addWarning($helper->__('CIS server nedostupan.'));
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
                $jir = trim($jir);
                $fiscalInvoice->setJir($jir);
                Mage::getSingleton('adminhtml/session')->addSuccess($helper->__('JIR %s.', $jir));
                $dt = new DateTime('now'); /* $dt->format('Y-m-d H:i:s') */
                if (!empty($jir)) {
                    $fiscalInvoice->setJirObtainedAt($dt->format('Y-m-d H:i:s'));
                }
            } else {

                Mage::log($response->getRawBody(), null, $errorLogFile, true);

                $PorukaGreskeNode = $DOMDocument->getElementsByTagName('PorukaGreske')->item(0);

                if ($PorukaGreskeNode) {
                    $PorukaGreske = $PorukaGreskeNode->nodeValue;
                    Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: %s.', $response->getStatus(), $PorukaGreske));
                } else {
                    Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: %s.', $response->getStatus(), $response->getMessage()));
                }

                Mage::log($response->getRawBody(), null, sprintf('fiscalInvoice_%s_%s.log', $fiscalInvoice->getId(), time()), true);
            }
        } else {
            $PorukaGreskeNode = $DOMDocument->getElementsByTagName('PorukaGreske')->item(0);
            if ($PorukaGreskeNode) {
                $PorukaGreske = $PorukaGreskeNode->nodeValue;
                Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: %s.', $response->getStatus(), $PorukaGreske));
            } else {
                Mage::getSingleton('adminhtml/session')->addWarning($helper->__('Pogreška prilikom dohvaćanja JIR-a. Status: %s. Poruka: %s.', $response->getStatus(), $response->getMessage()));
            }

            Mage::log($response->getRawBody(), null, $errorLogFile, true);
        }

        if ($ZastKod && $BrRac) {

            $fiscalInvoice->setTotalRequestAttempts((int)$fiscalInvoice->getTotalRequestAttempts() + 1);

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
                Mage::getSingleton('adminhtml/session')->addWarning($helper->__($e->getMessage()));
            }
        }


        /* Save in order to update $fiscalInvoice with SignedXmlRequestRawBody */
        $fiscalInvoice->save();
    }

    /**
     * Tied to core_block_abstract_to_html_after event.
     * Attaches the "Fiskalizacija" info block on the invoice view screen,
     * without the need to have custom *.phtml template file.
     *
     * @param null $observer
     */
    public function injectFiscalBlock($observer = null)
    {
        if ($observer->getEvent()->getBlock()->getNameInLayout() !== 'order_info') {
            return;
        }

        $currentUrl = Mage::helper('core/url')->getCurrentUrl();

        /* START Filter block inject only on "invoice or creditmemo view" pages */
        $patternOrderInvoice = 'sales_order_invoice/view/invoice_id';
        $patternInvoice = 'sales_invoice/view/invoice_id';

        $patternOrderCreditmemo = 'sales_order_creditmemo/view/creditmemo_id';
        $patternCreditmemo = 'sales_creditmemo/view/creditmemo_id';

        if (strstr($currentUrl, $patternInvoice) || strstr($currentUrl, $patternOrderInvoice)) {
            $entityType = self::FISCAL_EVENT_TYPE_INVOICE;
            $entity = Mage::registry('current_invoice');
        } elseif (strstr($currentUrl, $patternCreditmemo) || strstr($currentUrl, $patternOrderCreditmemo)) {
            $entityType = self::FISCAL_EVENT_TYPE_CREDITMEMO;
            $entity = Mage::registry('current_creditmemo');
        } else {
            return;
        }
        /* END Filter block inject only on "invoice or creditmemo view" pages */

        $helper = Mage::helper('inchoo_fiskalizacija');
        $storeId = $entity->getStoreId();

        if ($helper->isModuleEnabled($storeId) == false) {
            return;
        }

        /* Check if there is an fiscal invoice entry in inchoo_fiskalizacija_invoice table */
        $fiscalInvoice = Mage::getModel('inchoo_fiskalizacija/invoice')
                        ->getCollection()
                        ->addFieldToFilter('parent_entity_id', $entity->getId())
                        ->addFieldToFilter('parent_entity_type', $entityType)
                        ->getFirstItem();

        /* If there is no fiscal invoice, do not render anything - return */
        if (!$fiscalInvoice || !$fiscalInvoice->getId()) {
            return;
        }

        /* Start preparing output block */

        $dtEntityCreatedAt = $entity->getCreatedAt();
        if (!empty($dtEntityCreatedAt)) {
            $dtEntityCreatedAt = Mage::helper('core')->formatDate(Mage::getModel('core/locale')->storeDate($storeId, $entity->getCreatedAt(), true), Mage_Core_Model_Locale::FORMAT_TYPE_FULL, true);
        }

        $dtFiscalInvoiceCreatedAt = $fiscalInvoice->getCreatedAt();
        if (!empty($dtFiscalInvoiceCreatedAt)) {
            $dtFiscalInvoiceCreatedAt = Mage::helper('core')->formatDate(Mage::getModel('core/locale')->storeDate($storeId, $fiscalInvoice->getCreatedAt(), true), Mage_Core_Model_Locale::FORMAT_TYPE_FULL, true);
        }

        $dtFiscalInvoiceJirObtainedAt = $fiscalInvoice->getJirObtainedAt();
        if (!empty($dtFiscalInvoiceJirObtainedAt)) {
            $dtFiscalInvoiceJirObtainedAt = Mage::helper('core')->formatDate(Mage::getModel('core/locale')->storeDate($storeId, $fiscalInvoice->getJirObtainedAt(), true), Mage_Core_Model_Locale::FORMAT_TYPE_FULL, true);
        }

        $html = '<div>
                    <!--CIS Info-->
                    <div class="entry-edit">
                        <div class="entry-edit-head">
                            <h4 class="icon-head head-cis-info">Fiskalizacija</h4>
                        </div>
                        <fieldset>';

        /* We can either use $fiscalInvoice or $entity to fetch fiscal invoice info like JIR */
        $html .= '<div>BrRac: <strong>'.$entity->getInchooFiskalizacijaBrRac().'</strong></div>';
        $html .= '<div>ZastKod: <strong>'.$entity->getInchooFiskalizacijaZastKod().'</strong></div>';
        $html .= '<div>JIR: <strong>'.$entity->getInchooFiskalizacijaJir().'</strong></div>';
        $html .= '<div>OIB firme: <strong>'.$entity->getInchooFiskalizacijaOib().'</strong></div>';
        $html .= '<div>Oznaka blagajnika (OIB/naziv): <strong>'.$entity->getInchooFiskalizacijaBlagajnik().'</strong></div>';
        $html .= '<div>Vrijeme kreiranja računa: <strong>'.$dtEntityCreatedAt.'</strong></div>';
        $html .= '<div>Vrijeme kreiranja fiskalnog računa: <strong>'.$dtFiscalInvoiceCreatedAt.'</strong></div>';

        if ($entity->getInchooFiskalizacijaJir()) {
            $html .= '<div>Vrijeme potvrđenog fiskalnog računa (JIR dohvaćen): <strong>'.$dtFiscalInvoiceJirObtainedAt.'</strong></div>';
        }

        $html .= '<div>Ukupno poslanih zahtjeva: <strong>'.$fiscalInvoice->getTotalRequestAttempts().'</strong></div>';

        $urlParams = array(
            'parent_entity_id' => $entity->getId(),
            'parent_entity_type' => $entityType,
            'order_id' => $entity->getOrder()->getId(),
            'finvoice_id' => $fiscalInvoice->getId()
        );

        if (!$entity->getInchooFiskalizacijaJir()) {
            $html .= '<div><a href="'.Mage::helper("adminhtml")->getUrl('adminhtml/inchoo_fiskalizacija/resignAndSubmit', $urlParams).'">JIR nedostaje! PONOVI zahtjev!</a></div>';
        }

        $html .= '</fieldset>
                    </div>
                </div>
                <div class="clear"></div>';

        $fiscal = Mage::app()->getLayout()
            ->createBlock('Mage_Core_Block_Text', 'fiscal');

        $fiscal->setText($html);

        $observer->getEvent()->getTransport()->setHtml(
            $observer->getEvent()->getTransport()->getHtml().$fiscal->toHtml()
        );

        return;
    }

    /**
     * Tied to controller_action_layout_generate_blocks_after event.
     * Adds some JavaScript code logic for handling the system > config > fiskalizacija actions.
     *
     * @param null $observer
     */
    public function injectPPConfigJS($observer = null)
    {
        if ($observer->getEvent()->getAction()->getFullActionName() === 'adminhtml_system_config_edit') {
            $PPConfigJS = Mage::app()->getLayout()
                            ->createBlock('Mage_Core_Block_Text', 'fiscal_PPConfigJS');

            
            
            $PPConfigJS->setText('<!-- START PPConfigJS --><script type="text/javascript">function PPConfigJS_businessUnitReport(){ setLocation(\''.Mage::helper("adminhtml")->getUrl('adminhtml/inchoo_fiskalizacija/businessUnitReport', array('website'=>$observer->getEvent()->getAction()->getRequest()->getParam('website'))).'\'); } function PPConfigJS_businessUnitClose(){ setLocation(\''.Mage::helper("adminhtml")->getUrl('adminhtml/inchoo_fiskalizacija/businessUnitClose', array('website'=>$observer->getEvent()->getAction()->getRequest()->getParam('website'))).'\'); }</script><!-- END PPConfigJS -->');

            $observer->getEvent()->getLayout()
                        ->getBlock('js')->insert($PPConfigJS);
        }
    }

    /**
     * Tied to inchoo_fiskalizacija_new_fiscal_year crontab job.
     *
     * Should be triggered once per day, at midnight or minute after midnight.
     * Checks if we stepped into the new year. If we did, then run a initNewFiscalYear.
     */
    public function initNewFiscalYear()
    {
        Mage::helper('inchoo_fiskalizacija')->initNewFiscalYear();
    }

    public function validateCertificate($observer = null)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $creditmemo = $observer->getEvent()->getCreditmemo();

        if ($invoice) { $entity = $invoice; }
        elseif ($creditmemo) { $entity = $creditmemo; }
        else { return; }

        $helper = Mage::helper('inchoo_fiskalizacija');
        $storeId = $entity->getStoreId();

        if ($helper->isModuleEnabled($storeId) == false) {
            return;
        }

        $websiteId = Mage::getModel('core/store')
                        ->load($storeId)
                        ->getWebsiteId();

        $certificate = Mage::getModel('inchoo_fiskalizacija/cert')
                            ->load($websiteId, 'website_id');

        $validation = $certificate->validate();

        if ((!$certificate) || ($validation !== true)) {
            throw new Mage_Core_Exception(implode(' ', $validation));
        }
    }
}
