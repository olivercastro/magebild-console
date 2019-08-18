<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Console
 */
namespace Magebild\Console\Api;

interface AssignmentManagementInterface
{
    public function process($ids = [], $storeIds = null);

    public function setOptions($options);

    public function getErrorMessages();
}
