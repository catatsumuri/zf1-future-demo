<?php
declare(strict_types=1);

class Zend_View_Helper_FlashMessenger extends Zend_View_Helper_Abstract
{
    public function flashMessenger(): Zend_Controller_Action_Helper_FlashMessenger
    {
        return Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
    }
}
