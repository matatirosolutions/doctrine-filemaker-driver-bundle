<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use MSDev\DoctrineFileMakerDriverBundle\Entity\WebContent;

class TranslationExportCommand extends ContainerAwareCommand
{

    protected $languages = [];

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setName('translation:export')
            ->setDescription('Export the web content from FileMaker into translation files.')
            ->addOption(
                'no-cache-clear',
                null,
                InputOption::VALUE_NONE,
                "Skip clearing the cache; only use this if you plan to clear it "
                . "manually after running other tasks."
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // load the content of the WebContent table from FM
        $text = $this->loadTextFromDB();
        $this->updateFile($text, $output, 'messages');

        // now update the javascript versions
        $this->processJavascript($output);

        // clear cache unless requested not to
        if ($input->getOption("no-cache-clear")) {
            return;
        }

        $this->clearCache($output);
    }

    private function processJavascript(OutputInterface $output)
    {
        $config = false;
        try {
            $config = $this->getContainer()->getParameter('doctrine_file_maker_driver.javascript_translations');
        } catch(InvalidArgumentException $e) { }

        if(!$config) {
            $output->writeln([
                "<comment>If you wish to use translations in JavaScript please install the ",
                "    BazingaJsTranslationBundle",
                "(composer require willdurand/js-translation-bundle) and set config value ",
                "    doctrine_file_maker_driver.javascript_translations",
                "to true</comment>"]);
            return;
        }

        try {
            $secondaryInput = new ArrayInput([]);
            $command = $this->getApplication()->find('bazinga:js-translation:dump');
            $return = $command->run($secondaryInput, $output);
        } catch (CommandNotFoundException $e) {
            $output->writeln("<comment>To use translations in JavaScript you must install the " .
                "BazingaJsTranslationBundle</comment>");
            return;
        }

        if (0 == $return) {
            $output->writeln("<info>JavaScript translations updated</info>");
        }
    }

    private function updateFile($text, OutputInterface $output, string $type)
    {
        // perform the conversion from the messages strings file
        $en = $this->convertFromDB($text);

        $this->writeFile($en, $type, 'en');
        $output->writeln("<info>en messages translation updated</info>");

        // now update the other languages
        $this->updateOtherLanguages($output, $en, $type);
    }

    /**
     * Save the file to disk
     *
     * @param string $file
     * @param string $language
     * @return mixed|string
     */
    private function loadOrCreateFile($file, $language)
    {
        $qualifiedFile = $this->getTranslationsPath().DIRECTORY_SEPARATOR.$file;

        if (!file_exists($qualifiedFile)) {
            touch($qualifiedFile);
            $xml = $this->createEmptyXliffFile($language);

            $domxml = new \DOMDocument('1.0');
            $domxml->preserveWhiteSpace = false;
            $domxml->formatOutput = true;
            $domxml->loadXML($xml->asXML());
            $domxml->save($file);

            return $xml->asXML();
        }

        return $this->loadfile($file);
    }

    /**
     * Loads the requested file from the translations folder
     *
     * @param string $file Name of the file to load
     * @throws \Exception
     * @return string                                Content of the file
     */
    private function loadFile($file)
    {
        $file = $this->getTranslationsPath().DIRECTORY_SEPARATOR.$file;

        if (!file_exists($file)) {
            throw new \Exception(sprintf('Unable to open source file: %s', $file));
        }

        return file_get_contents($file);
    }

    /**
     * @param OutputInterface $output
     */
    private function clearCache(OutputInterface $output)
    {
        // and clear the cache
        $command = $this->getApplication()->find('cache:clear');
        $arguments = array(
            'command' => 'cache:clear',
            '--no-warmup'  => true,
        );
        $input = new ArrayInput($arguments);

        $return = $command->run($input, $output);
        if ($return == 0) {
            $output->writeln("<info>Cache cleared</info>");
        }

        // and then re-warm it
        $command = $this->getApplication()->find('cache:warm');
        $arguments = array(
            'command' => 'cache:warm'
        );
        $input = new ArrayInput($arguments);

        $return = $command->run($input, $output);
        if ($return == 0) {
            $output->writeln("<info>Cache warmed</info>");
        }
    }


    /**
     * Converts the DB records into the en XLIFF document
     * Because the default language for the app is English both the source
     * and the target are English
     *
     * @param array $contentArray       Array of data from the DB
     * @return \SimpleXMLElement        XLIF formatted content
     */
    private function convertFromDB($contentArray)
    {
        $xml = $this->createEmptyXliffFile("en");

        /** @var WebContent $trans */
        foreach($contentArray as $contentRow) {
            /** @noinspection PhpUndefinedFieldInspection
             * @var \SimpleXMLElement $trans */
            $trans = $xml->file->body->addChild('trans-unit');
            $trans->addAttribute('id', trim($contentRow->getId()));
            $trans->addAttribute('resname', trim($contentRow->getId()));
            $trans->addChild('source', trim($contentRow->getContent()));
            $trans->addChild('target', trim($contentRow->getContent()));
        }

        return $xml;
    }


    /**
     * Parses through other required languages to add newly created nodes
     * to their translation documents
     *
     * @param OutputInterface $output
     * @param \SimpleXMLElement $en
     * @param $type
     * @throws \Exception
     */
    private function updateOtherLanguages(OutputInterface $output, \SimpleXMLElement $en, $type)
    {
        // load each language file
        foreach ($this->languages as $lg) {
            $filename = "{$type}.{$lg}.xlf";
            $fileContents = $this->loadOrCreateFile($filename, $lg);
            $$lg = simplexml_load_string($fileContents);

            if (!($$lg instanceof \SimpleXMLElement)) {
                throw new \Exception(sprintf('File %s does not contain valid xml: %s', $filename, print_r($fileContents, true)));
            }
        }

        // parse through each node in the english document and add it if missing
        /** @noinspection PhpUndefinedFieldInspection */
        foreach ($en->file->body->{'trans-unit'} as $trans) {
            foreach ($this->languages as $lg) {
                if (!$this->nodeExists(${$lg}, $trans['id'])) {
                    /** @var \SimpleXMLElement $t */
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
     * @param \SimpleXMLElement $xml
     * @param string $id
     * @return boolean
     */
    private function nodeExists(\SimpleXMLElement $xml, $id)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        foreach ($xml->file->body->{'trans-unit'} as $trans) {
            if ((string)$trans['id'] == (string)$id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $language
     * @return \SimpleXMLElement
     */
    private function createEmptyXliffFile($language)
    {
        $xml = new \SimpleXMLElement('<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2"/>');
        $file = $xml->addChild('file');
        $file->addAttribute('source-language', "en");
        $file->addAttribute('target-language', $language);
        $file->addAttribute('datatype', "plaintext");
        $file->addAttribute('original', "file.ext");
        $file->addChild('body');

        return $xml;
    }


    /**
     * Saves an XLIF XML document to the correct messages file
     *
     * @param \SimpleXMLElement $xml XML to save
     * @param string $type Type of file to save (messages, routes etc)
     * @param string $locale Name of the locale
     * @throws \Exception
     */
    private function writeFile(\SimpleXMLElement $xml, $type, $locale)
    {
        $file = $this->getTranslationsPath().DIRECTORY_SEPARATOR."{$type}.{$locale}.xlf";
        if (!file_exists($file)) {
            $handle = fopen($file, 'w');
            fclose($handle);
        }
        if (!is_writable($file)) {
            throw new \Exception('Unable to open output file for writing');
        }

        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xml->asXML());
        $domxml->save($file);
    }


    /**
     * Detemrine the path to the translations folder
     *
     * @return string
     */
    private function getTranslationsPath()
    {
        $root = $this->getContainer()->get('kernel')->getRootdir();

        return $root.'/../vendor/matatirosoln/doctrine-filemaker-driver-bundle/Resources/translations';
    }

    /**
     * Load the content for the translation file from the DB
     */
    private function loadTextFromDB()
    {
        $em = $this->getContainer()->get('doctrine');
        return $em->getRepository('DoctrineFileMakerDriverBundle:WebContent')
            ->findAll();
    }

}
