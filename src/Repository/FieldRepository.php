<?php

namespace App\Repository;

use App\Entity\Field;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Field|null find($id, $lockMode = null, $lockVersion = null)
 * @method Field|null findOneBy(array $criteria, array $orderBy = null)
 * @method Field[]    findAll()
 * @method Field[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FieldRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Field::class);
    }

    // /**
    //  * @return Field[] Returns an array of Field objects
    //  */
    /*
      public function findByExampleField($value)
      {
      return $this->createQueryBuilder('f')
      ->andWhere('f.exampleField = :val')
      ->setParameter('val', $value)
      ->orderBy('f.id', 'ASC')
      ->setMaxResults(10)
      ->getQuery()
      ->getResult()
      ;
      }
     */

    /*
      public function findOneBySomeField($value): ?Field
      {
      return $this->createQueryBuilder('f')
      ->andWhere('f.exampleField = :val')
      ->setParameter('val', $value)
      ->getQuery()
      ->getOneOrNullResult()
      ;
      }
     */

    public function isUniqueNumber($number, $yearPlan) {
        //$em = $this->getEntityManager();
        var_dump($number);
        $qb = $this->createQueryBuilder('f')
                ->andWhere('f.number = :number')
                ->setParameter('number', $number)
                ->andWhere('f.yearPlan = :yearPlan')
                ->setParameter('yearPlan', $yearPlan);
        return $qb->getQuery()->getResult();
    }

}
