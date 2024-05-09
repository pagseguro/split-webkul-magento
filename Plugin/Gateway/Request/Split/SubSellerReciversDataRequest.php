<?php
/**
 * PagBank Webkul PagBank Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\SplitWebkulMagento\Plugin\Gateway\Request\Split;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PagBank\SplitMagento\Gateway\Request\Split\BaseDataRequest;
use PagBank\SplitMagento\Gateway\Request\Split\ReciversDataRequest;
use PagBank\SplitWebkulMagento\Helper\Data as Helper;

/**
 * Class SubSeller Recivers Data Request - Payment structure for sub-seller.
 */
class SubSellerReciversDataRequest
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Build.
     *
     * @param ReciversDataRequest $subject
     * @param callable            $proceed
     * @param array               $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetSecondarys(ReciversDataRequest $subject, callable $proceed, array $buildSubject)
    {
        $isEnabled = $this->helper->isEnabled();

        if (!$isEnabled) {
            return $proceed($buildSubject);
        }

        $secondary = [];

        /** @var PaymentDataObject $paymentDO **/
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var InfoInterface $payment **/
        $payment = $paymentDO->getPayment();

        /** @var Order $order **/
        $order = $payment->getOrder();

        $sellersData = $this->helper->getReciversData($order);

        foreach ($sellersData as $sellerData) {
            $secondary[] = [
                BaseDataRequest::RECEIVER_ACCOUNT => [
                    BaseDataRequest::RECEIVER_ACCOUNT_ID => $sellerData['pag_bank_id'],
                ],
                BaseDataRequest::RECEIVER_AMOUNT => [
                    BaseDataRequest::RECEIVER_AMOUNT_VALUE => $sellerData['pag_bank_amount'],
                ],
            ];
        }
        
        return $secondary;
    }
}
