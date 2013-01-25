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
class Inchoo_Fiskalizacija_Model_PoslovniProstorZahtjev
{
    private $_helper = null;

    public function __construct()
    {
        $this->_helper = Mage::helper('inchoo_fiskalizacija');
    }

    public function generate($UriId, $store, $zatvaranje = false)
    {
        $ns = 'tns';

        $writer = new XMLWriter();
        $writer->openMemory();

        $writer->setIndent(4);

        $writer->startElementNs($ns, 'PoslovniProstorZahtjev', 'http://www.apis-it.hr/fin/2012/types/f73');
        $writer->writeAttribute('Id', $UriId);
        $writer->startElementNs($ns, 'Zaglavlje', null);
        $writer->writeElementNs($ns, 'IdPoruke', null, $this->_helper->getUUIDv4());
        $writer->writeElementNs($ns, 'DatumVrijeme', null, Mage::getModel('core/locale')->storeDate($store, null, true)->toString('DD.MM.YYYYTHH:mm:ss'));
        $writer->endElement(); /* #Zaglavlje */
        $writer->startElementNs($ns, 'PoslovniProstor', null);
        $writer->writeElementNs($ns, 'Oib', null, $this->_helper->getOib($store));
        $writer->writeElementNs($ns, 'OznPoslProstora', null, $this->_helper->getPoslovniProstorOznPoslProstora($store));
        $writer->startElementNs($ns, 'AdresniPodatak', null);
        $writer->startElementNs($ns, 'Adresa', null);
        $writer->writeElementNs($ns, 'Ulica', null, $this->_helper->getPoslovniProstorUlica($store));
        $writer->writeElementNs($ns, 'KucniBroj', null, $this->_helper->getPoslovniProstorKucniBroj($store));
        $writer->writeElementNs($ns, 'KucniBrojDodatak', null, $this->_helper->getPoslovniProstorKucniBrojDodatak($store));
        $writer->writeElementNs($ns, 'BrojPoste', null, $this->_helper->getPoslovniProstorBrojPoste($store));
        $writer->writeElementNs($ns, 'Naselje', null, $this->_helper->getPoslovniProstorNaselje($store));
        $writer->writeElementNs($ns, 'Opcina', null, $this->_helper->getPoslovniProstorOpcina($store));
        $writer->endElement(); /* #Adresa */
        $writer->endElement(); /* #AdresniPodatak */
        $writer->writeElementNs($ns, 'RadnoVrijeme', null, $this->_helper->getPoslovniProstorRadnoVrijeme($store));
        $writer->writeElementNs($ns, 'DatumPocetkaPrimjene', null, $this->_helper->getPoslovniProstorDatumPocetkaPrimjene($store));

        if ($zatvaranje) {
            $writer->writeElementNs($ns, 'OznakaZatvaranja', null, 'Z'); /* Samo kada se zatvara poslovni prostor */
        }

        $writer->writeElementNs($ns, 'SpecNamj', null, '79343687407'); /* INCHOO OIB ALWAYS */
        $writer->endElement(); /* #PoslovniProstor */
        $writer->endElement(); /* #PoslovniProstorZahtjev */

        $PoslovniProstorZahtjevDOMDocument = new DOMDocument();
        $PoslovniProstorZahtjevDOMDocument->loadXML($writer->outputMemory());
        $PoslovniProstorZahtjevDOMDocument->encoding = 'UTF-8';
        $PoslovniProstorZahtjevDOMDocument->version = '1.0';

        return $PoslovniProstorZahtjevDOMDocument;
    }
}
