<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 03/07/2017
 * Time: 14:30
 */

namespace MSDev\DoctrineFileMakerDriverBundle\Service;

use Doctrine\DBAL\Connection;
use MSDev\DoctrineFileMakerDriverBundle\Exception\LayoutNotDefined;
use MSDev\DoctrineFileMakerDriverBundle\Exception\TermNotFound;
use MSDev\DoctrineFileMakerDriverBundle\Exception\ValueListNotFound;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class ValuelistManager
{
    /** @var Connection  */
    private $connection;

    /** @var SessionInterface */
    private $session;

    /** @var string */
    private $layout = '';


    public function __construct(Connection $connection, SessionInterface $session)
    {
        $this->connection = $connection->getWrappedConnection();
        $this->session = $session;
    }

    public function setValuelistLayout($layout): void
    {
        $this->layout = $layout;
    }

    /**
     * @param string $list
     *
     * @return array
     *
     * @throws ValueListNotFound
     * @throws LayoutNotDefined
     */
    public function getValuelist(string $list): array
    {
        if(empty($this->session->get('valuelists'))) {
            $this->loadValuelists();
        }

        $vls = $this->session->get('valuelists');
        if(array_key_exists($list, $vls)) {
            return $vls[$list];
        }

        throw new ValueListNotFound($list);
    }

    /**
     * @throws LayoutNotDefined
     */
    public function loadValuelists(): void
    {
        if(empty($this->layout)) {
            throw new LayoutNotDefined('No valuelist layout has been set in config.yml');
        }

        if('MSDev\DoctrineFMDataAPIDriver\FMConnection' === get_class($this->connection)) {
            $this->loadDAPIValueLists();

            return;
        }

        $this->loadCWPValueLists();
    }

    /**
     * @param string $list
     * @param string $termId
     *
     * @return string
     * @throws
     */
    public function getTermTitleByIdFromList(string $termId, string $list): string
    {
        if(empty($this->session->get('valuelists'))) {
            $this->loadValuelists();
        }

        $vls = $this->session->get('valuelists');
        if(!array_key_exists($list, $vls)) {
            throw new ValueListNotFound($list);
        }

        foreach($vls[$list] as $term) {
            if($termId === $term['id']) {
                return $term['title'];
            }
        }

        throw new TermNotFound($list, $termId);
    }

    private function loadCWPValueLists(): void
    {
        $lists = [];
        $fm = $this->connection->getConnection();
        $layout = $fm->getLayout($this->layout);
        if ($layout instanceof \FileMaker_Error) {
            $this->session->set('valuelists', $lists);
            return;
        }

        $vls = $layout->getValueListsTwoFields();
        foreach ($vls as $name => $values) {
            $list = [];
            foreach ($values as $title => $key) {
                $list[] = [
                    'id' => $key,
                    'title' => $title
                ];
            }
            $lists[$name] = $list;
        }

        $this->session->set('valuelists', $lists);
    }

    private function loadDAPIValueLists(): void
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