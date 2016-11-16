<?php
namespace CPSIT\CourseDeftisExport\Component\PreProcessor;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Benjamin Rannow <b.rannow@familie-redlich.de>
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class PerformanceToDeftisArray
 * Maps Performance objects to an array which can
 * be processed to valid Deftis XML
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class PerformanceToDeftisArray
    extends AbstractPreProcessor
    implements PreProcessorInterface
{

    /**
     * Tells whether the configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!empty($configuration['class'])) {
            return true;
        }

        return false;
    }

    /**
     * Processes the record
     *
     * @param array $configuration
     * @param \DWenzel\T3events\Domain\Model\Performance $record
     * @return bool
     */
    public function process($configuration, &$record)
    {
        $performance = $record;
        if (!is_a($performance, $configuration['class'])) {
            return false;
        }

        $record = $this->mapPerformanceToArray($performance, $configuration);

        return true;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function mapPerformanceToArray($performance, $configuration)
    {
        $performanceArray = [];

        return $performanceArray;
    }

    /**
     * @param AbstractEntity $entity
     * @param $path
     * @param string $default
     * @return mixed|string|AbstractEntity
     */
    protected function getEntityValueFromPath(AbstractEntity $entity, $path, $default = null)
    {
        $value = ObjectAccess::getPropertyPath($entity, $path);
        if (empty($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @param array $configuration
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function getConfigurationValue($configuration, $key, $default = '')
    {
        if (isset($configuration['fields'])) {
            if (!empty($configuration['fields'][$key])) {

                return $configuration['fields'][$key];
            }
        }

        return $default;
    }

}
