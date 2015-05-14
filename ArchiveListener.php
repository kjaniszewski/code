<?php

namespace Codersmill\ArchiveBundle\Listener;

use Codersmill\ArchiveBundle\Entity\Archive;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PostPersist;
use FS\SolrBundle\Solr;
use FS\SolrBundle\Tests\SolrClientFake;
use Knp\Component\Pager\Event\AfterEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Session\Session;

class ArchiveListener
{
    private $container;

    public function postPersist(Archive $archive)
    {
        $archive->serializeTags();
        $this->container->get('solr.client')->addDocument($archive);
    }

    public function postUpdate(Archive $archive)
    {
        $archive->serializeTags();
        $this->container->get('solr.client')->updateDocument($archive);
    }

    public function preRemove(Archive $archive)
    {
        $this->container->get('solr.client')->removeDocument($archive);
    }

    function __construct($container)
    {
        $this->container = $container;
    }
}

?>