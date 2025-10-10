<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MageOS\LlmTxt\Model\Config;

final class ConfigTest extends TestCase
{
    private Config $config;
    private ScopeConfigInterface|MockObject $scopeConfig;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function test_is_enabled_returns_boolean(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'llmtxt/general/enabled',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(true);

        $result = $this->config->isEnabled();

        $this->assertTrue($result);
    }

    public function test_get_cache_lifetime_returns_integer(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                'llmtxt/general/cache_lifetime',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('86400');

        $result = $this->config->getCacheLifetime();

        $this->assertSame(86400, $result);
    }

    public function test_get_site_name_returns_string(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                'llmtxt/general/site_name',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('Test Store');

        $result = $this->config->getSiteName();

        $this->assertSame('Test Store', $result);
    }

    public function test_get_generated_content_returns_string(): void
    {
        $content = "# Test\n> Description";

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                'llmtxt/content/generated_content',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($content);

        $result = $this->config->getGeneratedContent();

        $this->assertSame($content, $result);
    }

    public function test_should_use_manual_content_returns_boolean(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'llmtxt/content/use_manual_content',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(false);

        $result = $this->config->shouldUseManualContent();

        $this->assertFalse($result);
    }

    public function test_get_openai_model_returns_default_when_empty(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                'llmtxt/ai_generation/openai_model',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('');

        $result = $this->config->getOpenAiModel();

        $this->assertSame('gpt-4o-mini', $result);
    }

    public function test_get_openai_model_returns_configured_value(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                'llmtxt/ai_generation/openai_model',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('gpt-4o');

        $result = $this->config->getOpenAiModel();

        $this->assertSame('gpt-4o', $result);
    }

    public function test_config_respects_store_scope(): void
    {
        $storeId = 5;

        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'llmtxt/general/enabled',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(true);

        $result = $this->config->isEnabled($storeId);

        $this->assertTrue($result);
    }
}
