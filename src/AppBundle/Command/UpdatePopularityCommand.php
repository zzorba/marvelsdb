<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

class UpdatePopularityCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this->setName('app:update-popularity')
             ->setDescription('Recalculates popularity metrics for open decklists.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Updating popularity metrics...');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $conn = $em->getConnection();

        $sql = "UPDATE decklist
                SET pop = (1 + nb_votes) / (1 + DATEDIFF(NOW(), date_creation))
                WHERE next_deck IS NULL";

        $rows = $conn->executeStatement($sql);

        $output->writeln(sprintf('Successfully processed %d records.', $rows));

    }
}