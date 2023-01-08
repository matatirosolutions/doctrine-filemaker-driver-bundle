<?php
declare(strict_types=1);

namespace MSDev\DoctrineFileMakerDriverBundle\Command\DAPI;

use MSDev\DoctrineFileMakerDriverBundle\Exception\AdminAPIException;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AuthenticationException;
use MSDev\DoctrineFileMakerDriverBundle\Service\DataApiAdminService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ResetCommand extends Command
{
    /** @var DataApiAdminService */
    private $dapiService;

    public function __construct(DataApiAdminService $dapiService)
    {
        parent::__construct();
        $this->dapiService = $dapiService;
    }

    protected function configure(): void
    {
        $this->setName('filemaker:dapi:reset')
            ->setDescription('Disables, then re-enables the FileMaker Data API.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->dapiService->setDAPIState(false);
            if ($result) {
                $output->writeln('<error>Unable to disable DAPI</error>');
                return Command::FAILURE;
            }
            $output->writeln('<info>DAPI has been disabled</info>');

            $result = $this->dapiService->setDAPIState(true);
            if ($result) {
                $output->writeln('<info>DAPI has been enabled</info>');
                return Command::SUCCESS;
            }

            $output->writeln('<error>Unable to enable DAPI</error>');
            return Command::FAILURE;
        } catch (AdminAPIException|AuthenticationException $exception) {
            $output->writeln(
                sprintf('<error>An error occurred managing DAPI: %s</error>', $exception->getMessage())
            );
            return Command::FAILURE;
        }
    }

}
