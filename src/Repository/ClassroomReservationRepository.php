<?php

namespace App\Repository;

use App\Entity\ClassroomReservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClassroomReservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClassroomReservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClassroomReservation[]    findAll()
 * @method ClassroomReservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassroomReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassroomReservation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ClassroomReservation $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ClassroomReservation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return ClassroomReservation[] Returns an array of ClassroomReservation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ClassroomReservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
