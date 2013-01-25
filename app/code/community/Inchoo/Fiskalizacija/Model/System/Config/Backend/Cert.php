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
class Inchoo_Fiskalizacija_Model_System_Config_Backend_Cert extends Mage_Adminhtml_Model_System_Config_Backend_File
{   
    protected function _beforeSave()
    {
        $value = $this->getValue();
        
        if ($_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value']) {
            /* Make sure passphrase is assigned. */
            $certificatePass = null;
            if (!isset($_POST['groups'][$this->getGroupId()]['fields']['certificate_pass']['value'])) {
                Mage::throwException('Certificate password cannot be empty.');
                return $this;                
            } else {
                $certificatePass = $_POST['groups'][$this->getGroupId()]['fields']['certificate_pass']['value'];
            }
            
            $pfx = null;
            
            try {
                $file = array();
                $tmpName = $_FILES['groups']['tmp_name'];

                $file['tmp_name'] = $tmpName[$this->getGroupId()]['fields'][$this->getField()]['value'];
                $name = $_FILES['groups']['name'];
                $file['name'] = uniqid().$name[$this->getGroupId()]['fields'][$this->getField()]['value'];

                $uploader = new Mage_Core_Model_File_Uploader($file);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $result = $uploader->save(Mage::getBaseDir('tmp'));                

                /* Grab temporarily uploaded PFX file. */
                $pfx = $result['path'].DS.$result['file'];                
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
                return $this;
            }

            /* By default, if we are unable to parse the certificate, set the message to indicate the error. */
            $this->setValue('Something is wrong. Failed to set the certificate.');

            $tmpPublicCert = Mage::getBaseDir('tmp').DS.uniqid('fiscal', true);
            $tmpPrivateKey = Mage::getBaseDir('tmp').DS.uniqid('fiscal', true);

            $cert = array();
            $pkcs12Read = openssl_pkcs12_read(file_get_contents($pfx), $cert, $certificatePass);

            $chars = Mage_Core_Helper_Data::CHARS_PASSWORD_LOWERS
                . Mage_Core_Helper_Data::CHARS_PASSWORD_UPPERS
                . Mage_Core_Helper_Data::CHARS_PASSWORD_DIGITS;

            /* Generate a new passphrase for private key that we will store in database. */
            $newPrivateKeyPass = Mage::helper('core')->getRandomString(16, $chars);
            
            if ($pkcs12Read) {
                file_put_contents($tmpPublicCert, $cert['cert']);
                openssl_pkey_export_to_file($cert['pkey'], $tmpPrivateKey, $newPrivateKeyPass);
            } else {
                $this->unsValue();
                /* Remove temporarily uploaded PFX file and other generated files. */
                @unlink($pfx);
                @unlink($tmpPublicCert);
                @unlink($tmpPrivateKey);
                
                Mage::throwException('Function openssl_pkcs12_read failure. Most likely reason: Invalid certificate passphrase.');
                return $this;                        
            }

            $data = openssl_x509_parse(file_get_contents($tmpPublicCert));

            /* First lets check if the cert for this website exist, if it does delete it */
            $existingCert = Mage::getModel('inchoo_fiskalizacija/cert');
            $existingCert->load($this->getScopeId(), 'website_id');

            if ($existingCert && $existingCert->getId()) {
                try {
                    $existingCert->delete();
                } catch (Exception $e) {
                    Mage::logException($e);
                }                        
            }                    

            /* Save the new certificate into database, encrypt all sensitive data. */
            $newCert = Mage::getModel('inchoo_fiskalizacija/cert');

            $newCert->setWebsiteId($this->getScopeId());
            $newCert->setPfxCert(Mage::helper('core')->encrypt(file_get_contents($pfx)));
            $newCert->setPemPrivateKey(Mage::helper('core')->encrypt(file_get_contents($tmpPrivateKey)));
            //$newCert->setPemPrivateKeyPassphrase(Mage::helper('core')->encrypt($certificatePass));
            $newCert->setPemPrivateKeyPassphrase(Mage::helper('core')->encrypt($newPrivateKeyPass));
            $newCert->setPemPublicCert(Mage::helper('core')->encrypt(file_get_contents($tmpPublicCert)));
            $newCert->setPemPublicCertName($data['name']);
            $newCert->setPemPublicCertSerialNumber($data['serialNumber']);
            $newCert->setPemPublicCertHash($data['hash']);
            $newCert->setPemPublicCertInfo(serialize($data));
            $newCert->setValidFrom($data['validFrom_time_t']);
            $newCert->setValidTo($data['validTo_time_t']);

            try {
                $newCert->save();

                $validFrom_time_t = Mage::getModel('core/date')->timestamp($data['validFrom_time_t']);
                $validTo_time_t = Mage::getModel('core/date')->timestamp($data['validTo_time_t']);

                $this->setValue(sprintf('Trenutno aktivan certifikat: CertDB# %s, Validity %s - %s, SerialNo %s.',$newCert->getId(), date('d.m.Y H:i:s', $validFrom_time_t), date('d.m.Y H:i:s', $validTo_time_t), $newCert->getPemPublicCertSerialNumber()));                            

                @unlink($pfx);
                @unlink($tmpPublicCert);
                @unlink($tmpPrivateKey);
            } catch (Exception $e) {
                Mage::logException($e);
            }         
            
            /* Remove temporarily uploaded PFX file and other generated files. */
            @unlink($pfx);
            @unlink($tmpPublicCert);
            @unlink($tmpPrivateKey);
            
        } else {
            if (is_array($value) && !empty($value['delete'])) {
                if (isset($value['value'])) {
                    $existingCert = Mage::getModel('inchoo_fiskalizacija/cert');
                    $existingCert->load($this->getScopeId(), 'website_id');
                    
                    if ($existingCert && $existingCert->getId()) {
                        try {
                            $existingCert->delete();
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }                        
                    }
                }
                $this->setValue('');
            } else {
                $this->unsValue();
            }
        }

        return $this;
    } 
    
    protected function _getAllowedExtensions()
    {
        return array('pfx');
    }    
}
