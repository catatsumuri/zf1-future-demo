<?php

class IndexController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->view->message = 'Welcome to Zend Framework 1 Future running on PHP 8.1';
    }
}
