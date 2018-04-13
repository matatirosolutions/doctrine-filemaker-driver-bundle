<?php
/**
 * Created by PhpStorm.
 * User: stevewinter
 * Date: 13/04/2018
 * Time: 09:51
 */

namespace MSDev\DoctrineFileMakerDriverBundle\Twig;

use MSDev\DoctrineFileMakerDriverBundle\Service\ValuelistManager;

class ValueListExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction(
                'valuelist_term',
                [$this, 'valuelistTerm'],
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
        return 'dfmdb_access_valuelist';
    }

    public function valuelistTerm(\Twig_Environment $environment, string $termId, string $list)
    {
        return $this->vlm->getTermTitleByIdFromList($termId, $list);
    }
}