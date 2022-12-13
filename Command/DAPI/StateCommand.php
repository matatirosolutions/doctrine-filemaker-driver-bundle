<?php
declare(strict_types=1);

namespace MSDev\DoctrineFileMakerDriverBundle\Command\DAPI;

use MSDev\DoctrineFileMakerDriverBundle\Exception\AdminAPIException;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AuthenticationException;
use MSDev\DoctrineFileMakerDriverBundle\Service\DataApiAdminService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StateCommand extends Command
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
        $this->setName('filemaker:dapi:state')
            ->setDescription('Gets the state of the FileMaker Data API.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->dapiService->isDAPIEnabled();
            if ($result) {
                $output->writeln('<info>DAPI is enabled</info>');
                return Command::SUCCESS;
            }

            $output->writeln('<error>DAPI is NOT enabled</error>');
            return Command::SUCCESS;
        } catch (AdminAPIException|AuthenticationException $exception) {
            $output->writeln(
                sprintf('<error>An error occurred fetching DAPI state: %s</error>', $exception->getMessage())
            );
            return Command::FAILURE;
        }
    }

}
