<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Component\Routing\Router;

/**
 * Construct a journal, and save it to the database.
 */
class JournalBuilder
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Router
     */
    private $router;

    /**
     * Set the service logger.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the ORM thing.
     *
     * @param Registry $registry
     */
    public function setManager(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * Set the router.
     *
     * @todo why does the journal builder need a router?
     *
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Fetch a single XML value from a SimpleXMLElement.
     *
     * @param SimpleXMLElement $xml
     * @param string           $xpath
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath)
    {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return (string) $data[0];
        }
        if (count($data) === 0) {
            return;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }

    /**
     * Build and persist a journal from XML.
     *
     * @param SimpleXMLElement $xml
     * @param string           $journal_uuid
     *
     * @return Journal
     */
    public function fromXml(SimpleXMLElement $xml, $journal_uuid)
    {
        $journal = $this->em->getRepository('AppBundle:Journal')->findOneBy(array(
            'uuid' => $journal_uuid,
        ));
        if ($journal === null) {
            $journal = new Journal();
        }
        $journal->setUuid($journal_uuid);
        $journal->setTitle($this->getXmlValue($xml, '//atom:title'));
        $journal->setUrl(html_entity_decode($this->getXmlValue($xml, '//pkp:journal_url'))); // &amp; -> &
        $journal->setEmail($this->getXmlValue($xml, '//atom:email'));
        $journal->setIssn($this->getXmlValue($xml, '//pkp:issn'));
        $journal->setPublisherName($this->getXmlValue($xml, '//pkp:publisherName'));
        $journal->setPublisherUrl(html_entity_decode($this->getXmlValue($xml, '//pkp:publisherUrl'))); // &amp; -> &
        $this->em->persist($journal);
        $this->em->flush($journal);

        return $journal;
    }
}
