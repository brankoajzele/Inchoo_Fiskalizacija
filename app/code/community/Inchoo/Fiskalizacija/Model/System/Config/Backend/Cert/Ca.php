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
class Inchoo_Fiskalizacija_Model_System_Config_Backend_Cert_Ca extends Mage_Adminhtml_Model_System_Config_Backend_File
{
    const FILE_SUFFIX = '.pem';

    protected function _beforeSave()
    {
        $value = $this->getValue();
        if ($_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value']){

            $uploadDir = $this->_getUploadDir();

            try {
                $file = array();
                $tmpName = $_FILES['groups']['tmp_name'];
                $file['tmp_name'] = $tmpName[$this->getGroupId()]['fields'][$this->getField()]['value'];
                $name = $_FILES['groups']['name'];
                $file['name'] = $name[$this->getGroupId()]['fields'][$this->getField()]['value'];
                $uploader = new Mage_Core_Model_File_Uploader($file);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $uploader->setAllowRenameFiles(true);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);

                /* START Custom Inchoo */
                $certificateCAcer = $result['path'].DS.$result['file'];
                $certificateCAcerContent = file_get_contents($certificateCAcer);

                /* Convert .cer to .pem, cURL uses .pem */
                $certificateCApemContent =  '-----BEGIN CERTIFICATE-----'.PHP_EOL
                    .chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL)
                    .'-----END CERTIFICATE-----'.PHP_EOL;

                $certificateCApem = $certificateCAcer.self::FILE_SUFFIX;
                file_put_contents($certificateCApem, $certificateCApemContent);
                /* END Custom Inchoo */

            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
                return $this;
            }

            $filename = $result['file'];
            if ($filename) {
                if ($this->_addWhetherScopeInfo()) {
                    $filename = $this->_prependScopeInfo($filename);
                }
                $this->setValue($filename);
            }
        } else {
            if (is_array($value) && !empty($value['delete'])) {

                if (isset($value['value'])) {
                    $cpcer = Mage::getBaseDir('media').DS.'inchoo_fiskalizacija'.DS.'certificate'.DS.'CA'.DS.$value['value'];
                    $cppem = $cpcer.self::FILE_SUFFIX;
                    @unlink($cpcer);
                    @unlink($cppem);
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
        return array('cer');
    }
}
