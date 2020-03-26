<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;


class TranslationExportController extends AbstractController
{
    public function TranslationExportAction()
    {
        /** @var KernelInterface $kernel */
        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'translation:export',
        ));
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_NORMAL,
            true
        );
        try {
            $application->run($input, $output);
            return new JsonResponse([
                'success' => true
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
