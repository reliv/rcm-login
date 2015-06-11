<?php

/**
 * Plugin Controller
 *
 * This is the main controller for this plugin
 *
 * PHP version 5.3
 *
 * LICENSE: No License yet
 *
 * @category  Reliv
 * @author    Rod McNew <rmcnew@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 */
namespace RcmLogin\Controller;

use Rcm\Plugin\PluginInterface;
use Rcm\Plugin\BaseController;
use RcmUser\Service\RcmUserService;
use Zend\Authentication\Result;
use Zend\EventManager\Event;

/**
 * Plugin Controller
 *
 * This is the main controller for this plugin
 *
 * @category  Reliv
 * @author    Rod McNew <rmcnew@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 *
 */
class PluginController extends BaseController implements PluginInterface
{

    /**
     * @var \RcmUser\Service\RcmUserService $rcmUserService
     */
    protected $rcmUserService;

    public function __construct(
        $config,
        RcmUserService $rcmUserService
    ) {
        parent::__construct($config);
        $this->rcmUserService = $rcmUserService;
    }

    public function renderInstance($instanceId, $instanceConfig)
    {
        $postSuccess = false;
        $error = null;
        $username = null;

        if ($this->postIsForThisPlugin()) {
            $user = $this->getUser();

            if (empty($user)) {
                $error = $instanceConfig['translate']['missing'];
            }

            $authResult = $this->rcmUserService->authenticate($user);

            if (!$authResult->isValid()) {
                if ($authResult->getCode() == Result::FAILURE_UNCATEGORIZED
                    && !empty($this->config['rcmPlugin']['RcmLogin']['uncategorizedErrorRedirect'])
                ) {
                    return $this->redirect()
                        ->toUrl($this->config['rcmPlugin']['RcmLogin']['uncategorizedErrorRedirect']);
                }

                $error = $instanceConfig['translate']['invalid'];
            }

            if (!$error) {
                $postSuccess = true;
            }
        }

        if ($postSuccess) {
            $parms = array(
                'request' => $this->getRequest(),
                'response' => $this->getResponse()
            );

            $event = new Event('LoginSuccessEvent', $this, $parms);
            $eventManager = $this->getEventManager();

            $eventManager->trigger($event);

            return $this->getResponse();

        }

        $view = parent::renderInstance(
            $instanceId,
            $instanceConfig
        );

        $view->setVariables(
            [
                'error' => $error,
                'username' => $username,
            ]
        );

        return $view;
    }

    protected function getUser()
    {
        $username = trim(
            filter_var(
                $this->getRequest()->getPost('username'),
                FILTER_SANITIZE_STRING
            )
        );

        $password = filter_var(
            $this->getRequest()->getPost('password'),
            FILTER_SANITIZE_STRING
        );

        if (empty($username) || empty($password)) {
            return null;
        }

        $user = $this->rcmUserService->buildNewUser();
        $user->setUsername($username);
        $user->setPassword($password);

        return $user;
    }
}
