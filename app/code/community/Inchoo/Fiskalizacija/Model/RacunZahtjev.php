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
class Inchoo_Fiskalizacija_Model_RacunZahtjev
{
    private $_helper = null;

    public function __construct()
    {
        $this->_helper = Mage::helper('inchoo_fiskalizacija');
    }

    /**
     * Mind melt :(
     *
     * @param $UriId
     * @param $fiscalInvoice
     * @param $entity
     * @param $store
     * @param bool $storniraj
     * @return DOMDocument
     */
    public function generate($UriId, $fiscalInvoice, $entity, $store, $storniraj = false)
    {
        $grandTotal = floatval($entity->getGrandTotal());
        if ($storniraj) { $grandTotal = -abs($grandTotal); }

        $shippingPDV = $this->_helper->getRacunPdvPorezStopa($store);

        $ns = 'tns';

        $writer = new XMLWriter();
        $writer->openMemory();

        $writer->setIndent(4);
        //if ($storniraj) { $writer->writeComment('STORNO RACUN'); }
        //else { $writer->writeComment('STANDARD RACUN'); }
        $writer->startElementNs($ns, 'RacunZahtjev', 'http://www.apis-it.hr/fin/2012/types/f73');
        $writer->writeAttribute('Id', $UriId);
        $writer->startElementNs($ns, 'Zaglavlje', null);
        $writer->writeElementNs($ns, 'IdPoruke', null, $this->_helper->getUUIDv4());
        $writer->writeElementNs($ns, 'DatumVrijeme', null, Mage::getModel('core/locale')->storeDate($store, null, true)->toString('dd.MM.YYYYTHH:mm:ss'));
        $writer->endElement(); /* #Zaglavlje */
        $writer->startElementNs($ns, 'Racun', null);
        $writer->writeElementNs($ns, 'Oib', null, $this->_helper->getOib($store));
        $writer->writeElementNs($ns, 'USustPdv', null, (($this->_helper->getRacunUSustPdv($store)) ? '1' : '0'));
        $writer->writeElementNs($ns, 'DatVrijeme', null, Mage::getModel('core/locale')->storeDate($store, $entity->getCreatedAt(), true)->toString('dd.MM.YYYYTHH:mm:ss'));
        $writer->writeElementNs($ns, 'OznSlijed', null, 'P'); /* P ili N => P na nivou Poslovnog prostora, N na nivou naplatnog uredaja */
        $writer->startElementNs($ns, 'BrRac', null);
        $writer->writeElementNs($ns, 'BrOznRac', null, $fiscalInvoice->getBrOznRac()); /* $fiscalInvoice->getBrOznRac => set automatically via database trigger function */
        $writer->writeElementNs($ns, 'OznPosPr', null, $this->_helper->getPoslovniProstorOznPoslProstora($store));
        $writer->writeElementNs($ns, 'OznNapUr', null, $this->_helper->getOznNapUr($store));
        $writer->endElement(); /* #BrRac */

        if ($this->_helper->getRacunUSustPdv($store)) {
            $writer->startElementNs($ns, 'Pdv', null);

            $shippingAmount = (float)$entity->getShippingAmount();

            if ($shippingAmount) {

                $ShippingAmountOsnovica = $shippingAmount / (1 + ($shippingPDV/100));
                $ShippingAmountIznos = $shippingAmount - $ShippingAmountOsnovica;/* Iznos Poreza */

                if ($storniraj) {
                    $ShippingAmountIznos = -abs($ShippingAmountIznos);
                    $ShippingAmountOsnovica = -abs($ShippingAmountOsnovica);
                }

                $writer->writeComment('Shipping Amount');
                $writer->startElementNs($ns, 'Porez', null);
                $writer->writeElementNs($ns, 'Stopa', null, number_format($shippingPDV, 2, '.', ''));
                $writer->writeElementNs($ns, 'Osnovica', null, number_format($ShippingAmountOsnovica, 2, '.', ''));
                $writer->writeElementNs($ns, 'Iznos', null, number_format($ShippingAmountIznos, 2, '.', ''));
                $writer->endElement(); /* #Porez */
            }

            $items = $entity->getItemsCollection();

            foreach ($items as $item) {

                if (!((float)$item->getRowTotal())) {
                    continue;
                }

                $lineItemBase = (float)($item->getRowTotal() - $item->getDiscountAmount()); /* Osnovica */
                $lineItemTotal = (float)($lineItemBase + $item->getTaxAmount()); /* Iznos jednog line itema */
                $lineItemTaxRate = round((($lineItemTotal / $lineItemBase) - 1)*100); /* Stopa */
                $lineItemTaxAmountTotal = (float)$item->getTaxAmount(); /* Iznos poreza jednog line itema */

                if ($storniraj) {
                    $lineItemBase = -abs($lineItemBase); /* Osnovica */
                    $lineItemTaxAmountTotal = -abs($lineItemTaxAmountTotal); /* Iznos */
                }

                $writer->writeComment($item->getName());
                $writer->startElementNs($ns, 'Porez', null);
                $writer->writeElementNs($ns, 'Stopa', null, number_format($lineItemTaxRate, 2, '.', ''));
                $writer->writeElementNs($ns, 'Osnovica', null, number_format($lineItemBase, 2, '.', ''));
                $writer->writeElementNs($ns, 'Iznos', null, number_format($lineItemTaxAmountTotal, 2, '.', ''));
                $writer->endElement(); /* #Porez */
            }

            $writer->endElement(); /* #Pdv */
        }

        if ($storniraj) {
            $grandTotal = -abs($grandTotal);
        }

        $writer->writeElementNs($ns, 'IznosUkupno', null, number_format($grandTotal, '2', '.', ''));

        $writer->writeElementNs($ns, 'NacinPlac', null, $this->_helper->getRacunNacinPlac($entity->getOrder()->getPayment()->getMethod(), $store));
        $writer->writeElementNs($ns, 'OibOper', null, $this->_helper->getOib($store));

        if ($storniraj) {
            $writer->writeElementNs($ns, 'ZastKod', null, $this->_helper->getZastKod($fiscalInvoice, $store, $entity, true));
        } else {
            $writer->writeElementNs($ns, 'ZastKod', null, $this->_helper->getZastKod($fiscalInvoice, $store, $entity));
        }

        if ((int)$fiscalInvoice->getTotalRequestAttempts() > 1) {
            $writer->writeElementNs($ns, 'NakDost', null, '1');
        } else {
            $writer->writeElementNs($ns, 'NakDost', null, '0');
        }

        $writer->endElement(); /* #Racun */
        $writer->endElement(); /* #RacunZahtjev */

        $RacunZahtjevDOMDocument = new DOMDocument();
        $RacunZahtjevDOMDocument->loadXML($writer->outputMemory());
        $RacunZahtjevDOMDocument->encoding = 'UTF-8';
        $RacunZahtjevDOMDocument->version = '1.0';

        return $RacunZahtjevDOMDocument;
    }
}
