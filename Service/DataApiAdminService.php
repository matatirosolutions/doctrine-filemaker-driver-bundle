<?php
declare(strict_types=1);

namespace MSDev\DoctrineFileMakerDriverBundle\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AdminAPIException;
use MSDev\DoctrineFileMakerDriverBundle\Exception\AuthenticationException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


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
     * @throws AdminAPIException|AuthenticationException
     */
    public function performFMRequest(string $method, string $uri, array $options): array
    {
        $client = new Client();
        $headers = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->token),
                'Accept-Encoding' => 'gzip, deflate, br',
            ]
        ];

        try {
            $response = $client->request($method, $this->baseURI.$uri, array_merge($headers, $options));
            $content = json_decode($response->getBody()->getContents(), true);

            return $content['response']['data'] ?? $content['response'];
        } catch (Exception $e) {
            /** @var ClientException $e */
            if(null === $e->getResponse()) {
                throw new AdminAPIException($e->getMessage(), $e->getCode(), $e);
            }

            if(401 === $e->getResponse()->getStatusCode()) {
                if($this->retried) {
                    throw new AdminAPIException($e->getMessage(), $e->getCode(), $e);
                }

                $this->retried = true;
                $this->performLogin();
                return $this->performFMRequest($method, $uri, $options);
            }

            throw new AdminAPIException($e->getMessage());
        } catch(GuzzleException $e) {
            throw new AdminAPIException($e->getMessage(), -1);
        }
    }

    /**
     * @throws AuthenticationException
     */
    private function performLogin(): void
    {
        $this->setBaseURI();
        $client = new Client();
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

            $content = json_decode($response->getBody()->getContents(), false);
            $this->token = $content->response->token;
        } catch (Exception $e) {
            /** @var ClientException $e */
            if(null === $e->getResponse()) {
                throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
            }

            if(404 === $e->getResponse()->getStatusCode()) {
                throw new AuthenticationException($e->getResponse()->getReasonPhrase(), $e->getResponse()->getStatusCode());
            }

            $content = json_decode($e->getResponse()->getBody()->getContents(), false);
            throw new AuthenticationException($content->messages[0]->message, $content->messages[0]->code);
        } catch(GuzzleException $e) {
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
