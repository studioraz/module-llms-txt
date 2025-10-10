<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OpenAiModel implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'gpt-4o-mini', 'label' => __('GPT-4o Mini (Recommended - Fast & Affordable)')],
            ['value' => 'gpt-4o', 'label' => __('GPT-4o (Latest - Most Capable)')],
            ['value' => 'gpt-4-turbo', 'label' => __('GPT-4 Turbo (Previous Generation)')],
        ];
    }
}
