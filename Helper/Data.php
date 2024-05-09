<?php
/**
 * PagBank Webkul PagBank Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\SplitWebkulMagento\Helper;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\Order;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\SplitWebkulMagento\Logger\Logger;
use PagBank\SplitMagento\Model\Config as SplitConfig;
use Webkul\Marketplace\Helper\Payment as WebkulHelperPay;
use Webkul\Marketplace\Model\SellerFactory;

/**
 * Webkul Marketplace Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SplitConfig
     */
    protected $splitConfig;

    /**
     * @var WebkulHelperPay
     */
    protected $webkulHelperPay;

    /**
     * @var SellerFactory
     */
    protected $sellerFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param Context            $context
     * @param HttpContext        $httpContext
     * @param Logger             $logger
     * @param Config             $config
     * @param SplitConfig        $splitConfig
     * @param WebkulHelperPay    $webkulHelperPay
     * @param SellerFactory      $sellerFactory
     * @param CustomerSession    $customerSession
     * @param EventManager       $eventManager
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        Logger $logger,
        Config $config,
        SplitConfig $splitConfig,
        WebkulHelperPay $webkulHelperPay,
        SellerFactory $sellerFactory,
        CustomerSession $customerSession,
        EventManager $eventManager
    ) {
        $this->httpContext = $httpContext;
        $this->logger = $logger;
        $this->config = $config;
        $this->splitConfig = $splitConfig;
        $this->webkulHelperPay = $webkulHelperPay;
        $this->sellerFactory = $sellerFactory;
        $this->customerSession = $customerSession;
        $this->eventManager = $eventManager;
        parent::__construct($context);
    }
    /**
     * Is Enabled
     *
     * @return bool
     */
    public function isEnabled()
    {

        $isEnabled = $this->splitConfig->getAddtionalValue('data_source');
        
        if ($isEnabled === 'custom') {
            return true;
        }

        return false;
    }

    /**
     * Get Recivers Data.
     *
     * @param Order $order
     *
     * @return array
     */
    public function getReciversData($order)
    {
        /**
         * Dispatch event for advance commission module
         */
        $this->eventManager->dispatch('mp_advance_commission_rule', [
            'order' => $order
        ]);

        $commisionsProd = $this->getCommissionForProducts($order);
        $commisionsShip = $this->getCommissionForShipping($order);

        $sellers = array_merge($commisionsProd, $commisionsShip);

        $sellerData = $this->normalizeSellers($sellers);

        return $sellerData;
    }

    /**
     * Normalize Sellers.
     *
     * @param array $sellers
     *
     * @return array
     */
    public function normalizeSellers($sellers)
    {
        $newValues = [];

        foreach ($sellers as $seller) {
            $sellerId = $seller['pag_bank_id'];

            if (array_key_exists($sellerId, $newValues)) {
                $newValues[$sellerId]['pag_bank_amount'] += $seller['pag_bank_amount'];
            }

            if (!array_key_exists($sellerId, $newValues)) {
                $newValues[$sellerId] = [
                    'pag_bank_id'       => $seller['pag_bank_id'],
                    'pag_bank_amount'   => $seller['pag_bank_amount'],
                ];
            }
        }

        return $newValues;
    }

    /**
     * Get Commission for Products
     *
     * @param Order $order
     *
     * @return array
     */
    public function getCommissionForProducts($order)
    {
        $sellerData = [];

        $cartItems = $order->getAllVisibleItems();
        
        foreach ($cartItems as $item) {
            $commissionData = $this->webkulHelperPay->getCommissionData($item);
            $commissionData = $this->updateCommissionData($commissionData);
            $commissionDetail = $commissionData['commissionDetail'];
            $pagBankId = $this->getPagBankAccountId($commissionDetail['id']);
            $itemPrice = $item->getPrice() * $item->getQtyOrdered();
            $reciver = $itemPrice - $commissionData['tempcoms'];

            $tempComs = $this->config->formatPrice($reciver);

            $sellerData[] = [
                'pag_bank_id'     => $pagBankId,
                'pag_bank_amount' => $tempComs,
            ];
        }

        $this->logger->info(json_encode($sellerData));

        return $sellerData;
    }

    /**
     * Get Commission for Shipping
     *
     * @param Order $order
     *
     * @return array
     */
    public function getCommissionForShipping($order)
    {
        $sellerData = [];

        // Óbvio que esse método seria o ideal mas o Webkul retorna shipf como nulo...
        // $shippingData = $this->webkulHelperPay->getShippingData($quote);

        $options = $this->customerSession->getShippingInformation();
        $shipMethod = $order->getShippingMethod();

        $this->logger->info(json_encode($options));

        $pattern = '/mpfrenet_(\d+[A-Z]{0,1})/';

        $isFrenet = preg_match($pattern, $shipMethod, $matches);

        if (!$isFrenet) {
            $this->logger->info(__('Shipping method is not Frenet'));
            return $sellerData;
        }
        
        foreach ($options['mpfrenet'] as $shipQuote) {
            $pagBankId = $this->getPagBankAccountId($shipQuote['seller_id']);
            $tempComs = $this->config->formatPrice($shipQuote['submethod'][$matches[1]]['cost']);
            $sellerData[] = [
                'pag_bank_id'     =>  $pagBankId,
                'pag_bank_amount' =>  $tempComs,
            ];
        }

        $this->logger->info(json_encode($sellerData));

        return $sellerData;
    }

    /**
     * Get PagBank Account Id.
     *
     * @param int $sellerId
     *
     * @return string
     */
    public function getPagBankAccountId($sellerId)
    {
        $collection = $this->sellerFactory->create()
                ->getCollection()
                ->addFieldToFilter('seller_id', $sellerId)->getFirstItem();

        return $collection->getPagBankAccountId();
    }

    /**
     * Update Commission Data.
     *
     * @param array $commissionData
     *
     * @return array
     */
    public function updateCommissionData($commissionData)
    {
        try {
            $tempcoms = $commissionData['tempcoms'];
            $commissionDetail = $commissionData['commissionDetail'];
            if (!$tempcoms) {
                $commissionDetail = $this->webkulHelperPay->getSellerDetail($commissionData['product_id']);

                if ($commissionDetail['id'] !== 0
                    && $commissionDetail['commission'] !== 0
                ) {
                    $tempcoms = round(
                        ($commissionData['row_total'] * $commissionDetail['commission']) / 100,
                        2
                    );
                }
            }
            return [
                'tempcoms' => $tempcoms,
                'commissionDetail' => $commissionDetail
            ];
        } catch (\Exception $e) {
            return $commissionData;
        }
    }

    /**
     * Get App Url.
     *
     * @param string $state
     *
     * @return string
     */
    public function getAppUrl($state)
    {
        $environment = $this->config->getEnvironmentMode();
        
        $urlConnect = Config::ENDPOINT_CONNECT_PRODUCTION;
        $appId = $this->config->getAddtionalValue('app_client_id_prod');
        $appRedirectUri = $this->config->getAddtionalValue('app_redirect_uri_prod');

        if ($environment === 'sandbox') {
            $urlConnect = Config::ENDPOINT_CONNECT_SANDBOX;
            $appId = $this->config->getAddtionalValue('app_client_id_sandbox');
            $appRedirectUri = $this->config->getAddtionalValue('app_redirect_uri_sandbox');
        }

        $params = [
            'response_type' => 'code',
            'client_id'     => $appId,
            'redirect_uri'  => $appRedirectUri,
            'scope'         => 'accounts.read',
            'state'         => $state,
        ];

        $link = $urlConnect.'?'.http_build_query($params, '&');

        return $link;
    }

    /**
     * Get Environment.
     *
     * @return string
     */
    public function getEnvironmentMode()
    {
        return $this->config->getEnvironmentMode();
    }
}
