<?php

namespace AppBundle\Repository;

/**
 * DriveRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DriveRepository extends \Doctrine\ORM\EntityRepository
{
    public function getParentItems()
    {
        $em = $this->getEntityManager();
        // $query  = $em->createQuery("SELECT partial d.{id, icon, title, dateCreated, linkDetails, star, deleted, hasChildren, children}
        //                         FROM AppBundle:Drive d 
        //                         WHERE d.parent=1");
        // $parentItems = $query->getArrayResult();
        // return $parentItems;
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('d')
                     ->from('AppBundle:Drive', 'd')
                     ->where('d.parent=1');
        $parentItems = $queryBuilder->getQuery();
        return $parentItems->getArrayResult();
    }

    public function getItem($itemId)
    {
        $em = $this->getEntityManager();
        
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('d')
                     ->from('AppBundle:Drive', 'd')
                     ->where('d.id= :itemId')
                     ->setParameters(array('itemId'=>$itemId));
        $item = $queryBuilder->getQuery();
        return $item->getArrayResult();
    }

    public function getFileDirectory($parentItems)
    {
        print_r($parentItems);
        $parent = array();
        foreach ($parentItems as $key => $value)
        {
            $children = array();
            // var_dump($fileDirectory[$key]['children']);
            if ($parentItems[$key]['hasChildren']===true) {
                foreach ($parentItems[$key]['children'] as $child) {
                    // var_dump($child);
                    $newChild = $this->getItem($child);
                    if ($newChild[0]['hasChildren']===true) {
                        foreach ($newChild[0]['children'] as $child) {
                            $newItem = $this->getItem($child);
                            // print_r($newChild[0]['children']);
                            \array_push($newChild[0]['children'] , $newItem);
                        }
                        $this->getFileDirectory($newChild[0]['children']);
                    }else{
                        \array_push($children, $newChild[0]);
                    }
                    
                }
                $parentItems[$key]['children'] = $children;

            }
        }
        return $parentItems;
    }
}
