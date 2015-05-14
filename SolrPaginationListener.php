<?php

namespace Codersmill\ArchiveBundle\Listener;

use Codersmill\ArchiveBundle\Entity\ArchiveRepository;
use Codersmill\ArchiveBundle\Solr\GroupedSolrQuery;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FS\SolrBundle\Query\SolrQuery;
use FS\SolrBundle\Solr;
use Knp\Component\Pager\Event\AfterEvent;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SolrPaginationListener implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->target) && 2 == count($event->target)) {
            $values = array_values($event->target);
            list($client, $query) = $values;

            if ($client instanceof Solr && $query instanceof GroupedSolrQuery) {
                $query->setOptions(['rows' => $event->getLimit(), 'start' => $event->getOffset()]);
                $solrResult = $query->getResult();

                $event->items  = $solrResult;
                $event->count  = $client->getNumFound();
                $event->setCustomPaginationParameter('result', $solrResult);
                $event->stopPropagation();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}

?>