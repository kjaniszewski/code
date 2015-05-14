<?php

namespace Codersmill\ArchiveBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Codersmill\ArchiveBundle\Entity\Archive;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    public function homepageAction()
    {
        $repository = $this->getDoctrine()->getRepository('CodersmillArchiveBundle:StaticPage');
        $page       = $repository->findOneByLabel('Strona główna');

        return $this->render('CodersmillArchiveBundle:Default:homepage.html.twig', array('page' => $page));
    }


    public function indexAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('CodersmillArchiveBundle:Archive');
        $archives   = $repository->getAllPublicArchives($this->container->get('session')->get('randomIds'), $this->getUser());

        $archive_names = Archive::getArchiveModelNames();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $archives,
            $request->query->get('page', 1),
            10, array('distinct' => false)
        );

        $templateSuffix = $request->get('ajax', 0) == 1 ? '_ajax' : '';

        return $this->render('CodersmillArchiveBundle:Default:index' . $templateSuffix . '.html.twig', array('archives' => $archives, 'pagination' => $pagination, 'archive_names' => $archive_names, 'ajax' => $request->get('ajax', 0)));
    }

    public function showAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('CodersmillArchiveBundle:Archive');
        $request    = $this->container->get('request');

        $archive    = $repository->find($id);
        $latest     = $repository->getLatestArchives();
        $tags       = $archive->getTags();
        $previous   = $repository->findPreviousArchive($archive);
        $next       = $repository->findNextArchive($archive);
        $collection = $repository->getJoinedArchives($archive);

        if (strpos($archive->getScanNumber(), 'IT_') !== false) {
            $additionalInfo = $archive->getAdditionalInfo();
        } else {
            $additionalInfo = $archive->getAdditionalInfoForPrivate();
        }

        $template = 'CodersmillArchiveBundle:Default:show' . ($request->get('ajax', 0) == 1 ? '_ajax' : '') . '.html.twig';

        return $this->render($template, array('collection' => $collection, 'archive' => $archive, 'next' => $next, 'previous' => $previous, 'latests' => $latest, 'archive_tags' => $tags, 'additional_info' => $additionalInfo));
    }

    public function tagsAction(Request $request, $name)
    {
        $repository = $this->getDoctrine()->getRepository('CodersmillArchiveBundle:Tag');

        $tag = $repository->createQueryBuilder('p')
            ->where('p.tag_name = :tagname')
            ->setParameter('tagname', $name)
            ->getQuery()
            ->getOneOrNullResult();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $tag->getArchives(),
            $request->query->get('page', 1),
            10
        );

        $archive_names = Archive::getArchiveModelNames();

        return $this->render('CodersmillArchiveBundle:Default:index.html.twig', array('pagination' => $pagination, 'archive_names' => $archive_names));
    }
}
