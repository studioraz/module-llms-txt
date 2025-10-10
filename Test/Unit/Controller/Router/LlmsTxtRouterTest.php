<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Controller\Router;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MageOS\LlmTxt\Controller\Router\LlmsTxtRouter;

final class LlmsTxtRouterTest extends TestCase
{
    private LlmsTxtRouter $router;
    private ActionFactory|MockObject $actionFactory;
    private ActionList|MockObject $actionList;
    private ConfigInterface|MockObject $routeConfig;

    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactory::class);
        $this->actionList = $this->createMock(ActionList::class);
        $this->routeConfig = $this->createMock(ConfigInterface::class);

        $this->router = new LlmsTxtRouter(
            $this->actionFactory,
            $this->actionList,
            $this->routeConfig
        );
    }

    public function test_match_returns_null_for_non_llms_txt_path(): void
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPathInfo'])
            ->getMockForAbstractClass();
        $request->method('getPathInfo')->willReturn('/some/other/path');

        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    public function test_match_returns_action_for_llms_txt_path(): void
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPathInfo'])
            ->getMockForAbstractClass();
        $request->method('getPathInfo')->willReturn('/llms.txt');

        $this->routeConfig->expects($this->once())
            ->method('getModulesByFrontName')
            ->with('llmstxt')
            ->willReturn(['MageOS_LlmTxt']);

        $this->actionList->expects($this->once())
            ->method('get')
            ->with('MageOS_LlmTxt', null, 'index', 'index')
            ->willReturn('MageOS\LlmTxt\Controller\Index\Index');

        $action = $this->createMock(ActionInterface::class);
        $this->actionFactory->expects($this->once())
            ->method('create')
            ->with('MageOS\LlmTxt\Controller\Index\Index')
            ->willReturn($action);

        $result = $this->router->match($request);

        $this->assertSame($action, $result);
    }

    public function test_match_handles_trailing_slash(): void
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPathInfo'])
            ->getMockForAbstractClass();
        $request->method('getPathInfo')->willReturn('/llms.txt/');

        $this->routeConfig->method('getModulesByFrontName')->willReturn(['MageOS_LlmTxt']);
        $this->actionList->method('get')->willReturn('MageOS\LlmTxt\Controller\Index\Index');

        $action = $this->createMock(ActionInterface::class);
        $this->actionFactory->method('create')->willReturn($action);

        $result = $this->router->match($request);

        $this->assertInstanceOf(ActionInterface::class, $result);
    }

    public function test_match_returns_null_when_no_modules_configured(): void
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPathInfo'])
            ->getMockForAbstractClass();
        $request->method('getPathInfo')->willReturn('/llms.txt');

        $this->routeConfig->expects($this->once())
            ->method('getModulesByFrontName')
            ->with('llmstxt')
            ->willReturn([]);

        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    public function test_match_is_case_sensitive(): void
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPathInfo'])
            ->getMockForAbstractClass();
        $request->method('getPathInfo')->willReturn('/LLMS.TXT');

        $result = $this->router->match($request);

        $this->assertNull($result);
    }
}
