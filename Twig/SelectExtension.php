<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Twig;

use MSDev\DoctrineFileMakerDriverBundle\Exception\LayoutNotDefined;
use MSDev\DoctrineFileMakerDriverBundle\Exception\ValueListNotFound;
use MSDev\DoctrineFileMakerDriverBundle\Service\ValuelistManager;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
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
    public function getFunctions(): array
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
                    'needs_environment' => true,
                )
            ),
            new TwigFunction(
                'render_yes_no_na',
                array($this, 'renderYesNoNa'),
                array(
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                )
            ),
            new TwigFunction(
                'render_rag',
                array($this, 'renderRAG'),
                array(
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                )
            ),
        );
    }

    /**
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName(): string
    {
        return 'dfmdb_render_select';
    }


    /**
     * @throws SyntaxError
     * @throws LayoutNotDefined
     * @throws RuntimeError
     * @throws LoaderError
     * @throws ValueListNotFound
     */
    public function renderSelect(Environment $environment, $type, $data, $opts = array()): string
    {
        $terms = $this->getTerms($type, $data);
        if(isset($opts['exclude']) && is_array($opts['exclude'])) {
            foreach($opts['exclude'] as $exclude) {
                $key = array_search($exclude, array_column($terms, 'title'));
                if($key !== false) {
                    unset($terms[$key]);
                }
            }
        }

        return $environment->render(
            "@DoctrineFileMakerDriver/select.html.twig",
            array(
                'terms' => $terms,
                'class' => $opts['class'] ?? 'selectpicker',
                'id' => $opts['id'] ?? ($opts['name'] ?? ''),
                'name' => $opts['name'] ?? '',
                'selected' => $opts['selected'] ?? array(),
                'disabled' => $opts['disabled'] ?? false,
                'required' => $opts['required'] ?? false,
                'multiple' => $opts['multiple'] ?? false,
                'data' => $this->setSelectDataAttributes($data, $opts),
            )
        );
    }

    /**
     * @throws SyntaxError
     * @throws LayoutNotDefined
     * @throws RuntimeError
     * @throws LoaderError
     * @throws ValueListNotFound
     */
    public function renderCombo(Environment $environment, $type, $data, $opts = array()): string
    {
        return $environment->render(
            "@DoctrineFileMakerDriver/combobox.html.twig",
            array(
                'terms' => $this->getTerms($type, $data),
                'class' => $opts['class'] ?? '',
                'id' => $opts['id'] ?? ($opts['name'] ?? ''),
                'name' => $opts['name'] ?? '',
                'selected' => $opts['selected'] ?? array(),
                'disabled' => $opts['disabled'] ?? false,
                'required' => $opts['required'] ?? false,
                'multiple' => $opts['multiple'] ?? false,
                'data' => $this->setSelectDataAttributes($data, $opts),
            )
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function renderYesNoNa(Environment $environment, $opts = array()): string
    {
        return $environment->render(
            "@DoctrineFileMakerDriver/yes-no-na.html.twig",
            array(
                'class' => $opts['class'] ?? '',
                'id' => $opts['id'] ?? ($opts['name'] ?? ''),
                'name' => $opts['name'] ?? '',
                'selected' => $opts['selected'] ?? array(),
                'na' => $opts['show-na'] ?? true,
                'disabled' => $opts['disabled'] ?? false,
            )
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function renderRAG(Environment $environment, $opts = array()): string
    {
        return $environment->render(
            "@DoctrineFileMakerDriver/rag.html.twig",
            array(
                'class' => $opts['class'] ?? '',
                'id' => $opts['id'] ?? ($opts['name'] ?? ''),
                'name' => $opts['name'] ?? '',
                'selected' => $opts['selected'] ?? array(),
                'disabled' => $opts['disabled'] ?? false,
                'red' => $opts['red'] ?? 'Red',
                'amber' => $opts['amber'] ?? 'Amber',
                'green' => $opts['green'] ?? 'Green',
            )
        );
    }


    /**
     * @throws LayoutNotDefined
     * @throws ValueListNotFound
     */
    private function getTerms($type, $data): array
    {
        switch($type) {
            case 'valuelist':
                return $this->vlm->getValuelist($data);
            case 'static':
                return $data;
            default:
                return [];
        }
    }

    private function setSelectDataAttributes($data, array $opts): array
    {
        $attr = [
            'taxonomy' => !is_array($data) ? $data : '',
            'live-search' => $opts['search'] ?? 'true',
            'hide-disabled' => 'true',
            'title' => $opts['title'] ?? 'Select',
            'clear-button' => true,
            'type' => $opts['type'] ?? 'entry',
        ];
        if(!empty($opts['data'])) {
            $attr = array_merge($attr, $opts['data']);
        }

        return $attr;
    }

}
