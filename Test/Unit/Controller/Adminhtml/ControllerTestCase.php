<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Wires a backend action context that records redirects and flash messages.
 */
abstract class ControllerTestCase extends TestCase
{
    /** @var array{route: string, params: array}|null */
    protected ?array $redirect = null;

    /** @var array<string, string[]> */
    protected array $messages = ['success' => [], 'error' => []];

    protected HttpResponse $response;

    /**
     * @param array $params
     * @return Context
     */
    protected function context(array $params = []): Context
    {
        $this->redirect = null;
        $this->messages = ['success' => [], 'error' => []];

        $request = $this->createStub(HttpRequest::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key, $default = null) => $params[$key] ?? $default
        );

        $helper = $this->createStub(BackendHelper::class);
        $helper->method('getUrl')->willReturnCallback(function ($route, $routeParams = []) {
            $this->redirect = ['route' => $route, 'params' => $routeParams];
            return 'https://admin.test/' . $route;
        });

        $this->response = $this->createStub(HttpResponse::class);

        $messageManager = $this->createStub(ManagerInterface::class);
        foreach (['success', 'error'] as $type) {
            $messageManager->method('add' . ucfirst($type) . 'Message')->willReturnCallback(
                function ($message) use ($type, $messageManager) {
                    $this->messages[$type][] = (string)$message;
                    return $messageManager;
                }
            );
        }

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResponse')->willReturn($this->response);
        $context->method('getHelper')->willReturn($helper);
        $context->method('getSession')->willReturn($this->createStub(Session::class));
        $context->method('getActionFlag')->willReturn($this->createStub(ActionFlag::class));
        $context->method('getMessageManager')->willReturn($messageManager);

        return $context;
    }

    /**
     * @param array<string, mixed> $values
     * @return ScopeConfigInterface
     */
    protected function scopeConfig(array $values): ScopeConfigInterface
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnCallback(static fn($path) => (bool)($values[$path] ?? false));
        $config->method('getValue')->willReturnCallback(static fn($path) => $values[$path] ?? null);
        return $config;
    }
}
