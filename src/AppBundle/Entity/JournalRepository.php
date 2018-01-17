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

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Func;

/**
 * JournalRepository.
 *
 * This class adds a simple journal search and a few other easy queries.
 */
class JournalRepository extends EntityRepository
{
    /**
     * Search for a journal by title, uuid, issn, url, email, or publisher.
     *
     * @param string $q
     *
     * @return Collection|Journal[]
     */
    public function search($q)
    {
        $qb = $this->createQueryBuilder('j');
        $qb->where('CONCAT(j.title, j.uuid, j.issn, j.url, j.email, j.publisherName) LIKE :q');
        $qb->setParameter('q', '%' . $q . '%');
        $query = $qb->getQuery();
        $journals = $query->getResult();

        return $journals;
    }

    /**
     * Find journals by status.
     *
     * @param string $status
     *
     * @return Collection|Journal[]
     */
    public function findByStatus($status)
    {
        return $this->findBy(array(
            'status' => $status,
        ));
    }

    /**
     * Summarize the journal statuses, counting them by status.
     *
     * @return array
     */
    public function statusSummary()
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.status, count(e) as ct')
            ->groupBy('e.status')
            ->orderBy('e.status');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find journals that haven't contacted the PLN in $days.
     *
     * @param int $days
     *
     * @return Collection|Journal[]
     */
    public function findSilent($days)
    {
        $dt = new DateTime("-{$days} day");

        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.contacted < :dt');
        $qb->setParameter('dt', $dt);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find journals that have gone silent and that notifications have been sent
     * for, but they have not been updated yet.
     *
     * @param int $days
     *
     * @return Collection|Journal[]
     */
    public function findOverdue($days)
    {
        $dt = new DateTime("-{$days} day");
        $qb = $this->createQueryBuilder('e');
        $qb->Where('e.notified < :dt');
        $qb->setParameter('dt', $dt);

        return $qb->getQUery()->getResult();
    }

    /**
     * @todo This method should be called findRecent(). It does not find
     * journals with status=new
     *
     * @param type $limit
     *
     * @return Collection|Journal[]
     */
    public function findNew($limit = 5)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy('e.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
