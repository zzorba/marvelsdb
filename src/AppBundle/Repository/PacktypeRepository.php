<?php 

namespace AppBundle\Repository;

class PacktypeRepository extends TranslatableRepository
{
  function __construct($entityManager)
  {
    parent::__construct($entityManager, $entityManager->getClassMetadata('AppBundle\Entity\Packtype'));
  }

  public function findAll()
  {
    $qb = $this->createQueryBuilder('b')
      ->select('b')
      ->orderBy('b.name', 'ASC');

    return $this->getResult($qb);
  }
}