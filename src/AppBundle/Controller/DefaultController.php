<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Model\DecklistManager;
use AppBundle\Entity\Decklist;

class DefaultController extends Controller
{

	public function indexAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		/**
		* @var $decklist_manager DecklistManager
		*/
		$decklist_manager = $this->get('decklist_manager');
		$decklist_manager->setLimit(50);

		$typeNames = [];
		foreach($this->getDoctrine()->getRepository('AppBundle:Type')->findAll() as $type) {
			$typeNames[$type->getCode()] = $type->getName();
		}

		$decklists_by_popular = [];
		$decklists_by_recent = [];
		$decklists_by_hero = [];
		$dupe_deck_list = [];

		$factions = $this->getDoctrine()->getRepository('AppBundle:Faction')->findBy(['isPrimary' => true], ['code' => 'ASC']);

		$type = $this->getDoctrine()->getRepository('AppBundle:Type')->findOneBy(['code' => 'hero'], ['id' => 'DESC']);
		$cards = $this->getDoctrine()->getRepository('AppBundle:Card')->findBy(['type' => $type], ['id' => 'ASC']);

		$date1 = strtotime('2022-05-22');
		$date2 = time();

		$year1 = date('Y', $date1);
		$year2 = date('Y', $date2);

		$month1 = date('m', $date1);
		$month2 = date('m', $date2);

		// $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
		$diff = $date2 - $date1;
		$weeks_since = ($diff / (60 * 60 * 24 * 7));
		if ($weeks_since >= 0 && $weeks_since < count($cards)) {
			$card = $cards[$weeks_since];
		} else {
			throw new \Exception("Ran out of heroes for spotlight.");
		}

		$paginator = $decklist_manager->findDecklistsByHero($card, true);
		$iterator = $paginator->getIterator();
		$userCheck = [];
		while($iterator->valid() && count($decklists_by_hero) < 10)
		{
			$decklist = $iterator->current();
			if (!isset($userCheck[$decklist->getUser()->getId()])){
				$decklists_by_hero[] = ['hero_meta' => json_decode($decklist->getCharacter()->getMeta()), 'faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist, 'meta' => json_decode($decklist->getMeta()) ];
				$userCheck[$decklist->getUser()->getId()] = true;
				$dupe_deck_list[$decklist->getId()] = true;
			}
			$iterator->next();
		}

		$paginator = $decklist_manager->findDecklistsByTrending();
		$iterator = $paginator->getIterator();
		while($iterator->valid() && count($decklists_by_popular) < 8)
		{
			$decklist = $iterator->current();
			if ($decklist->getCharacter()->getCode() != $card->getCode() && !isset($dupe_deck_list[$decklist->getId()])) {
				$decklists_by_popular[] = ['hero_meta' => json_decode($decklist->getCharacter()->getMeta()), 'faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist, 'meta' => json_decode($decklist->getMeta()) ];
				$dupe_deck_list[$decklist->getId()] = true;
			}
			$iterator->next();
		}
		$paginator = $decklist_manager->findDecklistsByAge(true);
		$iterator = $paginator->getIterator();
		$userCheck = [];
		while($iterator->valid() && count($decklists_by_recent) < 8)
		{
			$decklist = $iterator->current();
			if (!isset($userCheck[$decklist->getUser()->getId()])){
				if ($decklist->getCharacter()->getCode() != $card->getCode() && !isset($dupe_deck_list[$decklist->getId()])) {
					$decklists_by_recent[] = ['hero_meta' => json_decode($decklist->getCharacter()->getMeta()), 'faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist, 'meta' => json_decode($decklist->getMeta()) ];
					$userCheck[$decklist->getUser()->getId()] = true;
					$dupe_deck_list[$decklist->getId()] = true;
				}
			}
			$iterator->next();
		}

		$date1 = strtotime('2022-06-19');
		$date2 = time();

		$year1 = date('Y', $date1);
		$year2 = date('Y', $date2);

		$month1 = date('m', $date1);
		$month2 = date('m', $date2);

		// $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
		$diff = $date2 - $date1;
		$days_since = ($diff / (60 * 60 * 24));
		$cards_offset = [0,10,20];
		if ($days_since >= 0) {
			$card_offset = $cards_offset[$days_since % 3] + (floor($days_since / 3));
			$cards = $this->getDoctrine()->getRepository('AppBundle:Card')->findBy(['card_set' => null], ['id' => 'ASC'], 1, $card_offset);
			if (count($cards) > 0) {
				$card_of_the_day = $cards[0];
			} else {
				throw new \Exception("Ran out of heroes for spotlight.");
			}
		} else {
			throw new \Exception("Ran out of heroes for spotlight.");
		}

		$card_of_the_day_info = $this->get('cards_data')->getCardInfo($card_of_the_day, false, false);
		$paginator = $decklist_manager->findDecklistsByCard($card_of_the_day, true);
		$iterator = $paginator->getIterator();
		$card_of_the_day_decklists = [];
		$no_dupe_heroes = [];
		while($iterator->valid() && count($card_of_the_day_decklists) < 8)
		{
			$decklist = $iterator->current();
			if (!isset($no_dupe_heroes[$decklist->getCharacter()->getId()]) && !isset($dupe_deck_list[$decklist->getId()])) {
				$card_of_the_day_decklists[] = ['hero_meta' => json_decode($decklist->getCharacter()->getMeta()), 'faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist, 'meta' => json_decode($decklist->getMeta()) ];
				$dupe_deck_list[$decklist->getId()] = true;
				$no_dupe_heroes[$decklist->getCharacter()->getId()] = true;
			}
			$iterator->next();
		}

		$game_name = $this->container->getParameter('game_name');
		$publisher_name = $this->container->getParameter('publisher_name');

		$packs = $this->getDoctrine()->getRepository('AppBundle:Pack')->findBy([], ['dateRelease' => 'DESC']);

		// 1640210361

		return $this->render('AppBundle:Default:index.html.twig', [
		'pagetitle' =>  "$game_name Deckbuilder",
		'pagedescription' => "Build your deck for $game_name by $publisher_name. Browse the cards and the thousand of decklists submitted by the community. Publish your own decks and get feedback.",
		'decklists_by_popular' => $decklists_by_popular,
		'decklists_by_recent' => $decklists_by_recent,
		'hero_highlight' => $card,
		'hero_highlight_meta' => json_decode($card->getMeta()),
		'card_of_the_day' => $card_of_the_day_info,
		'card_of_the_day_decklists' => $card_of_the_day_decklists,
		'decklists_by_hero' => $decklists_by_hero,
		'packs' => array_slice($packs, 0, 4)
		], $response);
	}

	function rulesAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		$page = $this->renderView('AppBundle:Default:rulesreference.html.twig',
		array("pagetitle" => "Rules", "pagedescription" => "Rules Reference"));
		$response->setContent($page);
		return $response;
	}

	function aboutAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		return $this->render('AppBundle:Default:about.html.twig', array(
		"pagetitle" => "About",
		"game_name" => $this->container->getParameter('game_name'),
		), $response);
	}

	function apiIntroAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		return $this->render('AppBundle:Default:apiIntro.html.twig', array(
		"pagetitle" => "API",
		"game_name" => $this->container->getParameter('game_name'),
		"publisher_name" => $this->container->getParameter('publisher_name'),
		), $response);
	}
}
