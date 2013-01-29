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
class Inchoo_Fiskalizacija_Helper_Data extends Mage_Core_Helper_Data 
{
    const CONFIG_XML_PATH_SETTINGS_ACTIVE = 'inchoo_fiskalizacija/settings/active';
    const CONFIG_XML_PATH_SETTINGS_OIB = 'inchoo_fiskalizacija/settings/oib';
    const CONFIG_XML_PATH_SETTINGS_IS_VAT_SYSTEM = 'inchoo_fiskalizacija/settings/is_vat_system';
    const CONFIG_XML_PATH_SETTINGS_BUSINESS_PLACE_MARKING = 'inchoo_fiskalizacija/settings/business_place_marking';
    const CONFIG_XML_PATH_settings_FiskalizacijaSOAPServerEndpoint = 'inchoo_fiskalizacija/settings/FiskalizacijaSOAPServerEndpoint';
    const CONFIG_XML_PATH_settings_certificate_ca_pem = 'inchoo_fiskalizacija/settings/certificate_ca_pem';
    
    const CONFIG_XML_PATH_PoslovniProstor_OznPoslProstora = 'inchoo_fiskalizacija/PoslovniProstor/OznPoslProstora';
    const CONFIG_XML_PATH_PoslovniProstor_Ulica = 'inchoo_fiskalizacija/PoslovniProstor/Ulica';
    const CONFIG_XML_PATH_PoslovniProstor_KucniBroj = 'inchoo_fiskalizacija/PoslovniProstor/KucniBroj';
    const CONFIG_XML_PATH_PoslovniProstor_KucniBrojDodatak = 'inchoo_fiskalizacija/PoslovniProstor/KucniBrojDodatak';
    const CONFIG_XML_PATH_PoslovniProstor_BrojPoste = 'inchoo_fiskalizacija/PoslovniProstor/BrojPoste';
    const CONFIG_XML_PATH_PoslovniProstor_Naselje = 'inchoo_fiskalizacija/PoslovniProstor/Naselje';
    const CONFIG_XML_PATH_PoslovniProstor_Opcina = 'inchoo_fiskalizacija/PoslovniProstor/Opcina';
    const CONFIG_XML_PATH_PoslovniProstor_RadnoVrijeme = 'inchoo_fiskalizacija/PoslovniProstor/RadnoVrijeme';
    const CONFIG_XML_PATH_PoslovniProstor_DatumPocetkaPrimjene = 'inchoo_fiskalizacija/PoslovniProstor/DatumPocetkaPrimjene';
    
    const CONFIG_XML_PATH_Racun_USustPdv = 'inchoo_fiskalizacija/Racun/USustPdv';
    const CONFIG_XML_PATH_Racun_PdvPorezStopa = 'inchoo_fiskalizacija/Racun/PdvPorezStopa';
    const CONFIG_XML_PATH_Racun_NacinPlac = 'inchoo_fiskalizacija/Racun/NacinPlac';
    const CONFIG_XML_PATH_Racun_Blagajnik = 'inchoo_fiskalizacija/Racun/blagajnik';

    public function isModuleEnabled($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_SETTINGS_ACTIVE, $store);         
    }
    
    public function getOib($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_SETTINGS_OIB, $store);        
    }
    
    public function isModuleOutputEnabled($moduleName = null)
    {   
        return parent::isModuleOutputEnabled($moduleName);
    }
    
    public function getPoslovniProstorOznPoslProstora($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_OznPoslProstora, $store);        
    }

    public function getPoslovniProstorBrojPoste($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_BrojPoste, $store);        
    }
    
    public function getPoslovniProstorDatumPocetkaPrimjene($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_DatumPocetkaPrimjene, $store);        
    }
    
    public function getPoslovniProstorKucniBroj($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_KucniBroj, $store);        
    }
    
    public function getPoslovniProstorKucniBrojDodatak($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_KucniBrojDodatak, $store);        
    }
    
    public function getPoslovniProstorNaselje($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_Naselje, $store);        
    }    
    
    public function getPoslovniProstorOpcina($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_Opcina, $store);        
    }    
    
    public function getPoslovniProstorRadnoVrijeme($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_RadnoVrijeme, $store);
    }
    
    public function getPoslovniProstorUlica($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_PoslovniProstor_Ulica, $store);
    }    

    public function getRacunUSustPdv($store = null)
    {
        return (bool)Mage::getStoreConfig(self::CONFIG_XML_PATH_Racun_USustPdv, $store);
    }
    
    public function getRacunPdvPorezStopa($store = null)
    {
        return floatval(Mage::getStoreConfig(self::CONFIG_XML_PATH_Racun_PdvPorezStopa, $store));
    }

    public function getRacunBlagajnik($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_Racun_Blagajnik, $store);
    }

    public function getFiskalizacijaSOAPServerEndpoint($store = null)
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_settings_FiskalizacijaSOAPServerEndpoint, $store);
    }

    public function getRacunNacinPlac($paymentMethodCode, $store = null)
    {
        $mapping = Mage::getStoreConfig(self::CONFIG_XML_PATH_Racun_NacinPlac, $store);
        $mapping = explode("\n", trim($mapping));
        
        $NacinPlacKeys = array('G', 'K', 'C', 'T', 'O');
        $knownPaymentMethods = array();
        $PaymentMethods = array();
        
        foreach ($mapping as $m) {
            $_m = explode('=', trim($m));
            if (in_array($_m[1], $NacinPlacKeys)) {
                $knownPaymentMethods[] = $_m[0];
            }
            $PaymentMethods[$_m[0]] = $_m[1];
        }
        
        if (in_array($paymentMethodCode, $knownPaymentMethods)) {
            return $PaymentMethods[$paymentMethodCode];
        }
        
        return 'O';
    }    
    
    public function getOznNapUr()
    {
        return 1;
    }
    
    public function getZastKod($fiscalInvoice, $store, $invoice = null, $storniraj = false)
    {
        if ($invoice === null) {
            $invoice = Mage::getModel('sales/order_invoice');
            $invoice->load($fiscalInvoice->getInvoiceEntityId());
        }
        
        $cert = Mage::getModel('inchoo_fiskalizacija/cert');
        $cert->load(Mage::getModel('core/store')->load($store)->getWebsiteId(), 'website_id');
        
        $oib = $this->getOib();
        $datumVrijemeIzdavanjaRacuna = date('d.m.Y H:i:s', strtotime($fiscalInvoice->getCreatedAt()));
        $brojcanaOznakaRacuna = $fiscalInvoice->getId();
        $oznakaPoslovnogProstora = $this->getPoslovniProstorOznPoslProstora();
        $oznakaNaplatnogUredaja = $this->getOznNapUr();

        /*
         * Porezna uprava ne provjerava zaštitni kod ali na njezin zahtjev obveznik fiskalizacije,
         * temeljem istih ulaznih parametara, mora kreirati zaštitni kod jednak onome s računa.
         *
         * Dakle, ovaj kod dolje je nepotreban, ne treba slati "-" za storne račune.

        if ($storniraj) {
            $ukupniIznosRacuna = '-'.number_format($invoice->getGrandTotal(), '2', '.', '');
        } else {
            $ukupniIznosRacuna = number_format($invoice->getGrandTotal(), '2', '.', '');
        }

        */

        $ukupniIznosRacuna = number_format($invoice->getGrandTotal(), '2', '.', '');
        
        $medjurezultat = $oib;
        $medjurezultat .= $datumVrijemeIzdavanjaRacuna;
        $medjurezultat .= $brojcanaOznakaRacuna;
        $medjurezultat .= $oznakaPoslovnogProstora;
        $medjurezultat .= $oznakaNaplatnogUredaja;
        $medjurezultat .= $ukupniIznosRacuna;
        
        /* $medjurezultat => something like "7934368740720.12.2012 11:14:101MAGE11567.5" */

        $signature = "";

        $pkeyid = openssl_get_privatekey($cert->getPemPrivateKey(), $cert->getPemPrivateKeyPassphrase());
        
        openssl_sign($medjurezultat, $signature, $pkeyid, OPENSSL_ALGO_SHA1);
        openssl_free_key($pkeyid);

        $zastitniKod = md5($signature);        
        
        return $zastitniKod;
    }

    public function wrapIntoSoapEnvelope(DOMDocument &$XMLRequestDOMDoc, $XMLRequestType)
    {
        $envelope = new DOMDocument();

        $envelope->loadXML('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body></soapenv:Body>
</soapenv:Envelope>');

        $envelope->encoding = 'UTF-8';
        $envelope->version = '1.0';

        $XMLRequestTypeNode = $XMLRequestDOMDoc->getElementsByTagName($XMLRequestType)->item(0);
        $XMLRequestTypeNode = $envelope->importNode($XMLRequestTypeNode, true);

        $envelope->getElementsByTagName('Body')->item(0)->appendChild($XMLRequestTypeNode);

        $XMLRequestDOMDoc = $envelope;
    }

    /**
     * Should be triggered one per day, at midnight or minute after midnight.
     * Checks if we stepped into the new year.
     *
     * If we stepped into the new year then it will try to "truncate" the "inchoo_fiskalizacija_invoice" table
     * and create an archive table under inchoo_fiskalizacija_invoice_[oldYear].
     */
    public function initNewFiscalYear()
    {
        $year = (int)date("Y"); /* grab the current system year */

        $resource = Mage::getSingleton('core/resource');
        $conn = $resource->getConnection('core_write');
        $liveTable = $resource->getTableName('inchoo_fiskalizacija/invoice');

        $error = null;
        $totalInLive = null;
        $totalInArchive = null;

        try {
            $yearInTable = (int)$conn->fetchOne("SELECT YEAR(created_at) FROM {$liveTable};");
            /* New fiscal year has not yet started */
            if (($yearInTable + 1) !== $year) {
                return; /* exit */
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return;
        }

        try {
            $conn->query("CREATE TABLE {$liveTable}_archive_{$yearInTable} LIKE {$liveTable};");
            $conn->query("INSERT INTO {$liveTable}_archive_{$yearInTable} SELECT * FROM {$liveTable};");
            $totalInLive = (int)$conn->fetchOne("SELECT COUNT(entity_id) FROM {$liveTable};");
            $totalInArchive = (int)$conn->fetchOne("SELECT COUNT(entity_id) FROM {$liveTable}_archive_{$yearInTable};");
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        if (($totalInLive === $totalInArchive) && ((int)$totalInArchive) > 0) {
            try {
                $conn->query("TRUNCATE TABLE {$liveTable};");
                $conn->query("ALTER TABLE {$liveTable} AUTO_INCREMENT = 1;");
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error) {
            Mage::log("Unable to switch to new fiscal year. ".$error, null, null, true);
        }
    }

    public function getUUIDv4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function getCertificateCaPem($store = null)
    {
        $cert = Mage::getStoreConfig(self::CONFIG_XML_PATH_settings_certificate_ca_pem, $store);
        return trim($cert);
    }

    public function getCertificateCaPemPath($store = null)
    {
        $path = Mage::getBaseDir('media').DS.'inchoo_fiskalizacija'.DS.'certificate'.DS.'CA'.DS
                    .$this->getCertificateCaPem($store)
                    .Inchoo_Fiskalizacija_Model_System_Config_Backend_Cert_Ca::FILE_SUFFIX;

        return trim($path);
    }

    /**
     * Function taken from Magento eCommerce platform from /app/code/core/Mage/Core/Helper/Data.php file.
     *
     * @param $len
     * @param null $chars
     * @return string
     */
    function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = 'abcdefghijklmnopqrstuvwxyz' . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . '0123456789';
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
}
