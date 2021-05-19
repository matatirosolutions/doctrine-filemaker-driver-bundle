<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Twig;

use MSDev\DoctrineFileMakerDriverBundle\Service\ValuelistManager;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SelectExtension extends AbstractExtension
{

    /**
     * @var ValuelistManager
     */
    protected $vlm;

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
            new TwigFunction(
                'render_select',
                [$this, 'renderSelect'],
                [
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction(
                'render_combobox',
                array($this, 'renderCombo'),
                array(
                    'is_safe' => array('html'),
                )
            ),
            new TwigFunction(
                'render_yes_no_na',
                array($this, 'renderYesNoNa'),
                array(
                    'is_safe' => array('html'),
                )
            ),
            new TwigFunction(
                'render_rag',
                array($this, 'renderRAG'),
                array(
                    'is_safe' => array('html'),
                )
            ),
        );
    }

    /**
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName()
    {
        return 'dfmdb_render_select';
    }

    /**
     *
     */
    public function renderSelect(Environment $environment, $type, $data, $opts = array())
    {
        return $environment->render(
            "@DoctrineFileMakerDriver/select.html.twig",
            array(
                'terms' => $this->getTerms($type, $data),
                'class' => isset($opts['class']) ? $opts['class'] : 'selectpicker',
                'id' => isset($opts['id']) ? $opts['id'] : (isset($opts['name']) ? $opts['name'] : ''),
                'name' => isset($opts['name']) ? $opts['name'] : '',
                'selected' => isset($opts['selected']) ? $opts['selected'] : array(),
                'disabled' => isset($opts['disabled']) ? $opts['disabled'] : false,
                'required' => isset($opts['required']) ? $opts['required'] : false,
                'data' => $this->setSelectDataAttributes($data, $opts),
            )
        );
    }

    public function renderCombo(Environment $environment, $type, $data, $opts = array())
    {
        return $environment->render(
            "@DoctrineFileMakerDriver/combobox.html.twig",
            array(
                'terms' => $this->getTerms($type, $data),
                'class' => isset($opts['class']) ? $opts['class'] : '',
                'id' => isset($opts['id']) ? $opts['id'] : (isset($opts['name']) ? $opts['name'] : ''),
                'name' => isset($opts['name']) ? $opts['name'] : '',
                'selected' => isset($opts['selected']) ? $opts['selected'] : array(),
                'disabled' => isset($opts['disabled']) ? $opts['disabled'] : false,
                'data' => $this->setSelectDataAttributes($data, $opts),
            )
        );
    }

    public function renderYesNoNa(Environment $environment, $opts = array())
    {
        return $environment->render(
            "@DoctrineFileMakerDriver/yes-no-na.html.twig",
            array(
                'class' => isset($opts['class']) ? $opts['class'] : '',
                'id' => isset($opts['id']) ? $opts['id'] : (isset($opts['name']) ? $opts['name'] : ''),
                'name' => isset($opts['name']) ? $opts['name'] : '',
                'selected' => isset($opts['selected']) ? $opts['selected'] : array(),
                'na' => isset($opts['show-na']) ? $opts['show-na'] : true,
                'disabled' => isset($opts['disabled']) ? $opts['disabled'] : false,
            )
        );
    }

    public function renderRAG(Environment $environment, $opts = array())
    {
        return $environment->render(
            "@DoctrineFileMakerDriver/rag.html.twig",
            array(
                'class' => isset($opts['class']) ? $opts['class'] : '',
                'id' => isset($opts['id']) ? $opts['id'] : (isset($opts['name']) ? $opts['name'] : ''),
                'name' => isset($opts['name']) ? $opts['name'] : '',
                'selected' => isset($opts['selected']) ? $opts['selected'] : array(),
                'disabled' => isset($opts['disabled']) ? $opts['disabled'] : false,
                'red' => isset($opts['red']) ? $opts['red'] : 'Red',
                'amber' => isset($opts['amber']) ? $opts['amber'] : 'Amber',
                'green' => isset($opts['green']) ? $opts['green'] : 'Green',
            )
        );
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