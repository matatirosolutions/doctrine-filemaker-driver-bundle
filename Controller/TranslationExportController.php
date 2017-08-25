<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class TranslationExportController extends Controller
{

    public function TranslationExportAction()
    {
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
        $application->run($input, $output);

        return new JsonResponse([
            'success' => true
        ]);
    }
}
