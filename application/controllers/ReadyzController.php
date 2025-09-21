<?php
declare(strict_types=1);

class ReadyzController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $response = $this->getResponse()->setHeader('Content-Type', 'text/plain');

        $adapter = $this->resolveAdapter();

        if (!$adapter instanceof Zend_Db_Adapter_Abstract) {
            $response
                ->setHttpResponseCode(503)
                ->setBody('DB adapter unavailable');

            return;
        }

        try {
            $adapter->getConnection();
            $adapter->query('SELECT 1');
        } catch (Zend_Db_Exception $exception) {
            error_log('Readiness DB check failed: ' . $exception->getMessage());

            $response
                ->setHttpResponseCode(503)
                ->setBody('DB check failed');

            return;
        }

        $response->setBody('READY');
    }

    private function resolveAdapter(): ?Zend_Db_Adapter_Abstract
    {
        if (Zend_Registry::isRegistered('db')) {
            $adapter = Zend_Registry::get('db');

            if ($adapter instanceof Zend_Db_Adapter_Abstract) {
                return $adapter;
            }
        }

        try {
            return Zend_Db_Table::getDefaultAdapter();
        } catch (Zend_Db_Table_Exception $exception) {
            error_log('Default adapter unavailable: ' . $exception->getMessage());

            return null;
        }
    }
}

