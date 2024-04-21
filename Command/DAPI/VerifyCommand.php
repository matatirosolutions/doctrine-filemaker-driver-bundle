<?php
declare(strict_types=1);

namespace MSDev\DoctrineFileMakerDriverBundle\Command\DAPI;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AdminAPIException;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AuthenticationException;
use MSDev\DoctrineFileMakerDriverBundle\Service\DataApiAdminService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class VerifyCommand extends Command
{
    /** @var DataApiAdminService */
    private $dapiService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(ManagerRegistry $managerRegistry, DataApiAdminService $dapiService)
    {
        parent::__construct();
        $this->entityManager = $managerRegistry->getManager('content');
        $this->dapiService = $dapiService;
    }

    protected function configure(): void
    {
        $this->setName('filemaker:dapi:verify')
            ->setDescription('Makes a call to the FileMaker Data API to verify that it responds. If not it tries to restart it.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Can we currently connect to DAPI
        try {
            $result = $this->dapiService->connectToDAPI($this->entityManager);
            // Yes, no further action required
            if ($result) {
                $output->writeln('<info>Successfully connected to DAPI</info>');
                return Command::SUCCESS;
            }
        } catch (AdminAPIException|AuthenticationException $exception) {
            $output->writeln(
                sprintf('<error>An error occurred fetching DAPI state: %s</error>', $exception->getMessage())
            );
        }

        try {
            // We are usually in this position because of a DAPI crash - explicitly disable DAPI first
            $this->dapiService->setDAPIState(false);
            $output->writeln('<info>DAPI explicitly set to disabled</info>');

            // Then enable it again
            $result = $this->dapiService->setDAPIState(true);
            if ($result) {
                $output->writeln('<info>DAPI enabled</info>');
            }

            // Wait a few seconds to allow things to catch up
            $output->writeln('<info>Waiting for service to restart</info>');
            sleep(10);

            // Now try to connect again
            $result = $this->dapiService->connectToDAPI($this->entityManager);
            if ($result) {
                $output->writeln('<info>Successfully connected to DAPI</info>');
                return Command::SUCCESS;
            }

            $output->writeln('<error>DAPI is NOT enabled</error>');
            return Command::FAILURE;
        } catch (AdminAPIException|AuthenticationException $exception) {
            $output->writeln(
                sprintf('<error>An error occurred fetching DAPI state: %s</error>', $exception->getMessage())
            );
            return Command::FAILURE;
        }
    }

}
