<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace PagBank\SplitWebkulMagento\Plugin\Block\Adminhtml\Customer\Edit\Tab;

use PagBank\SplitWebkulMagento\Helper\Data as Helper;

/**
 * Isso é uma cópia do módulo original por isso não há PHPMD válido.
 * @SuppressWarnings(PHPMD)
 */
class PagBankPaymentInfo extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Template used.
     */
    public const COMM_TEMPLATE = 'PagBank_SplitWebkulMagento::customer/paymentinfo.phtml';

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Edit
     */
    protected $customerEdit;

    /**
     * @param \Magento\Framework\Registry                       $registry
     * @param \Magento\Backend\Block\Widget\Context             $context
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param Helper                                            $helper
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        Helper $helper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->customerEdit = $customerEdit;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Set template to itself.
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::COMM_TEMPLATE);
        }
    }

    /**
     * Get Payment Info.
     */
    public function getPaymentInfo()
    {
        $paymentInfo = $this->customerEdit->getPaymentMode();
        return $paymentInfo;
    }

    /**
     * Get PagBank Account Id.
     */
    public function getPagBankAccountId()
    {
        $sellerId = $this->getRequest()->getParam('id');
        return $this->helper->getPagBankAccountId($sellerId);
    }
}
