<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 03/07/2017
 * Time: 14:30
 */

namespace MSDev\DoctrineFileMakerDriverBundle\Service;

use Doctrine\DBAL\Connection;
use MSDev\DoctrineFileMakerDriver\FMConnection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use \FileMaker;

class ValuelistManager
{
    /**
     * @var FMConnection
     */
    private $connection;

    /**
     * @var FileMaker
     */
    private $fm;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $layout = '';

    /**
     * ValuelistService constructor.
     */
    public function __construct(Connection $connection, SessionInterface $session)
    {
        $this->connection = $connection->getWrappedConnection();
        $this->fm = $this->connection->getConnection();
        $this->session = $session;
    }

    public function setValuelistLayout($layout)
    {
        $this->layout = $layout;
    }


    public function getValuelist(string $list)
    {
        if(empty($this->session->get('valuelists'))) {
            $this->loadValuelists();
        }

        $vls = $this->session->get('valuelists');
        if(array_key_exists($list, $vls)) {
            return $vls[$list];
        }

        throw new InvalidConfigurationException('There is no valuelist of that name');
    }


    public function loadValuelists()
    {
        if(empty($this->layout)) {
            throw new InvalidConfigurationException('No valuelist layout has been set in config.yml');
        }

        $layout = $this->fm->getLayout($this->layout);
        if($layout instanceof \FileMaker_Error) {
            return;
        }

        $vls = $layout->getValueListsTwoFields();
        $lists = [];
        foreach($vls as $name => $values) {
            $list = [];
            foreach($values as $title => $key) {
                $list[] = [
                    'id' => $key,
                    'title' => $title
                ];
            }
            $lists[$name] = $list;
        }

        $this->session->set('valuelists', $lists);
    }

    /**
     * @param string $list
     * @param string $termId
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getTermTitleByIdFromList(string $termId, string $list)
    {
        if(empty($this->session->get('valuelists'))) {
            $this->loadValuelists();
        }

        $vls = $this->session->get('valuelists');
        if(!array_key_exists($list, $vls)) {
            throw new InvalidConfigurationException('There is no valuelist of that name.');
        }

        foreach($vls[$list] as $term) {
            if($termId == $term['id']) {
                return $term['title'];
            }
        }

        throw new InvalidConfigurationException("Unable to find a term with ID {$termId} in list {$list}");
    }
}