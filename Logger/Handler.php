<?php
/**
 * PagBank Webkul PagBank Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\SplitWebkulMagento\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * Logging level for custom logger.
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * Custom File name.
     *
     * @var string
     */
    protected $fileName = '/var/log/webkul_pag_bank.log';
}
