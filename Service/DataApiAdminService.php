<?php
declare(strict_types=1);

namespace MSDev\DoctrineFileMakerDriverBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MSDev\DoctrineFileMakerDriverBundle\Entity\WebContentInterface;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AdminAPIException;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AuthenticationException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;


class DataApiAdminService
{
    /** @var ?string */
    private $token;
    private $baseURI;

    /** @var bool */
    private $retried = false;

    /** @var  */
    private $parameters;

    public function __construct(ParameterBagInterface $parameters) {
        $this->parameters = $parameters;
    }

    public function __destruct()
    {
        $this->performLogout();
    }

    /**
     * @throws AdminAPIException|AuthenticationException
     */
    public function isDAPIEnabled(): bool
    {
        if (null === $this->token) {
            $this->performLogin();
        }

        $result = $this->performFMRequest('GET', '/fmdapi/config', []);
        return $result['enabled'];
    }

    /**
     * @throws AuthenticationException|AdminAPIException
     */
    public function setDAPIState(bool $enabled): bool
    {
        if(null === $this->token) {
            $this->performLogin();
        }
        $result = $this->performFMRequest('PATCH', '/fmdapi/config', [
            'body' => json_encode(['enabled' => $enabled])
        ]);
        return $result['enabled'];
    }

    /**
     * @throws AdminAPIException|AuthenticationException|TransportExceptionInterface
     */
    public function performFMRequest(string $method, string $uri, array $options): array
    {
        $client = HttpClient::create();
        $headers = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->token),
                'Accept-Encoding' => 'gzip, deflate, br',
            ]
        ];

        try {
            $response = $client->request($method, $this->baseURI.$uri, array_merge($headers, $options));
            $content = json_decode($response->getContent(), true);

            return $content['response']['data'] ?? $content['response'];
        } catch (ClientExceptionInterface $except) {
            if(null === $except->getResponse()) {
                throw new AdminAPIException($except->getMessage(), $except->getCode(), $except);
            }

            if(401 === $except->getResponse()->getStatusCode()) {
                if($this->retried) {
                    throw new AdminAPIException($except->getMessage(), $except->getCode(), $except);
                }

                $this->retried = true;
                $this->performLogin();
                return $this->performFMRequest($method, $uri, $options);
            }

            throw new AdminAPIException($except->getMessage());
        } catch (TransportExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $except) {
            throw new AdminAPIException($except->getMessage(), -1);
        }
    }

    /**
     * @throws AdminAPIException
     */
    public function connectToDAPI(EntityManagerInterface $entityManager): bool
    {
        $contentClass = $this->parameters->get('doctrine_file_maker_driver.content_class');
        try {
            if(null === $entityManager->getConnection()->getDatabasePlatform() ||
                'MSDev\DoctrineFMDataAPIDriver\FMPlatform' !== get_class($entityManager->getConnection()->getDatabasePlatform())
            ) {
                throw new AdminAPIException('Incorrect database platform');
            }

            /** @var WebContentInterface $record */
            $record = $entityManager->getRepository($contentClass)->findOneBy(['id' => '*']);
            if(null !== $record->getId()) {
                return true;
            }
        } catch (Exception | Throwable $exception) {
            if('Doctrine\DBAL\Exception' === get_class($exception)) {
                throw new AdminAPIException($exception->getPrevious()->getMessage());
            }
            throw new AdminAPIException($exception->getMessage());
        }

        return false;
    }

    /**
     * @throws AuthenticationException
     */
    private function performLogin(): void
    {
        $this->setBaseURI();
        $client = HttpClient::create();
        try {
            $response = $client->request('POST', $this->baseURI . '/user/auth', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'auth' => [
                    $this->parameters->get('doctrine_file_maker_driver.admin_username'),
                    $this->parameters->get('doctrine_file_maker_driver.admin_password')
                ]
            ]);

            $content = json_decode($response->getContent(), false);
            $this->token = $content->response->token;
        } catch (ClientExceptionInterface $e) {
            if(null === $e->getResponse()) {
                throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
            }

            if(404 === $e->getResponse()->getStatusCode()) {
                throw new AuthenticationException('Not found', $e->getResponse()->getStatusCode());
            }

            $content = json_decode($e->getResponse()->getContent(), false);
            throw new AuthenticationException($content->messages[0]->message, $content->messages[0]->code);
        } catch(TransportExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new AuthenticationException($e->getMessage(), -1);
        }
    }

    private function performLogout(): void
    {
        try {
            $this->performFMRequest('DELETE', '/user/auth/' . $this->token, []);
        } catch (AdminAPIException|AuthenticationException $e) { }
    }

    private function setBaseURI(): void
    {
        $host = $this->parameters->get('doctrine_file_maker_driver.admin_server');
        $port = $this->parameters->get('doctrine_file_maker_driver.admin_port');
        $this->baseURI =
            (strpos($host, 'http') === 0 ? $host : 'https://' . $host) .
            (443 === $port ? '' : ":$port") .
            ('/' === substr($host, -1) ? '' : '/') .
            'fmi/admin/api/v2';
    }

}
