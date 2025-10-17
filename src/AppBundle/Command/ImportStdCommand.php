<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Cardset;
use AppBundle\Entity\Pack;
use AppBundle\Entity\Card;

class ImportStdCommand extends ContainerAwareCommand
{
	/* @var $em EntityManager */
	private $em;

	private $links = [];
	private $duplicates = [];

	/* @var $output OutputInterface */
	private $output;

	private $collections = [];

	protected function configure()
	{
		$this
		->setName('app:import:std')
		->setDescription('Import cards data file in json format from a copy of https://github.com/zzorba/marvels-json-data')
		->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to the repository'
				);

		$this->addOption(
				'player',
				null,
				InputOption::VALUE_NONE,
				'Only player cards'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $input->getArgument('path');
		$player_only = $input->getOption('player');
		$this->em = $this->getContainer()->get('doctrine')->getEntityManager();
		$this->output = $output;

		/* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
		$helper = $this->getHelper('question');
		//$this->loadCollection('Card');
		// factions

		$output->writeln("Importing Classes...");
		$factionsFileInfo = $this->getFileInfo($path, 'factions.json');
		$imported = $this->importFactionsJsonFile($factionsFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Faction');
		$this->collections['Faction2'] = $this->collections['Faction'];
		$output->writeln("Done.");

		// types

		$output->writeln("Importing Types...");
		$typesFileInfo = $this->getFileInfo($path, 'types.json');
		$imported = $this->importTypesJsonFile($typesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Type');
		$output->writeln("Done.");

		// subtypes

		$output->writeln("Importing SubTypes...");
		$subtypesFileInfo = $this->getFileInfo($path, 'subtypes.json');
		$imported = $this->importSubtypesJsonFile($subtypesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Subtype');
		$output->writeln("Done.");

	// packtypes

		$output->writeln("Importing PackTypes...");
		$packtypesFileInfo = $this->getFileInfo($path, 'packtypes.json');
		$imported = $this->importPacktypesJsonFile($packtypesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Packtype');
		$output->writeln("Done.");

		$output->writeln("Importing CardsetTypes...");
		$cardsettypesFileInfo = $this->getFileInfo($path, 'settypes.json');
		$imported = $this->importCardsettypesJsonFile($cardsettypesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Cardsettype');
		$output->writeln("Done.");

		// card sets

		$output->writeln("Importing Card Sets...");
		$setsFileInfo = $this->getFileInfo($path, 'sets.json');
		$imported = $this->importCardSetsJsonFile($setsFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Cardset');
		$output->writeln("Done.");

		// second, packs

		$output->writeln("Importing Packs...");
		$packsFileInfo = $this->getFileInfo($path, 'packs.json');
		$imported = $this->importPacksJsonFile($packsFileInfo);
		$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Pack');
		$output->writeln("Done.");

		// third, cards

		$output->writeln("Importing Cards...");
		$imported = [];
		// get subdirs of files and do this for each file
		$scanned_directory = array_diff(scandir($path."/pack"), array('..', '.'));
		$fileSystemIterator = $this->getFileSystemIterator($path."/pack/");
		foreach ($fileSystemIterator as $fileinfo) {
			$imported = array_merge($imported, $this->importCardsJsonFile($fileinfo, $player_only));
		}
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		// reload the cards so we can link cards
		if ($this->links && count($this->links) > 0){
			$output->writeln("Resolving Links");
			$this->loadCollection('Card');
			foreach($this->links as $link){
				$card = $this->em->getRepository('AppBundle\\Entity\\Card')->findOneBy(['code' => $link['card_id']]);
				$target = $this->em->getRepository('AppBundle\\Entity\\Card')->findOneBy(['code' => $link['target_id']]);
				if ($card && $target){
					$card->setLinkedTo($target);
					$target->setLinkedTo();
					$output->writeln("Importing link between ".$card->getName()." and ".$target->getName().".");
				}
			}
			$this->em->flush();
		}

		// go over duplicates and create them based on the cards they are duplicating
		if ($this->duplicates && count($this->duplicates) > 0) {
			$output->writeln("Resolving Duplicates");
			$this->loadCollection('Card');
			foreach($this->duplicates as $duplicate) {
				$duplicate_of = $this->em->getRepository('AppBundle\\Entity\\Card')->findOneBy(['code' => $duplicate['duplicate_of']]);
				$new_card = $duplicate['card'];
				// create a new "card" using the data of this card.
				$new_card_data = $duplicate_of->serialize();
				$new_card_data['code'] = $new_card['code'];
				$new_card_data['duplicate_of'] = $duplicate['duplicate_of'];
				if (isset($new_card['pack_code'])) {
					$new_card_data['pack_code'] = $new_card['pack_code'];
				}
				if (isset($new_card['position'])) {
					$new_card_data['position'] = $new_card['position'];
				}
				if (isset($new_card['quantity'])) {
					$new_card_data['quantity'] = $new_card['quantity'];
				}
				if (isset($new_card['flavor'])) {
					$new_card_data['flavor'] = $new_card['flavor'];
				}
				$new_cards = [];
				$new_cards[] = $new_card_data;
				$duplicates_added = $this->importCardsFromJsonData($new_cards);
				print_r(count($duplicates_added));
				if ($duplicates_added && isset($duplicates_added[0])) {
					$duplicates_added[0]->setDuplicateOf($duplicate_of);
					//print_r($new_card_data);
				}
			}

			$this->em->flush();
		}


		$output->writeln("");
		$output->writeln("Generate cards json.");
		$doctrine = $this->getContainer()->get('doctrine');

		$supported_locales = $this->getContainer()->getParameter('supported_locales');
		$default_locale = $this->getContainer()->getParameter('locale');
		foreach($supported_locales as $supported_locale) {
			$doctrine->getRepository('AppBundle:Card')->setDefaultLocale($supported_locale);
			$list_cards = $doctrine->getRepository('AppBundle:Card')->findAll();
			// build the file
			$cards = array();
			/* @var $card \AppBundle\Entity\Card */
			foreach($list_cards as $card) {
				$cards[] = $this->getContainer()->get('cards_data')->getCardInfo($card, true, "en");
			}
			$content = json_encode($cards);
			$webdir = $this->getContainer()->get('kernel')->getRootDir() . "/../web";
			file_put_contents($webdir."/cards-all-".$supported_locale.".json", $content);

			$list_cards = $doctrine->getRepository('AppBundle:Card')->findAllWithoutEncounter();
			// build the file
			$cards = array();
			/* @var $card \AppBundle\Entity\Card */
			foreach($list_cards as $card) {
				$cards[] = $this->getContainer()->get('cards_data')->getCardInfo($card, true, "en");
			}
			$content = json_encode($cards);
			$webdir = $this->getContainer()->get('kernel')->getRootDir() . "/../web";
			file_put_contents($webdir."/cards-player-".$supported_locale.".json", $content);
		}
		$output->writeln("Done.");

	}

	protected function importFactionsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$faction = $this->getEntityFromData('AppBundle\\Entity\\Faction', $data, [
					'code',
					'name',
					'is_primary'
			], [], []);
			if($faction) {
				$result[] = $faction;
				$this->em->persist($faction);
			}
		}

		return $result;
	}

	protected function importTypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Type', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importSubtypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Subtype', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importPacktypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Packtype', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importCardsettypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Cardsettype', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importCardSetsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Cardset', $data, [
					'code',
					'name'
			], [
				'card_set_type_code'
			], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}


	protected function importScenariosJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Scenario', $data, [
				'code',
				'name'
			], [
				'campaign_code'
			], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importCampaignsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Campaign', $data, [
					'code',
					'name',
					'size'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importTaboosJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$taboosData = $this->getDataFromFile($fileinfo);
		foreach($taboosData as $tabooData) {
			$tabooData['cards'] = json_encode($tabooData['cards']);
			$taboo = $this->getEntityFromData('AppBundle\Entity\Taboo', $tabooData, [
					'code',
					'name',
					'date_start',
					'active',
					'cards'
			], [], []);
			if($taboo) {
				$result[] = $taboo;
				$this->em->persist($taboo);
			}
		}

		return $result;
	}


	protected function importPacksJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$packsData = $this->getDataFromFile($fileinfo);
		foreach($packsData as $packData) {
			$pack = $this->getEntityFromData('AppBundle\Entity\Pack', $packData, [
					'code',
					'name',
					'position',
					'size',
					'date_release'
			], [
				'pack_type_code'
			], [
					'cgdb_id'
			]);
			if($pack) {
				$result[] = $pack;
				$this->em->persist($pack);
			}
		}

		return $result;
	}

	protected function importCardsFromJsonData($cardsData) {
		$result = [];

		foreach($cardsData as $cardData) {
			$card = $this->getEntityFromData('AppBundle\Entity\Card', $cardData, [
				'code',
				'position',
				'quantity',
				'name'
			], [
				'faction_code',
				'faction2_code',
				'pack_code',
				'type_code',
				'subtype_code',
				'set_code',
				'back_card_code',
				'front_card_code'
			], [
				'deck_limit',
				'set_position',
				'illustrator',
				'flavor',
				'traits',
				'text',
				'cost',
				'cost_per_hero',
				'resource_physical',
				'resource_mental',
				'resource_energy',
				'resource_wild',
				'restrictions',
				'deck_options',
				'deck_requirements',
				'meta',
				'subname',
				'back_text',
				'back_flavor',
				'back_name',
				'double_sided',
				'is_unique',
				'hidden',
				'permanent',
				'errata',
				'octgn_id'

			]);
			if($card) {
				if ($card->getName()){
					$card->setRealName($card->getName());
				}
				if ($card->getTraits()){
					$card->setRealTraits($card->getTraits());
				}
				if ($card->getText()){
					$card->setRealText($card->getText());
				}
				$result[] = $card;
				$this->em->persist($card);
				if (isset($cardData['back_link'])){
					// if we have back link, store the reference here
					$this->links[] = ['card_id'=> $card->getCode(), 'target_id'=> $cardData['back_link']];
				}
			}
		}

		return $result;
	}

	protected function importCardsJsonFile(\SplFileInfo $fileinfo, $special="")
	{
		$result = [];

		$code = $fileinfo->getBasename('.json');
		if (stristr($code, "_encounter") !== FALSE && $special){
			return $result;
		}
		$code = str_replace("_encounter", "", $code);

		$pack = $this->em->getRepository('AppBundle:Pack')->findOneBy(['code' => $code]);
		if(!$pack) throw new \Exception("Unable to find Pack [$code]");

		$cardsData = $this->getDataFromFile($fileinfo);
		$result = $this->importCardsFromJsonData($cardsData);
		// return all cards imported
		return $result;
	}

	protected function copyFieldValueToEntity($entity, $entityName, $fieldName, $newJsonValue)
	{
		$metadata = $this->em->getClassMetadata($entityName);
		$type = $metadata->fieldMappings[$fieldName]['type'];

		// new value, by default what json gave us is the correct typed value
		$newTypedValue = $newJsonValue;

		// current value, by default the json, serialized value is the same as what's in the entity
		$getter = 'get'.ucfirst($fieldName);
		$currentJsonValue = $currentTypedValue = $entity->$getter();

		// if the field is a data, the default assumptions above are wrong
		if(in_array($type, ['date', 'datetime'])) {
			if($newJsonValue !== null) {
				$newTypedValue = new \DateTime($newJsonValue);
			}
			if($currentTypedValue !== null) {
				switch($type) {
					case 'date': {
						$currentJsonValue = $currentTypedValue->format('Y-m-d');
						break;
					}
					case 'datetime': {
						$currentJsonValue = $currentTypedValue->format('Y-m-d H:i:s');
					}
				}
			}
		}

		$different = ($currentJsonValue !== $newJsonValue);
		if($different) {
			//print_r(gettype($currentJsonValue));
			//print_r(gettype($newJsonValue));
			if (is_array($currentJsonValue) || is_array($newJsonValue)){
				$this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info>");
			} else {
				$this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info> ($currentJsonValue => $newJsonValue)");
			}
			$setter = 'set'.ucfirst($fieldName);
			$entity->$setter($newTypedValue);
		}
	}

	protected function copyKeyToEntity($entity, $entityName, $data, $key, $isMandatory = TRUE)
	{
		$metadata = $this->em->getClassMetadata($entityName);
		if(!key_exists($key, $data)) {
			if($isMandatory) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			} else {
				$data[$key] = null;
			}
		}

		$value = $data[$key];
		if ($key == "is_unique"){
			if (!$value){
				$value = false;
			}
		}
		if ($key == "hidden"){
			if (!$value){
				$value = false;
			}
		}
		if ($key == "permanent"){
			if (!$value){
				$value = false;
			}
		}

		if ($key == "deck_requirements"){
			if ($value){
				$value = json_encode($value);
			}
		}

		if ($key == "meta"){
			if ($value){
				$value = json_encode($value);
			}
		}

		if ($key == "deck_options" && $value){
			if ($value){
				$value = json_encode($value);
			}
		}

		if(!key_exists($key, $metadata->fieldNames)) {
			throw new \Exception("Missing column [$key] in entity ".$entityName);
		}
		$fieldName = $metadata->fieldNames[$key];

		$this->copyFieldValueToEntity($entity, $entityName, $fieldName, $value);
	}

	protected function getEntityFromData($entityName, $data, $mandatoryKeys, $foreignKeys, $optionalKeys)
	{
		if(!key_exists('code', $data)) {
			throw new \Exception("Missing key [code] in ".json_encode($data));
		}

		if (key_exists('duplicate_of', $data) && !key_exists('name', $data)) {
			$this->duplicates[] = ['card' => $data, 'duplicate_of' => $data['duplicate_of']];
			return;
		}
		$entity = $this->em->getRepository($entityName)->findOneBy(['code' => $data['code']]);

		if(!$entity) {
			// if we cant find it, try more complex methods just to check
			// the only time this should work is if the existing name also has an _ meaning it was temporary.

			if (!$entity){
				$entity = new $entityName();
			}
		}
		$orig = $entity->serialize();
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}

		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, FALSE);
		}

		foreach($foreignKeys as $key) {
			$foreignEntityShortName = ucfirst(str_replace('_code', '', $key));
			if ($key === "front_card_code"){
				$foreignEntityShortName = "Card";
			}
			if ($key === "set_code") {
				$foreignEntityShortName = "Cardset";
			}
			if ($key === "pack_type_code") {
				$foreignEntityShortName = 'Packtype';
			}
			if ($key === "card_set_type_code") {
				$foreignEntityShortName = 'Cardsettype';
			}

			if(!key_exists($key, $data)) {
				// optional links to other tables
				if ($key === "faction2_code" || $key === "subtype_code" || $key === "set_code" || $key === "back_card_code" || $key === "front_card_code"){
					continue;
				}
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}

			$foreignCode = $data[$key];
			if(!key_exists($foreignEntityShortName, $this->collections)) {
				throw new \Exception("No collection for [$foreignEntityShortName] in ".json_encode($data));
			}

			if (!$foreignCode){
				continue;
			}
			//echo "\n";
			//print("hvor mange ".count($this->collections[$foreignEntityShortName]));
			if(!key_exists($foreignCode, $this->collections[$foreignEntityShortName])) {
				throw new \Exception("Invalid code [$foreignCode] for key [$key] in ".json_encode($data));
			}
			$foreignEntity = $this->collections[$foreignEntityShortName][$foreignCode];

			$getter = 'get'.$foreignEntityShortName;

			if(!$entity->$getter() || $entity->$getter()->getId() !== $foreignEntity->getId()) {
				$this->output->writeln("Changing the <info>$key</info> of <info>".$entity->toString()."</info>");
				$setter = 'set'.$foreignEntityShortName;
				$entity->$setter($foreignEntity);
			}
		}

		// special case for Card
		if($entityName === 'AppBundle\Entity\Card') {
			// calling a function whose name depends on the type_code
			$cleanName = $entity->getType()->getName();
			if ($cleanName == "Alter-Ego") {
				$cleanName = "AlterEgo";
			}
			if ($cleanName == "Side Scheme") {
				$cleanName = "SideScheme";
			}
			if ($cleanName == "Main Scheme") {
				$cleanName = "MainScheme";
			}
			if ($cleanName == "Player Side Scheme") {
				$cleanName = "PlayerSideScheme";
			}
			if ($cleanName == "Evidence - Means") {
				$cleanName = "EvidenceMeans";
			}
			if ($cleanName == "Evidence - Motive") {
				$cleanName = "EvidenceMotive";
			}
			if ($cleanName == "Evidence - Opportunity") {
				$cleanName = "EvidenceOpportunity";
			}
			$functionName = 'import' . $cleanName . 'Data';
			$this->$functionName($entity, $data);
		}

		if($entity->serialize() !== $orig || (isset($data['back_link']) && (!$entity->getLinkedTo() || $entity->getLinkedTo()->getCode() != $data['back_link']) )) return $entity;

	}

	protected function importSupportData(Card $card, $data)
	{

	}

	protected function importUpgradeData(Card $card, $data)
	{
		$optionalKeys = [
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_crisis',
			'scheme_hazard',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importObligationData(Card $card, $data)
	{
		$optionalKeys = [
			'boost',
			'boost_star',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_crisis',
			'scheme_hazard',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importHeroData(Card $card, $data)
	{
		$mandatoryKeys = [
			'attack',
			'defense',
			'hand_size',
			'health',
			'thwart',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		$optionalKeys = [
			'attack_star',
			'defense_star',
			'health_star',
			'scheme_acceleration',
			'thwart_star',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importAlterEgoData(Card $card, $data)
	{
		$mandatoryKeys = [
			'health',
			'hand_size',
			'recover',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		$optionalKeys = [
			'health_star',
			'recover_star',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importAllyData(Card $card, $data)
	{
		$mandatoryKeys = [
			'attack',
			'health',
			'thwart',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		$optionalKeys = [
			'attack_cost',
			'attack_star',
			'health_star',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_hazard',
			'thwart_cost',
			'thwart_star',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}


	protected function importMinionData(Card $card, $data)
	{
		$optionalKeys = [
			'attack',
			'attack_star',
			'boost',
			'boost_star',
			'health',
			'health_per_group',
			'health_per_hero',
			'health_star',
			'scheme',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_hazard',
			'scheme_star',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importEnvironmentData(Card $card, $data)
	{
		$optionalKeys = [
			'boost',
			'boost_star',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_hazard',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importEvidenceMeansData(Card $card, $data)
	{

	}

	protected function importEvidenceMotiveData(Card $card, $data)
	{

	}

	protected function importEvidenceOpportunityData(Card $card, $data)
	{

	}

	protected function importSideSchemeData(Card $card, $data)
	{
		$optionalKeys = [
			'base_threat',
			'base_threat_fixed',
			'base_threat_per_group',
			'boost',
			'boost_star',
			'escalation_threat',
			'escalation_threat_fixed',
			'escalation_threat_star',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_crisis',
			'scheme_hazard',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importMainSchemeData(Card $card, $data)
	{
		$optionalKeys = [
			'base_threat',
			'base_threat_fixed',
			'base_threat_per_group',
			'escalation_threat',
			'escalation_threat_fixed',
			'escalation_threat_star',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_crisis',
			'scheme_hazard',
			'stage',
			'threat',
			'threat_fixed',
			'threat_per_group',
			'threat_star',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importPlayerSideSchemeData(Card $card, $data)
	{
		$mandatoryKeys = [
			'base_threat',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		$optionalKeys = [
			'base_threat_fixed',
			'base_threat_per_group',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_crisis',
			'scheme_hazard',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importEventData(Card $card, $data)
	{
		$mandatoryKeys = [
			'cost'
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}

	protected function importResourceData(Card $card, $data)
	{

	}

	protected function importVillainData(Card $card, $data)
	{
		$mandatoryKeys = [
			'health',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		$optionalKeys = [
			'attack',
			'attack_star',
			'health_per_group',
			'health_per_hero',
			'health_star',
			'scheme',
			'scheme_star',
			'stage',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importLeaderData(Card $card, $data)
	{
		$mandatoryKeys = [
			'health',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		$optionalKeys = [
			'attack',
			'attack_star',
			'health_per_hero',
			'health_star',
			'scheme',
			'scheme_star',
			'stage',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importTreacheryData(Card $card, $data)
	{
		$optionalKeys = [
			'boost',
			'boost_star',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importAttachmentData(Card $card, $data)
	{
		$optionalKeys = [
			'attack',
			'attack_star',
			'boost',
			'boost_star',
			'scheme',
			'scheme_acceleration',
			'scheme_amplify',
			'scheme_crisis',
			'scheme_hazard',
			'scheme_star',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function getDataFromFile(\SplFileInfo $fileinfo)
	{

		$file = $fileinfo->openFile('r');
		$file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

		$lines = [];
		foreach($file as $line) {
			if($line !== false) $lines[] = $line;
		}
		$content = implode('', $lines);

		$data = json_decode($content, true);

		if($data === null) {
			throw new \Exception("File [".$fileinfo->getPathname()."] contains incorrect JSON (error code ".json_last_error().")");
		}

		return $data;
	}

	protected function getDataFromString($string) {
		$data = json_decode($string, true);

		if($data === null) {
			throw new \Exception("String contains incorrect JSON (error code ".json_last_error().")");
		}

		return $data;
	}

	protected function getFileInfo($path, $filename)
	{
		$fs = new Filesystem();

		if(!$fs->exists($path)) {
			throw new \Exception("No repository found at [$path]");
		}

		$filepath = "$path/$filename";

		if(!$fs->exists($filepath)) {
			throw new \Exception("No $filename file found at [$path]");
		}

		return new \SplFileInfo($filepath);
	}

	protected function getFileSystemIterator($path)
	{
		$fs = new Filesystem();

		if(!$fs->exists($path)) {
			throw new \Exception("No repository found at [$path]");
		}

		$iterator = new \GlobIterator("$path/*.json");

		if(!$iterator->count()) {
			throw new \Exception("No json file found at [$path]");
		}

		return $iterator;
	}

	protected function loadCollection($entityShortName)
	{
		$this->collections[$entityShortName] = [];
		$entities = $this->em->getRepository('AppBundle:'.$entityShortName)->findAll();
		//echo $entityShortName."\n";
		foreach($entities as $entity) {
			$this->collections[$entityShortName][$entity->getCode()] = $entity;
			//echo $entity->getCode()."\n";
		}
	}

}
