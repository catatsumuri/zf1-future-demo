<?php
declare(strict_types=1);

class ErrorController extends Zend_Controller_Action
{
    public function errorAction(): void
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $errors = $request->getParam('error_handler');

        if ($errors instanceof ArrayObject && isset($errors['exception'])) {
            $this->view->exception = $errors['exception'];
        }

        $this->view->message = 'An error occurred';
    }
}
