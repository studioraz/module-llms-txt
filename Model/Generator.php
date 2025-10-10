<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Generator
{
    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function generate(?int $storeId = null): string
    {
        $storeId = $storeId ?? (int) $this->storeManager->getStore()->getId();

        // Check if using manual content
        if ($this->config->shouldUseManualContent($storeId)) {
            return $this->config->getManualContent($storeId);
        }

        // Otherwise use AI-generated content
        $generatedContent = $this->config->getGeneratedContent($storeId);

        if (!empty($generatedContent)) {
            return $generatedContent;
        }

        // Fallback: Basic content if nothing is generated yet
        return $this->generateFallbackContent($storeId);
    }

    private function generateFallbackContent(int $storeId): string
    {
        $siteName = $this->config->getSiteName($storeId);
        if (empty($siteName)) {
            $siteName = (string) $this->storeManager->getStore($storeId)->getName();
        }

        $siteDescription = $this->config->getSiteDescription($storeId);

        $content = "# {$siteName}\n\n";

        if (!empty($siteDescription)) {
            $content .= "> {$siteDescription}\n\n";
        }

        $content .= "Please configure your LLM.txt content in the admin panel.\n";
        $content .= "Use the 'Generate with AI' button to automatically create content from your store data.";

        return $content;
    }

    public function estimateTokenCount(string $content): int
    {
        // Rough estimation: 1 token ≈ 0.75 words
        $wordCount = str_word_count($content);
        return (int) ceil($wordCount * 1.3);
    }
}
