<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Block;

use Magento\Framework\View\Element\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MageOS\LlmTxt\Block\Data;
use MageOS\LlmTxt\Model\Config;
use MageOS\LlmTxt\Model\Generator;

final class DataTest extends TestCase
{
    private Data $block;
    private Generator|MockObject $generator;
    private Config|MockObject $config;
    private StoreManagerInterface|MockObject $storeManager;
    private Context|MockObject $context;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->generator = $this->createMock(Generator::class);
        $this->config = $this->createMock(Config::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->block = new Data(
            $this->context,
            $this->generator,
            $this->config,
            $this->storeManager
        );
    }

    public function test_to_html_returns_generated_content_when_enabled(): void
    {
        $storeId = 1;
        $content = "# Test Store\n> Description";

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($storeId);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->with($storeId)
            ->willReturn(true);

        $this->generator->expects($this->once())
            ->method('generate')
            ->with($storeId)
            ->willReturn($content);

        $reflectionMethod = new \ReflectionMethod($this->block, '_toHtml');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($this->block);

        $this->assertSame($content . PHP_EOL, $result);
    }

    public function test_to_html_returns_empty_when_disabled(): void
    {
        $storeId = 1;

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($storeId);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->with($storeId)
            ->willReturn(false);

        $this->generator->expects($this->never())
            ->method('generate');

        $reflectionMethod = new \ReflectionMethod($this->block, '_toHtml');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($this->block);

        $this->assertSame('', $result);
    }

    public function test_to_html_handles_exceptions_gracefully(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->config->method('isEnabled')->willReturn(true);
        $this->generator->method('generate')->willThrowException(new \Exception('Test error'));

        $reflectionMethod = new \ReflectionMethod($this->block, '_toHtml');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($this->block);

        $this->assertSame('', $result);
    }

    public function test_get_identities_returns_cache_tags(): void
    {
        $storeId = 5;

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($storeId);
        $this->storeManager->method('getStore')->willReturn($store);

        $result = $this->block->getIdentities();

        $this->assertIsArray($result);
        $this->assertContains('llmtxt_' . $storeId, $result);
    }
}
