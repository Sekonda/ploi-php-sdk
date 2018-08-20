<?php

namespace Ploi\Resources\Server;

use Ploi\Exceptions\Http\InternalServerError;
use Ploi\Exceptions\Http\NotFound;
use Ploi\Exceptions\Http\NotValid;
use Ploi\Exceptions\Http\PerformingMaintenance;
use Ploi\Exceptions\Http\TooManyAttempts;
use Ploi\Exceptions\Resource\Server\Site\DomainAlreadyExists;
use Ploi\Http\Response;
use Ploi\Resources\Resource;
use stdClass;

/**
 * Class Site
 *
 * @package Ploi\Resources\Server
 */
class Site extends Resource
{
    /**
     * @var Server
     */
    private $server;

    /**
     * Site constructor.
     *
     * @param Server   $server
     * @param int|null $id
     */
    public function __construct(Server $server, int $id = null)
    {
        parent::__construct($server->getPloi(), $id);

        $this->setServer($server);

        // Build the endpoint
        $this->buildEndpoint();
    }

    /**
     * Returns either a single site or an array of sites
     *
     * @param int|null $id
     * @return null|Response
     * @throws \Ploi\Exceptions\Http\InternalServerError
     * @throws \Ploi\Exceptions\Http\NotFound
     * @throws \Ploi\Exceptions\Http\NotValid
     * @throws \Ploi\Exceptions\Http\PerformingMaintenance
     * @throws \Ploi\Exceptions\Http\TooManyAttempts
     */
    public function get(int $id = null)
    {
        if ($id) {
            $this->setId($id);
        }

        // Make sure the endpoint is built
        $this->buildEndpoint();

        return $this->getPloi()->makeAPICall($this->getEndpoint());
    }

    /**
     * Gets the server
     *
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * Sets the server
     *
     * @param Server $server
     * @return Site
     */
    public function setServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Builds the endpoint out based over the server's endpoint and id
     *
     * @return Site
     */
    public function buildEndpoint(): self
    {
        $this->setEndpoint($this->getServer()->getEndpoint() . '/' . $this->getServer()->getId() . '/sites');

        if ($this->getId()) {
            $this->setEndpoint($this->getEndpoint() . '/' . $this->getId());
        }

        return $this;
    }

    public function create($domain, $webDirectory = '/public', $projectRoot = '/')
    {
        // Remove the id
        $this->setId(null);

        // Set the options
        $options = [
            'body' => json_encode([
                'root_domain'   => $domain,
                'web_directory' => $webDirectory,
                'project_root'  => $projectRoot,
            ]),
        ];

        // Build the endpoint
        $this->buildEndpoint();

        // Make the request
        try {
            $response = $this->getPloi()->makeAPICall($this->getEndpoint(), 'post', $options);
        } catch (NotValid $exception) {
            $errors = json_decode($exception->getMessage())->errors;

            if (!empty($errors->root_domain)
                && $errors->root_domain[0] === "The root domain has already been taken.") {
                throw new DomainAlreadyExists($domain . ' already exists!');
            }

            return $exception;
        }

        return $response->getJson()->data;
    }

    /**
     * @param int|null $id
     * @return stdClass
     * @throws InternalServerError
     * @throws NotFound
     * @throws NotValid
     * @throws PerformingMaintenance
     * @throws TooManyAttempts
     * @throws \Exception
     */
    public function delete(int $id = null): ?bool
    {
        if ($id) {
            $this->setId($id);
        }

        $this->buildEndpoint();

        $response = $this->getPloi()->makeAPICall($this->getEndpoint(), 'delete');

        if ($response->getResponse()->getStatusCode() === 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deploys a website
     *
     * @param int|null $id
     * @return bool
     * @throws InternalServerError
     * @throws NotFound
     * @throws NotValid
     * @throws PerformingMaintenance
     * @throws TooManyAttempts
     */
    public function deploy(int $id = null): bool
    {
        if ($id) {
            $this->setId($id);
        }

        // Build the default endpoint
        $this->buildEndpoint();

        // Deploy endpoint
        $this->setEndpoint($this->getEndpoint() . '/deploy');

        // Send the request
        $response = $this->getPloi()->makeAPICall($this->getEndpoint(), 'post');

        if ($response->getResponse()->getStatusCode() === 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the deploy script for a website
     *
     * @param int|null $id
     * @return null|string
     * @throws InternalServerError
     * @throws NotFound
     * @throws NotValid
     * @throws PerformingMaintenance
     * @throws TooManyAttempts
     */
    public function getDeployScript(int $id = null): ?string
    {
        if ($id) {
            $this->setId($id);
        }

        // Make sure the endpoint is built
        $this->buildEndpoint();
        $this->setEndpoint($this->getEndpoint() . '/deploy/script');

        $response = $this->getPloi()->makeAPICall($this->getEndpoint());

        if (!empty($response->getJson()->deploy_script)) {
            return $response->getJson()->deploy_script;
        }

        return null;
    }

    /**
     * Updates the deploy script for a website
     *
     * @param string   $script
     * @param int|null $id
     * @return bool
     * @throws InternalServerError
     * @throws NotFound
     * @throws NotValid
     * @throws PerformingMaintenance
     * @throws TooManyAttempts
     */
    public function updateDeployScript(string $script, int $id = null): bool
    {
        if ($id) {
            $this->setId($id);
        }

        // Make sure the endpoint is built
        $this->buildEndpoint();
        $this->setEndpoint($this->getEndpoint() . '/deploy/script');

        // Set the options
        $options = [
            'body' => json_encode([
                'deploy_script' => $script,
            ]),
        ];

        $response = $this->getPloi()->makeAPICall($this->getEndpoint(), 'patch', $options);

        return $response->getResponse()->getStatusCode() === 200 ? true : false;
    }
}