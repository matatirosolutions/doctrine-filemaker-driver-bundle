<?php
/**
 * Created by PhpStorm.
 * User: stevewinter
 * Date: 13/04/2018
 * Time: 09:51
 */

namespace MSDev\DoctrineFileMakerDriverBundle\Twig;

use MSDev\DoctrineFileMakerDriverBundle\Service\ValuelistManager;

class TaxonomyExtension extends \Twig_Extension
{
    /**
     * @var ValuelistManager
     */
    protected $vlm;

    /** @var \Twig_Environment */
    protected $twig;

    /**
     * Constructor
     *
     * @param ValuelistManager $vlm
     */
    public function __construct(ValuelistManager $vlm)
    {
        $this->vlm = $vlm;
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'taxonomy_checkboxes',
                [$this, 'taxonomyCheckboxes'],
                [
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
                'taxonomy_radio',
                [$this, 'taxonomyRadioButtons'],
                [
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                ]
            ),
        );
    }

    /**
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName()
    {
        return 'dfmdb_taxonomy_selector';
    }

    /**
     * @param \Twig_Environment $environment
     * @param string $type          Either
     * @param string|array $list
     * @param array $options
     * @return string
     */
    public function taxonomyCheckboxes(\Twig_Environment $environment, string $type, $list, array $options)
    {
        $this->twig = $environment;
        return $this->renderTemplate('@DoctrineFileMakerDriver/taxonomy.html.twig', $type, $list, $options, 'checkbox');
    }

    public function taxonomyRadioButtons(\Twig_Environment $environment, string $type, $list, array $options)
    {
        $this->twig = $environment;
        return $this->renderTemplate('@DoctrineFileMakerDriver/taxonomy.html.twig', $type, $list, $options, 'radio');
    }


    private function renderTemplate(string $template, string $type, string $list, array $options, string $selector)
    {
        return $this->twig->render($template, [
            'selector' => $selector,
            'terms' => $this->getTerms($type, $list),
            'class' => 'taxonomy-term' . (isset($options['class']) ? ' '.$options['class'] : ''),
            'id' => isset($options['id']) ? $options['id'] : (isset($options['name']) ? $options['name'] : ''),
            'name' => isset($options['name']) ? $options['name'] : '',
            'selected' => isset($options['selected']) ? $options['selected'] : [],
            'disabled' => isset($options['disabled']) ? $options['disabled'] : false,
            'required' => isset($options['required']) ? $options['required'] : false,
            'data' => $this->setSelectDataAttributes($list, $options),
        ]);
    }


    private function getTerms($type, $data) {
        switch($type) {
            case 'valuelist':
                return $this->vlm->getValuelist($data);
            case 'static':
                return $data;
            default:
                return [];
        }
    }

    private function setSelectDataAttributes($data, array $opts)
    {
        $attr = [
            'taxonomy' => !is_array($data) ? $data : '',
            'live-search' => isset($opts['search']) ? $opts['search'] : 'true',
            'hide-disabled' => 'true',
            'title' => isset($opts['title']) ? $opts['title'] : 'Select',
            'clear-button' => true,
            'type' => isset($opts['type']) ? $opts['type'] : 'entry',
        ];
        if(!empty($opts['data'])) {
            $attr = array_merge($attr, $opts['data']);
        }

        return $attr;
    }
}