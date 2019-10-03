<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 03/07/2017
 * Time: 14:30
 */

namespace MSDev\DoctrineFileMakerDriverBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ValuelistManager
{

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

        throw new InvalidConfigurationException("There is no valuelist named {$list}.");
    }


    public function loadValuelists()
    {
        if(empty($this->layout)) {
            throw new InvalidConfigurationException('No valuelist layout has been set in config.yml');
        }

        if('MSDev\DoctrineFMDataAPIDriver\FMConnection' == get_class($this->connection)) {
            return $this->loadDAPIValueLists();
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
            throw new InvalidConfigurationException("There is no valuelist named {$list}.");
        }

        foreach($vls[$list] as $term) {
            if($termId == $term['id']) {
                return $term['title'];
            }
        }

        throw new InvalidConfigurationException("Unable to find a term with ID {$termId} in list {$list}");
    }

    private function loadDAPIValueLists()
    {
        $lists = [];
        try {
            $resp = $this->connection->performFMRequest('GET', 'layouts/' . $this->layout, []);
            foreach ($resp['valueLists'] as $list) {
                $members = [];
                foreach ($list['values'] as $item) {
                    $members[] = [
                        'id' => $item['value'],
                        'title' => $item['displayValue']
                    ];
                }
                $lists[$list['name']] = $members;
            }
        } catch(\Exception $except) {}

        $this->session->set('valuelists', $lists);
    }
}