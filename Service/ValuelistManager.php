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
}