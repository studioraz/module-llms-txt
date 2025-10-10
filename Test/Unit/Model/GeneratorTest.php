<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MageOS\LlmTxt\Model\Config;
use MageOS\LlmTxt\Model\Generator;

final class GeneratorTest extends TestCase
{
    private Generator $generator;
    private Config|MockObject $config;
    private StoreManagerInterface|MockObject $storeManager;
    private StoreInterface|MockObject $store;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->store = $this->createMock(StoreInterface::class);

        $this->generator = new Generator(
            $this->config,
            $this->storeManager
        );
    }

    public function test_generate_returns_manual_content_when_enabled(): void
    {
        $storeId = 1;
        $manualContent = "# Manual Store\n> Custom content";

        $this->store->method('getId')->willReturn($storeId);
        $this->storeManager->method('getStore')->willReturn($this->store);

        $this->config->expects($this->once())
            ->method('shouldUseManualContent')
            ->with($storeId)
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('getManualContent')
            ->with($storeId)
            ->willReturn($manualContent);

        $result = $this->generator->generate($storeId);

        $this->assertSame($manualContent, $result);
    }

    public function test_generate_returns_generated_content_when_available(): void
    {
        $storeId = 1;
        $generatedContent = "# AI Store\n> AI generated content";

        $this->store->method('getId')->willReturn($storeId);
        $this->storeManager->method('getStore')->willReturn($this->store);

        $this->config->method('shouldUseManualContent')
            ->with($storeId)
            ->willReturn(false);

        $this->config->expects($this->once())
            ->method('getGeneratedContent')
            ->with($storeId)
            ->willReturn($generatedContent);

        $result = $this->generator->generate($storeId);

        $this->assertSame($generatedContent, $result);
    }

    public function test_generate_returns_fallback_when_no_content(): void
    {
        $storeId = 1;
        $storeName = 'Test Store';

        $this->store->method('getId')->willReturn($storeId);
        $this->store->method('getName')->willReturn($storeName);
        $this->storeManager->method('getStore')->willReturn($this->store);

        $this->config->method('shouldUseManualContent')->willReturn(false);
        $this->config->method('getGeneratedContent')->willReturn('');
        $this->config->method('getSiteName')->willReturn('');
        $this->config->method('getSiteDescription')->willReturn('');

        $result = $this->generator->generate($storeId);

        $this->assertStringContainsString('# ' . $storeName, $result);
        $this->assertStringContainsString('Generate with AI', $result);
    }

    public function test_estimate_token_count_returns_reasonable_estimate(): void
    {
        $content = 'This is a test content with multiple words to count tokens properly.';

        $result = $this->generator->estimateTokenCount($content);

        // 12 words * 1.3 ≈ 15-16 tokens
        $this->assertGreaterThan(10, $result);
        $this->assertLessThan(20, $result);
    }

    public function test_estimate_token_count_handles_empty_content(): void
    {
        $result = $this->generator->estimateTokenCount('');

        $this->assertSame(0, $result);
    }

    public function test_generate_uses_current_store_when_no_store_id(): void
    {
        $currentStoreId = 3;

        $this->store->method('getId')->willReturn($currentStoreId);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);

        $this->config->method('shouldUseManualContent')
            ->with($currentStoreId)
            ->willReturn(false);

        $this->config->method('getGeneratedContent')
            ->with($currentStoreId)
            ->willReturn('Test content');

        $this->generator->generate();
    }
}
