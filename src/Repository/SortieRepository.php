<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 *
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function add(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    public function howManyPeopleAreAtThisOuting()
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            select user_sortie.sortie_id,count(*)
            from user_sortie
            group by user_sortie.sortie_id; 
        ';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
    
        return $resultSet->fetchAllAssociative();
    }
    
    public function whatOutingsIsTheUserRegisteredFor($userId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            select user_sortie.sortie_id
            from user_sortie
            where user_sortie.user_id=:user_id;
        ';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("user_id", $userId);
        $resultSet = $stmt->executeQuery();

        $res = array();
        foreach( $resultSet->fetchAllAssociative() as $r)
            $res[] = $r['sortie_id'];
    
        return $res;
    }
    
    public function removeInscription($sortieId,$userId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            delete from user_sortie
            where user_sortie.user_id=:user_id and user_sortie.sortie_id=:sortie_id;
        ';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("user_id", $userId);
        $stmt->bindValue("sortie_id", $sortieId);
        $resultSet = $stmt->executeQuery();
    
        return $resultSet->fetchAllAssociative();
    }
    
    public function addInscription($sortieId,$userId):void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            insert into user_sortie
            values (:user_id,:sortie_id);
        ';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("user_id", $userId);
        $stmt->bindValue("sortie_id", $sortieId);
        $stmt->executeQuery();
    }
    
    public function publishOuting($sortieId):void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            update sortie
            set etat_id=:etat_id
            where sortie.id=:sortie_id;
        ';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("etat_id","2");
        $stmt->bindValue("sortie_id", $sortieId);
        $stmt->executeQuery();
    }
    
    public function setAnnulationId($sortieId,$annulationId):void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            update sortie
            set annulation_id=:annulation_id,etat_id=:etat_id
            where sortie.id=:sortie_id;
        ';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("annulation_id",$annulationId);
        $stmt->bindValue("sortie_id", $sortieId);
        $stmt->bindValue("etat_id", "6");
        $stmt->executeQuery();
    }

//    /**
//     * @return Sortie[] Returns an array of Sortie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sortie
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
