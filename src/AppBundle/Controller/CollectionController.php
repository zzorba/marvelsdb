<?php

namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller {
    public function packsAction($reloaduser = false) {
        $categories = [];
        //$categories[] = ["label" => "Core", "packs" => []];
        $owned_packs = $this->getUser()->getOwnedPacks();
        $oPacks = [];
        if ($owned_packs) {
            $oPacks = explode(',', $owned_packs);
        }
        $list_packs = $this->getDoctrine()->getRepository('AppBundle:Pack')->findBy([], ["position" => "ASC"]);
        foreach ($list_packs as $pack) {
            if ($pack->getType()) {
                $pack_type = $pack->getType()->getName();
            } else {
                $pack_type = "Core";
            }
            if (!isset($categories[$pack_type])) {
                $categories[$pack_type] = ['label'=> $pack_type, 'packs'=> [] ];
            }
            $checked = count($oPacks) ? in_array($pack->getId(), $oPacks) : ($pack->getDateRelease() != null);
            $categories[$pack_type]['packs'][] = ["code" => $pack->getCode(), "id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === null];
        }
        return $this->render('AppBundle:Collection:collection.html.twig', [
            'pagetitle' =>  "My Collection",
            'categories' => $categories,
            'reloaduser' => $reloaduser
        ]);
    }
    public function savePacksAction(Request $request) {
        $selectedPacks = $request->get('selected-packs');
        if (preg_match('/[^0-9\-,]/', $selectedPacks)) {
            return new Response('Invalid pack selection.');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $user->setOwnedPacks($selectedPacks);
        $em->persist($user);
        $em->flush();
        $this->get('session')->getFlashBag()->set('notice', "Collection saved.");
        return $this->redirect($this->get('router')->generate('collection_packs'));
    }
}

?>