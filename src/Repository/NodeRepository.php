<?php

namespace App\Repository;

use App\Entity\Node;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Node>
 *
 * @method Node|null find($id, $lockMode = null, $lockVersion = null)
 * @method Node|null findOneBy(array $criteria, array $orderBy = null)
 * @method Node[]    findAll()
 * @method Node[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Node::class);
    }
    
    public function findByTag($tag, $limit = null, $offset = null): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.tags', 't')
            ->andWhere('t.label = :tag')
            ->setParameter('tag', $tag)
            ->orderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function findByCategory($category, $limit = null, $offset = null): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.category', 'c')
            ->andWhere('c.label = :category')
            ->setParameter('category', $category)
            ->orderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function findByRegion($region, $locale, $limit = null, $offset = null): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.regions', 'r')
            ->leftJoin('n.language', 'l')
            ->andWhere('r.id = :region')
            ->andWhere('l.locale = :locale OR l is null')
            ->setParameter('region', $region)
            ->setParameter('locale', $locale)
            ->orderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function findByRegionLabel($label, $locale, $limit = null, $offset = null): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.regions', 'r')
            ->leftJoin('n.language', 'l')
            ->andWhere('r.label = :label')
            ->andWhere('l.locale = :locale OR l is null')
            ->setParameter('label', $label)
            ->setParameter('locale', $locale)
            ->orderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findPrev(Node $node): ?Node
    {
        return $this->createQueryBuilder('n')
                    ->andWhere('n.id < :node')
                    ->setParameter('node', $node)
                    ->orderBy('n.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult()
            ;
    }

    public function findNext(Node $node): ?Node
    {
        return $this->createQueryBuilder('n')
                    ->andWhere('n.id > :node')
                    ->setParameter('node', $node)
                    ->orderBy('n.id', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult()
            ;
    }

//    /**
//     * @return Node[] Returns an array of Node objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Node
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
