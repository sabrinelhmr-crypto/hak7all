<?php

namespace App\Repository;

use App\Entity\City;
use App\Entity\Trade;
use App\Entity\WorkerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkerProfile>
 */
class WorkerProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkerProfile::class);
    }

    /**
     * Artisans visibles : e-mail vérifié, consentement au numéro public, compte actif.
     *
     * @return WorkerProfile[]
     */
    public function search(?Trade $trade, ?City $city, bool $onlyAvailable, ?City $preferredCity = null): array
    {
        $qb = $this->createQueryBuilder('wp')
            ->addSelect('u', 'c', 't')
            ->join('wp.user', 'u')
            ->join('u.city', 'c')
            ->join('wp.trade', 't')
            ->andWhere('u.emailVerifiedAt IS NOT NULL')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('wp.phonePublic = true')
            ->andWhere('u.role = :role')
            ->setParameter('role', \App\Entity\User::ROLE_WORKER)
            ->orderBy('wp.isAvailable', 'DESC')
            ->addOrderBy('u.fullName', 'ASC');

        if (null !== $trade) {
            $qb->andWhere('wp.trade = :trade')->setParameter('trade', $trade);
        }

        if (null !== $city) {
            $qb->andWhere('u.city = :city')->setParameter('city', $city);
        }

        if ($onlyAvailable) {
            $qb->andWhere('wp.isAvailable = true');
        }

        $results = $qb->getQuery()->getResult();

        if (null !== $preferredCity) {
            usort($results, static function (WorkerProfile $a, WorkerProfile $b) use ($preferredCity): int {
                if ($a->isAvailable() !== $b->isAvailable()) {
                    return $a->isAvailable() ? -1 : 1;
                }

                $aLocal = $a->getUser()?->getCity()?->getId() === $preferredCity->getId();
                $bLocal = $b->getUser()?->getCity()?->getId() === $preferredCity->getId();

                if ($aLocal !== $bLocal) {
                    return $aLocal ? -1 : 1;
                }

                return strcmp($a->getUser()?->getFullName() ?? '', $b->getUser()?->getFullName() ?? '');
            });
        }

        return $results;
    }

    /**
     * Artisans disponibles du même métier et de la même ville (proposition après 10 min).
     *
     * @return WorkerProfile[]
     */
    public function findAlternatives(Trade $trade, City $city, int $limit = 3): array
    {
        return $this->createQueryBuilder('wp')
            ->addSelect('u', 'c', 't')
            ->join('wp.user', 'u')
            ->join('u.city', 'c')
            ->join('wp.trade', 't')
            ->andWhere('u.emailVerifiedAt IS NOT NULL')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('wp.phonePublic = true')
            ->andWhere('wp.isAvailable = true')
            ->andWhere('wp.trade = :trade')
            ->andWhere('u.city = :city')
            ->setParameter('trade', $trade)
            ->setParameter('city', $city)
            ->orderBy('u.fullName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
