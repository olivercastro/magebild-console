<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Console
 */
namespace Magebild\Console\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Success extends Base
{
    protected $fileName = '/var/log/magebild/massassignaction.log';
    protected $loggerType = Logger::INFO;
}
