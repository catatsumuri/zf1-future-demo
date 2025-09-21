<?php
declare(strict_types=1);

class HealthzController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->getResponse()
            ->setHeader('Content-Type', 'text/plain')
            ->setBody('OK');
    }
}
