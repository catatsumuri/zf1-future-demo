<?php

class TestController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $adapter = Zend_Db_Table::getDefaultAdapter();

        if (!$adapter instanceof Zend_Db_Adapter_Abstract) {
            $this->view->isConnected = false;
            $this->view->message = 'Database adapter is not configured. Check the DB_* environment variables.';

            return;
        }

        try {
            $version = $adapter->fetchOne('SELECT VERSION()');
            $this->view->isConnected = true;
            $this->view->message = 'Successfully connected to MySQL.';
            $this->view->version = $version;
        } catch (Zend_Db_Exception $exception) {
            $this->view->isConnected = false;
            $this->view->message = 'Database connection failed.';
            $this->view->error = $exception->getMessage();
        }
    }
}
