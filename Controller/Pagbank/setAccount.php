<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 *
 * @SuppressWarnings(PHPMD)
 */

namespace PagBank\SplitWebkulMagento\Controller\Pagbank;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Customer\Model\Url as CustomerUrl;
use Webkul\Marketplace\Model\SellerFactory;

/**
 * Webkul Marketplace Account Save Payment Information Controller.
 *
 * Isso é uma cópia do módulo original por isso não há PHPMD válido.
 * @SuppressWarnings(PHPMD)
 */
class SetAccount extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var SellerFactory
     */
    protected $sellerModel;

    /**
     * @param Context                                    $context
     * @param Session                                    $customerSession
     * @param FormKeyValidator                           $formKeyValidator
     * @param Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param HelperData                                 $helper
     * @param CustomerUrl                                $customerUrl
     * @param SellerFactory                              $sellerModel
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        HelperData $helper,
        CustomerUrl $customerUrl,
        SellerFactory $sellerModel
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_date = $date;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl;
        $this->sellerModel = $sellerModel;
        parent::__construct(
            $context
        );
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Seller's SavePaymentInfo action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        if ($this->getRequest()->getParam('account')) {
           
            try {
               
                $pagBankAccount = $this->getRequest()->getParam('account');
                $sellerId = $this->_getSession()->getCustomerId();
                $collection = $this->sellerModel->create()
                              ->getCollection()
                              ->addFieldToFilter('seller_id', $sellerId);
                $autoIds = $collection->getAllIds();
                foreach ($autoIds as $autoId) {
                    $value = $this->sellerModel->create()->load($autoId);
                    $value->setPagBankAccountId($pagBankAccount);
                    $value->setUpdatedAt($this->_date->gmtDate());
                    $value->save();
                }
                // clear cache
                $this->helper->clearCache();
                $this->messageManager->addSuccess(
                    __('Payment information was successfully saved')
                );
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/account',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            } catch (\Exception $e) {

                $this->helper->logDataInLogger(
                    "Controller_Account_SavePaymentInfo execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/account',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                '*/*/account',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
