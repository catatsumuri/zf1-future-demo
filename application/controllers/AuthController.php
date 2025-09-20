<?php
declare(strict_types=1);

class AuthController extends Zend_Controller_Action
{
    private Application_Model_UserRepository $users;

    public function init(): void
    {
        $this->users = new Application_Model_UserRepository();
    }

    public function loginAction(): void
    {
        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            $this->_helper->redirector->gotoSimple('index', 'index');

            return;
        }

        $request = $this->getRequest();
        $this->view->email = '';
        $this->view->error = null;

        if (!$request->isPost()) {
            return;
        }

        $email = (string) $this->_getParam('email', '');
        $password = (string) $this->_getParam('password', '');
        $this->view->email = $email;

        if ($email === '' || $password === '') {
            $this->view->error = 'メールアドレスとパスワードを入力してください。';

            return;
        }

        $user = $this->users->findByEmail($email);

        if ($user !== null && $user->isPasswordValid($password)) {
            $auth->getStorage()->write($user->toIdentity());
            $this->_helper->flashMessenger->addMessage(['success' => 'ログインしました。']);
            $this->_helper->redirector->gotoSimple('index', 'index');

            return;
        }

        $this->view->error = 'メールアドレスまたはパスワードが正しくありません。';
    }

    public function logoutAction(): void
    {
        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            $auth->clearIdentity();
            $this->_helper->flashMessenger->addMessage(['info' => 'ログアウトしました。']);
        }

        $this->_helper->redirector->gotoSimple('login');

        return;
    }
}
