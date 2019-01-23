<?php

declare(strict_types=1);

namespace App\Git\ReadModel;

use Bitbucket\API\Api;
use Bitbucket\API\Http\Listener\BasicAuthListener;

/**
 * Class BitbucketClient.
 */
abstract class BitbucketClient
{
    /**
     * @var Api
     */
    private static $client;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    /**
     * BitbucketClient constructor.
     *
     * @param string $email
     * @param string $password
     */
    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * @param string $email
     * @param string $password
     */
    private function initClient(string $email, string $password): void
    {
        if (!self::$client) {
            self::$client = new Api();
            self::$client->getClient()->addListener(new BasicAuthListener($email, $password));
        }
    }

    /**
     * @return Api
     */
    protected function client(): Api
    {
        $this->initClient($this->email, $this->password);
        return self::$client;
    }
}
