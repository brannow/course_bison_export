<?php
namespace CPSIT\CourseBisonExport\Component\PreProcessor;

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
use CPSIT\IhkofEvents\Domain\Model\Contact;
use CPSIT\T3eventsCourse\Domain\Model\Certificate;
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use DWenzel\T3events\Domain\Model\Audience;
use DWenzel\T3events\Domain\Model\Genre;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class PerformanceToBisonArray
 * Maps Performance objects to an array which can
 * be processed to valid Bison XML
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class PerformanceToBisonArray
    extends AbstractPreProcessor
    implements PreProcessorInterface
{

    const DATETIME_FORMAT = 'Y-m-d H:i:s';

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

        $performanceArray['id'] = $this->getEntityValueFromPath($performance, 'uid', 0);
        $performanceArray['seminarprovider'] = $this->getConfigurationValue($configuration, 'seminarprovider', '');

        // what does it mean?
        $performanceArray['contingent'] = '';

        // what does it mean? interpreted as startDate
        $performanceArray['timeJournal'] = '';

        // ENUM (no,yes) - veröffentlichen
        $performanceArray['bPublished'] = 'no';
        // ENUM (no,yes)
        $performanceArray['bPlanned'] = 'no';
        // ENUM (no,yes)
        $performanceArray['bChecked'] = 'no';
        // ENUM (no,yes) - Zulassung nach SGB III §85 für Bildungsgutscheine?
        $performanceArray['bVoucher'] = 'no';
        // ENUM (no,yes) - Nach Veröffentlichung stehen lassen
        $performanceArray['bKeep'] = 'no';

        // Kursname - VARCHAR (255)
        $performanceArray['name'] = substr($this->getEntityValueFromPath($performance, 'event.headline', ''), 0 ,255);

        $performanceArray['aim'] = $this->getEntityValueFromPath($performance, 'event.goals', '');
        // Inhaltsbeschreibung des Kurses
        $performanceArray['description'] = $this->getEntityValueFromPath($performance, 'event.description', '');

        // duration - as varchar (100)
        $performanceArray['duration'] = substr($this->getEntityValueFromPath($performance, 'duration', ''), 0 ,100);

        $now = new \DateTime();
        $startDate = $this->getEntityValueFromPath($performance, 'date', $now);
        $endDate = $this->getEntityValueFromPath($performance, 'endDate', $now);

        $performanceArray['timeDescription'] = $this->getEntityValueFromPath($performance, 'classTime', '');
        $performanceArray['timeStart'] = $startDate->format(static::DATETIME_FORMAT);
        $performanceArray['timeEnd'] = $endDate->format(static::DATETIME_FORMAT);

        $performanceArray['participantMax'] = $this->getEntityValueFromPath($performance, 'places', 0);

        $performanceArray['price'] = number_format($this->getEntityValueFromPath($performance, 'price', 0.0), 2, ',', '.'). '€';

        // what does it mean?
        $performanceArray['providerNumber'] = '';

        $performanceArray['placeName'] = $this->getEntityValueFromPath($performance, 'eventLocation.name', '');
        $performanceArray['placeAdd'] = $this->getEntityValueFromPath($performance, 'eventLocation.details', '');
        $performanceArray['placeStreet'] = $this->getEntityValueFromPath($performance, 'eventLocation.address', '');
        $performanceArray['placePostoffice'] = $this->getEntityValueFromPath($performance, 'eventLocation.zip', '');
        $performanceArray['placeCity'] = $this->getEntityValueFromPath($performance, 'eventLocation.place', '');

        $certs = $this->getEntityValueFromPath($performance, 'event.certificate', []);
        if (!empty($certs)) {
            /** @var Certificate $cert */
            $cert = $certs[0];
            $performanceArray['completion'] = $cert->getTitle();
        }

        $contacts = $this->getEntityValueFromPath($performance, 'event.contactPersons', []);
        if (!empty($contacts)) {
            /** @var Contact $contact */
            $contact = $contacts[0];
            $performanceArray['contactName'] = $contact->getFullName();
        }

        $performanceArray['meta'] = $this->getEntityValueFromPath($performance, 'event.keywords', '');

        $performanceArray['semGroups'] = $this->getSeminarGroups($performance, $configuration);

        $performanceArray['semTopics'] = $this->getSeminarTopic($performance, $configuration);

        return $performanceArray;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getSeminarGroups($performance, $configuration)
    {
        $seminarGroups = [];

        $audiences = $this->getEntityValueFromPath($performance, 'event.audience', []);

        /** @var Audience $audience */
        foreach ($audiences as $audience) {
            $seminarGroups[] = [
                'semGroup' => [
                    'text' => $audience->getTitle()
                ]
            ];
        }

        return $seminarGroups;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getSeminarTopic($performance, $configuration)
    {
        $seminarTopics = [];

        $genres = $this->getEntityValueFromPath($performance, 'event.genre', []);

        /** @var Genre $genre */
        foreach ($genres as $genre) {
            $seminarTopics[] = [
                'semTopic' => [
                    'text' => $genre->getTitle()
                ]
            ];
        }

        return $seminarTopics;
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

        if ($value instanceof LazyObjectStorage) {
            return $value->toArray();
        } elseif ($value instanceof LazyLoadingProxy) {
            $value = $value->_loadRealInstance();
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
