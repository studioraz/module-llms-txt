<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Model\OpenAi;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Client
{
    private const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const XML_PATH_API_KEY = 'llmtxt/ai_generation/openai_api_key';
    private const XML_PATH_MODEL = 'llmtxt/ai_generation/openai_model';
    private const TIMEOUT = 30;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function generateLlmsTxt(array $storeData, ?int $storeId = null): string
    {
        $apiKey = $this->getApiKey($storeId);
        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }

        $model = $this->getModel($storeId);
        $prompt = $this->buildPrompt($storeData);

        try {
            $response = $this->httpClient->post(self::API_ENDPOINT, [
                'timeout' => self::TIMEOUT,
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert at creating concise, well-structured llms.txt files that help AI systems understand website content. You follow the llmstxt.org standard precisely.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 2000,
                    'temperature' => 0.7,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (!isset($body['choices'][0]['message']['content'])) {
                throw new \RuntimeException('Invalid response from OpenAI API');
            }

            return trim($body['choices'][0]['message']['content']);

        } catch (GuzzleException $e) {
            $this->logger->error('OpenAI API request failed', [
                'exception' => $e->getMessage(),
                'store_id' => $storeId
            ]);

            if ($e->getCode() === 401) {
                throw new \RuntimeException('Invalid OpenAI API key. Please check your credentials.');
            }

            if ($e->getCode() === 429) {
                throw new \RuntimeException('OpenAI rate limit reached. Please try again in a few moments.');
            }

            throw new \RuntimeException('Failed to generate content: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('OpenAI API request failed', [
                'exception' => $e->getMessage(),
                'store_id' => $storeId
            ]);

            if ($e->getCode() === 401) {
                throw new \RuntimeException('Invalid OpenAI API key. Please check your credentials.');
            }

            if ($e->getCode() === 429) {
                throw new \RuntimeException('OpenAI rate limit reached. Please try again in a few moments.');
            }

            throw new \RuntimeException('Failed to generate content: ' . $e->getMessage());
        }
    }

    private function buildPrompt(array $storeData): string
    {
        $categoriesText = $this->formatCategories($storeData['categories'] ?? []);
        $productsText = $this->formatProducts($storeData['products'] ?? []);
        $pagesText = $this->formatPages($storeData['cms_pages'] ?? []);

        return <<<PROMPT
Create an llms.txt file for this Magento eCommerce store.

The llms.txt format helps AI systems understand website content. Follow this structure:

# Store Name
> Brief compelling description (1-2 sentences)

Optional additional context paragraph

## Section Name
- [Link Title](URL): Brief description (1 sentence)

REQUIREMENTS:
1. Start with store name as H1
2. Include engaging blockquote description
3. Organize content into 2-4 logical H2 sections (e.g., "Categories", "Featured Products", "Customer Resources")
4. Use clear, concise language
5. Keep TOTAL output under 1500 words / 2000 tokens
6. Only include the most important/representative items
7. Make descriptions compelling but brief

STORE DATA:
Store Name: {$storeData['store_name']}
Store URL: {$storeData['store_url']}

Top Categories:
{$categoriesText}

Sample Products:
{$productsText}

Key Pages:
{$pagesText}

Generate ONLY the llms.txt content. No explanations or preamble.
PROMPT;
    }

    private function formatCategories(array $categories): string
    {
        $lines = [];
        foreach ($categories as $category) {
            $desc = mb_substr($category['description'] ?? '', 0, 100);
            $lines[] = "- {$category['name']}: {$desc}";
        }
        return implode("\n", $lines) ?: 'No categories available';
    }

    private function formatProducts(array $products): string
    {
        $lines = [];
        $count = 0;
        foreach ($products as $product) {
            if ($count++ >= 10) break; // Limit to 10 products in prompt
            $desc = mb_substr($product['description'] ?? '', 0, 80);
            $lines[] = "- {$product['name']}: {$desc}";
        }
        return implode("\n", $lines) ?: 'No products available';
    }

    private function formatPages(array $pages): string
    {
        $lines = [];
        foreach ($pages as $page) {
            $lines[] = "- {$page['title']} ({$page['identifier']})";
        }
        return implode("\n", $lines) ?: 'No pages available';
    }

    private function getApiKey(?int $storeId): string
    {
        $encrypted = (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $encrypted ? $this->encryptor->decrypt($encrypted) : '';
    }

    private function getModel(?int $storeId): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MODEL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'gpt-4o-mini';
    }

    private function getBaseUrl(int $storeId): string
    {
        try {
            return (string) $this->storeManager->getStore($storeId)->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }
}
