<?php
declare(strict_types=1);

namespace Amasty\ExportCore\Export\Action\Export\PostFilter;

use Amasty\ExportCore\Api\ActionInterface;
use Amasty\ExportCore\Api\Config\Profile\FieldsConfigInterface;
use Amasty\ExportCore\Api\ExportProcessInterface;
use Amasty\ExportCore\Export\Filter\EntityFiltersProvider;

class FilteringAction implements ActionInterface
{
    /**
     * @var EntityFiltersProvider
     */
    private $entityFiltersProvider;

    public function __construct(
        EntityFiltersProvider $entityFiltersProvider
    ) {
        $this->entityFiltersProvider = $entityFiltersProvider;
    }

    //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
    public function initialize(ExportProcessInterface $exportProcess)
    {
    }

    public function execute(ExportProcessInterface $exportProcess)
    {
        $fieldsConfig = $exportProcess->getProfileConfig()->getFieldsConfig();
        $mainEntityCode = $exportProcess->getProfileConfig()->getEntityCode();

        $data = $exportProcess->getData();
        $this->applyFilters($data, $fieldsConfig, $mainEntityCode);
        $exportProcess->setData($data);
    }

    private function applyFilters(
        array &$data,
        FieldsConfigInterface $fieldsConfig,
        string $entityCode = ''
    ): void {
        if (!$entityCode) {
            $entityCode = $fieldsConfig->getName();
        }
        if (!$entityCode) {
            return;
        }

        $filters = $this->entityFiltersProvider->get($entityCode, $fieldsConfig, true);
        foreach ($data as $index => &$row) {
            foreach ($filters as $filterConfig) {
                if (!$filterConfig[EntityFiltersProvider::KEY_FILTER_INSTANCE]->applyAfter(
                    $row,
                    $filterConfig[EntityFiltersProvider::KEY_FIELD_FILTER]
                )) {
                    unset($data[$index]);
                    continue 2;
                }
            }

            if ($subEntitiesFieldsConfig = $fieldsConfig->getSubEntitiesFieldsConfig()) {
                foreach ($subEntitiesFieldsConfig as $config) {
                    $subEntityName = $config->getName();
                    if (isset($row[$subEntityName])) {
                        $this->applyFilters($row[$subEntityName], $config);
                        if (empty($row[$subEntityName]) && $config->isExcludeRowIfNoResultsFound()) {
                            unset($data[$index]);
                            continue 2;
                        }
                    }
                }
            }
        }
    }
}
