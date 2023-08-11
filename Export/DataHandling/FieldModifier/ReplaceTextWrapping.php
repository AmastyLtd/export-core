<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Export Core for Magento 2 (System)
 */

namespace Amasty\ExportCore\Export\DataHandling\FieldModifier;

use Amasty\ExportCore\Api\Config\Profile\FieldInterface;
use Amasty\ExportCore\Api\Config\Profile\ModifierInterface;
use Amasty\ExportCore\Api\FieldModifier\FieldModifierInterface;
use Amasty\ExportCore\Export\DataHandling\AbstractModifier;
use Amasty\ExportCore\Export\DataHandling\ModifierProvider;
use Amasty\ExportCore\Export\Utils\Config\ArgumentConverter;

class ReplaceTextWrapping extends AbstractModifier implements FieldModifierInterface
{
    private const DEFAULT_SEPARATOR = ' ';

    /**
     * @var ArgumentConverter
     */
    private $argumentConverter;

    public function __construct(
        $config,
        ArgumentConverter $argumentConverter
    ) {
        parent::__construct($config);
        $this->argumentConverter = $argumentConverter;
    }

    public function transform($value)
    {
        if (!empty($value)) {
            $separator = $this->config['input_value'] ?? '';
            $transformedValue = preg_replace('/[^\S ]+/', $separator, (string)$value);
            $value = is_string($transformedValue) ? $transformedValue : $value;
        }

        return $value;
    }

    public function getGroup(): string
    {
        return ModifierProvider::TEXT_GROUP;
    }

    public function getLabel(): string
    {
        return __('Replace Text Wrapping')->render();
    }

    public function getValue(ModifierInterface $modifier): array
    {
        $modifierData = [];
        foreach ($modifier->getArguments() as $argument) {
            $modifierData['value'][$argument->getName()] = $argument->getValue();
        }
        $modifierData['select_value'] = $modifier->getModifierClass();

        return $modifierData;
    }

    public function prepareArguments(FieldInterface $field, $requestData): array
    {
        $arguments = [];

        if (!empty($requestData['value']['input_value'])) {
            $arguments = $this->argumentConverter->valueToArguments(
                (string)$requestData['value']['input_value'],
                'input_value',
                'string'
            );
        }

        return $arguments;
    }

    public function getJsConfig(): array
    {
        return [
            'component' => 'Amasty_ExportCore/js/fields/modifier',
            'template' => 'Amasty_ExportCore/fields/modifier',
            'childTemplate' => 'Amasty_ExportCore/fields/1input-modifier',
            'childComponent' => 'Amasty_ExportCore/js/fields/modifier-field',
            'additionalData' => ['placeholder' => __('With')->render(), 'default' => self::DEFAULT_SEPARATOR]
        ];
    }
}
