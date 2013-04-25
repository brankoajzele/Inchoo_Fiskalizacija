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
class Inchoo_Fiskalizacija_Model_Cert extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('inchoo_fiskalizacija/cert');
    }
    
    public function getPemPrivateKey()
    {
        return Mage::helper('core')->decrypt($this->getData('pem_private_key'));
    }
    
    public function getPemPrivateKeyPassphrase()
    {
        return Mage::helper('core')->decrypt($this->getData('pem_private_key_passphrase'));
    }
    
    public function getPemPublicCert()
    {
        return Mage::helper('core')->decrypt($this->getData('pem_public_cert'));
    }

    public function getPemPublicCertificateData()
    {
        return openssl_x509_parse($this->getPemPublicCert());
    }

    public function getPemPrivateKeyResourceHandle()
    {
        return openssl_pkey_get_private($this->getPemPrivateKey(), $this->getPemPrivateKeyPassphrase());
    }

    /**
     * Checks if certificate is valid. If its valid it will return boolean true,
     * if its invalid it will return array with error messages.
     *
     * @return array|bool
     */
    public function validate()
    {
        $errors = array();

        $startDatedt = strtotime($this->getValidFrom());
        $endDatedt = strtotime($this->getValidTo());
        $usrDatedt = strtotime(date('Y-m-d H:i:s'));

        if (!$this->getId()) {
            $errors[] = Mage::helper('inchoo_fiskalizacija')->__('Certifikat nije pronađen.');
            return $errors;
        }

        if(!($usrDatedt >= $startDatedt && $usrDatedt <= $endDatedt)) {
            $errors[] = Mage::helper('inchoo_fiskalizacija')->__('Period valjanosti certifikata je istekao.');
        }
        
        if (empty($errors)) {
            return true;
        }
        
        return $errors;
    }
}
