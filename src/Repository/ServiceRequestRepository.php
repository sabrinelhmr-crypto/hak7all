<?php

namespace App\Repository;

use App\Entity\ServiceRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceRequest>
 */
class ServiceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceRequest::class);
    }

    /** @return ServiceRequest[] */
    public function findForClient(User $client): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('w', 'wp', 't')
            ->join('r.worker', 'w')
            ->leftJoin('w.workerProfile', 'wp')
            ->leftJoin('wp.trade', 't')
            ->andWhere('r.client = :client')
            ->setParameter('client', $client)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return ServiceRequest[] */
    public function findForWorker(User $worker): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c')
            ->join('r.client', 'c')
            ->andWhere('r.worker = :worker')
            ->setParameter('worker', $worker)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPendingForWorker(User $worker): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.worker = :worker')
            ->andWhere('r.status = :status')
            ->setParameter('worker', $worker)
            ->setParameter('status', ServiceRequest::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUrgentSince(User $client, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.client = :client')
            ->andWhere('r.isUrgent = true')
            ->andWhere('r.createdAt >= :since')
            ->setParameter('client', $client)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return ServiceRequest[] */
    public function findUnseenUrgentForWorker(User $worker): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c')
            ->join('r.client', 'c')
            ->andWhere('r.worker = :worker')
            ->andWhere('r.isUrgent = true')
            ->andWhere('r.seenAt IS NULL')
            ->andWhere('r.status = :status')
            ->setParameter('worker', $worker)
            ->setParameter('status', ServiceRequest::STATUS_PENDING)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return ServiceRequest[] */
    public function findAwaitingClientNotice(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isUrgent = true')
            ->andWhere('r.status = :status')
            ->andWhere('r.createdAt <= :tenMinutesAgo')
            ->setParameter('status', ServiceRequest::STATUS_PENDING)
            ->setParameter('tenMinutesAgo', (new \DateTimeImmutable())->modify('-10 minutes'))
            ->getQuery()
            ->getResult();
    }
}
