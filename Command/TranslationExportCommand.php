<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Exception;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use MSDev\DoctrineFileMakerDriverBundle\Entity\WebContent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Kernel;

class TranslationExportCommand extends Command
{
    /** @var array  */
    private $languages = [];

    /** @var EntityManagerInterface */
    private $em;

    /** @var string */
    private $projectDir;

    /** @var ParameterBagInterface */
    private $params;

    /** @var bool */
    private $phpAPI;

    /**
     * TranslationExportCommand constructor.
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $params
     * @param string $projectDir
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, string $projectDir)
    {
        parent::__construct(null);

        $this->em = $em;
        $this->params = $params;
        $this->projectDir = $projectDir;
        $this->phpAPI = $em->getConnection()->getDriver() instanceof \MSDev\DoctrineFileMakerDriver\FMDriver;
    }


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('translation:export')
            ->setDescription('Export the web content from FileMaker into translation files.')
            ->addOption(
                'no-cache-clear',
                null,
                InputOption::VALUE_NONE,
                'Skip clearing the cache; only use this if you plan to clear it '
                . 'manually after running other tasks.'
            )
            ->addOption(
                'icu',
                null,
                InputOption::VALUE_OPTIONAL,
                'Translations are in intl-icu format.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $icu = filter_var($input->getOption('icu'), FILTER_VALIDATE_BOOLEAN);
        // load the content of the WebContent table from FM
        $text = $this->loadTextFromDB();
        $this->updateFile($text, $output, 'messages', $icu);

        // now update the javascript versions
        $this->processJavascript($output);

        // clear cache unless requested not to
        if ($input->getOption('no-cache-clear')) {
            return 0;
        }

        $this->clearAndRewarmCache($output);
        return 0;
    }

    private function processJavascript(OutputInterface $output): void
    {
        $config = false;
        try {
            $config = $this->params->get('doctrine_file_maker_driver.javascript_translations');
        } catch(InvalidArgumentException $e) { }

        if(!$config) {
            $output->writeln([
                '<comment>If you wish to use translations in JavaScript please install the ',
                '    BazingaJsTranslationBundle',
                '(composer require willdurand/js-translation-bundle) and set config value ',
                '    doctrine_file_maker_driver.javascript_translations',
                'to true</comment>']);
            return;
        }

        $secondaryInput = new ArrayInput([]);
        if(Kernel::MAJOR_VERSION >= 4) {
            $secondaryInput = new ArrayInput([
                'target' => $this->projectDir . '/translations/js/',
                '--format' => ['json']
            ]);
        }

        $application = $this->getApplication();
        if(null === $application) {
            $output->writeln('<error>Unable to retrieve application</error>');
            return;
        }

        try {
            $command = $application->find('bazinga:js-translation:dump');
            if (0 === $command->run($secondaryInput, $output)) {
                $output->writeln('<info>JavaScript translations updated</info>');
            }
        } catch (CommandNotFoundException $e) {
            $output->writeln('<comment>To use translations in JavaScript you must install the ' .
                'BazingaJsTranslationBundle</comment>');
            return;
        } catch (Exception $except) {
            $output->writeln('<error>Unable to run command bazinga:js-translation:dump</error>');
        }
    }

    /**
     * @param $text
     * @param OutputInterface $output
     * @param string $type
     *
     * @throws RuntimeException
     */
    private function updateFile($text, OutputInterface $output, string $type, bool $icu): void
    {
        // perform the conversion from the messages strings file
        $en = $this->convertFromDB($text);

        $this->writeFile($en, $type, 'en', $icu);
        $output->writeln('<info>en messages translation updated</info>');

        // now update the other languages
        $this->updateOtherLanguages($output, $en, $type);
    }

    /**
     * Save the file to disk
     *
     * @param string $file
     * @param string $language
     *
     * @return string
     *
     * @throws RuntimeException
     */
    private function loadOrCreateFile($file, $language): string
    {
        $qualifiedFile = $this->getTranslationsPath().DIRECTORY_SEPARATOR.$file;
        if (!file_exists($qualifiedFile)) {
            touch($qualifiedFile);
            $xml = $this->createEmptyXliffFile($language);

            $domxml = new DOMDocument('1.0');
            $domxml->preserveWhiteSpace = false;
            $domxml->formatOutput = true;
            $domxml->loadXML($xml->asXML());
            $domxml->save($file);

            return $xml->asXML();
        }

        return $this->loadfile($file);
    }

    private function loadFile(string $file): string
    {
        $file = $this->getTranslationsPath().DIRECTORY_SEPARATOR.$file;

        if (!file_exists($file)) {
            throw new RuntimeException(sprintf('Unable to open source file: %s', $file));
        }

        return file_get_contents($file);
    }

    private function clearAndRewarmCache(OutputInterface $output): void
    {
        $application = $this->getApplication();
        if(null === $application) {
            $output->writeln('<error>Unable to retrieve application container</error>');
            return;
        }

        $this->clearCache($output, $application);
        $this->warmCache($output, $application);
    }

    /**
     * Converts the DB records into the en XLIFF document
     * Because the default language for the app is English both the source
     * and the target are English
     *
     * @param array $contentArray      Array of data from the DB
     * @return SimpleXMLElement        XLIF formatted content
     */
    private function convertFromDB($contentArray): SimpleXMLElement
    {
        $xml = $this->createEmptyXliffFile('en');

        /** @var WebContent $trans */
        foreach($contentArray as $contentRow) {
            /** @noinspection PhpUndefinedFieldInspection
             * @var SimpleXMLElement $trans */
            $trans = $xml->file->body->addChild('trans-unit');
            $trans->addAttribute('id', $this->cleanContent($contentRow->getId()));
            $trans->addAttribute('resname', $this->cleanContent($contentRow->getId()));
            $trans->addChild('source', $this->cleanContent($contentRow->getContent()));
            $trans->addChild('target', $this->cleanContent($contentRow->getContent()));
        }

        return $xml;
    }

    private function cleanContent($string)
    {
        if($this->phpAPI) {
            return trim($string);
        }

        return trim(
            htmlspecialchars($string, ENT_QUOTES)
        );
    }


    /**
     * Parses through other required languages to add newly created nodes
     * to their translation documents
     *
     * @param OutputInterface $output
     * @param SimpleXMLElement $en
     * @param $type
     * @throws RuntimeException
     */
    private function updateOtherLanguages(OutputInterface $output, SimpleXMLElement $en, $type): void
    {
        // load each language file
        foreach ($this->languages as $lg) {
            $filename = "{$type}.{$lg}.xlf";
            $fileContents = $this->loadOrCreateFile($filename, $lg);
            $$lg = simplexml_load_string($fileContents);

            if (!($$lg instanceof SimpleXMLElement)) {
                throw new RuntimeException(
                    sprintf('File %s does not contain valid xml: %s', $filename, print_r($fileContents, true))
                );
            }
        }

        // parse through each node in the english document and add it if missing
        /** @noinspection PhpUndefinedFieldInspection */
        foreach ($en->file->body->{'trans-unit'} as $trans) {
            foreach ($this->languages as $lg) {
                if (!$this->nodeExists(${$lg}, $trans['id'])) {
                    /** @var SimpleXMLElement $t */
                    $t = ${$lg}->file->body->addChild('trans-unit');
                    $t->addAttribute('id', (string)$trans['id']);
                    $t->addChild('source', (string)$trans->source);
                    $t->addChild('target', (string)$trans->target);
                }
            }
        }

        // update the files on disk
        foreach ($this->languages as $lg) {
            $this->writeFile(${$lg}, $type, $lg);
            $output->writeln("<info>{$lg} {$type} translations updated</>");
        }
    }

    /**
     * Determine if the node in question exists in the current language
     *
     * @param SimpleXMLElement $xml
     * @param string $id
     * @return boolean
     */
    private function nodeExists(SimpleXMLElement $xml, $id): bool
    {
        /** @noinspection PhpUndefinedFieldInspection */
        foreach ($xml->file->body->{'trans-unit'} as $trans) {
            if ((string)$trans['id'] === (string)$id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $language
     * @return SimpleXMLElement
     */
    private function createEmptyXliffFile($language): SimpleXMLElement
    {
        $xml = new SimpleXMLElement('<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2"/>');
        $file = $xml->addChild('file');
        $file->addAttribute('source-language', 'en');
        $file->addAttribute('target-language', $language);
        $file->addAttribute('datatype', 'plaintext');
        $file->addAttribute('original', 'file.ext');
        $file->addChild('body');

        return $xml;
    }


    /**
     * Saves an XLIF XML document to the correct messages file
     *
     * @param SimpleXMLElement $xml     XML to save
     * @param string $type              Type of file to save (messages, routes etc)
     * @param string $locale            Name of the locale
     *
     * @throws RuntimeException
     */
    private function writeFile(SimpleXMLElement $xml, $type, $locale, bool $icu): void
    {
        $icuStr = $icu ? '+intl-icu' : '';
        $file = $this->getTranslationsPath().DIRECTORY_SEPARATOR."{$type}{$icuStr}.{$locale}.xlf";
        if (!file_exists($file)) {
            $handle = fopen($file, 'wb');
            fclose($handle);
        }
        if (!is_writable($file)) {
            throw new RuntimeException('Unable to open output file for writing');
        }

        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xml->asXML());
        $domxml->save($file);
    }

    private function getTranslationsPath(): string
    {
        if(Kernel::MAJOR_VERSION >= 4) {
            return $this->projectDir . '/translations';
        }

        return $this->projectDir . '/vendor/matatirosoln/doctrine-filemaker-driver-bundle/Resources/translations';
    }

    /**
     * Load the content for the translation file from the DB
     */
    private function loadTextFromDB(): array
    {
        return $this->em->getRepository('DoctrineFileMakerDriverBundle:WebContent')
            ->findAll();
    }

    private function clearCache(OutputInterface $output, Application $application): void
    {
        $command = $application->find('cache:clear');
        $arguments = array(
            'command' => 'cache:clear',
            '--no-warmup' => true,
        );
        $input = new ArrayInput($arguments);

        try {
            if ($command->run($input, $output) === 0) {
                $output->writeln('<info>Cache cleared</info>');
            }
        } catch (Exception $e) {
            $output->writeln('<error>Unable to clear cache</error>');
        }
    }

    private function warmCache(OutputInterface $output, Application $application): void
    {
        $command = $application->find('cache:warm');
        $arguments = array(
            'command' => 'cache:warm'
        );
        $input = new ArrayInput($arguments);

        try {
            if ($command->run($input, $output) === 0) {
                $output->writeln('<info>Cache warmed</info>');
            }
        } catch (Exception $e) {
            $output->writeln('<error>Unable to warm cache</error>');
        }
    }
}
