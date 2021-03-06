<?php

namespace Botonomous\client;

use Botonomous\ImChannel;
use Botonomous\Team;
use /* @noinspection PhpUndefinedClassInspection */
    GuzzleHttp\Psr7\Request;

/**
 * Class ApiClient.
 */
class ApiClient extends AbstractClient
{
    const BASE_URL = 'https://slack.com/api/';
    const CONTENT_TYPE = 'application/x-www-form-urlencoded';

    private $arguments = [
        'rtm.start' => [
            'required' => [
                'token',
            ],
            'optional' => [
                'simple_latest',
                'no_unreads',
                'mpim_aware',
            ],
        ],
        'chat.postMessage' => [
            'required' => [
                'token',
                'channel',
                'text',
            ],
            'optional' => [
                'parse',
                'link_names',
                'attachments',
                'unfurl_links',
                'unfurl_media',
                'username',
                'as_user',
                'icon_url',
                'icon_emoji',
            ],
        ],
        'oauth.access' => [
            'required' => [
                'client_id',
                'client_secret',
                'code',
            ],
            'optional' => [
                'redirect_uri',
            ],
        ],
        'team.info' => [
            'required' => [
                'token',
            ],
        ],
        'im.list' => [
            'required' => [
                'token',
            ],
        ],
        'users.list' => [
            'required' => [
                'token',
            ],
            'optional' => [
                'presence',
            ],
        ],
        'users.info' => [
            'required' => [
                'token',
                'user',
            ],
        ],
    ];

    private $token;

    /**
     * ApiClient constructor.
     *
     * @param null $token
     */
    public function __construct($token = null)
    {
        $this->setToken($token);
    }

    /**
     * API CURL Call with post method.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function apiCall($method, array $arguments = [])
    {
        try {
            $requestBody = $this->prepareRequestBody($method, $arguments);
            $response = $this->sendRequest($method, $requestBody);

            return $this->processResponse($response);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $method
     * @param $requestBody
     *
     * @throws \Exception
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    private function sendRequest($method, $requestBody)
    {
        try {
            /** @noinspection PhpUndefinedClassInspection */
            $request = new Request(
                'POST',
                self::BASE_URL.$method,
                ['Content-Type' => self::CONTENT_TYPE],
                $requestBody
            );

            return $this->getClient()->send($request);
        } catch (\Exception $e) {
            throw new \Exception('Failed to send data to the Slack API: '.$e->getMessage());
        }
    }

    /**
     * @param $method
     * @param array $arguments
     *
     * @throws \Exception
     *
     * @return string
     */
    private function prepareRequestBody($method, array $arguments = [])
    {
        $arguments = array_merge($arguments, $this->getArgs());

        // check the required arguments are provided
        try {
            $this->validateRequiredArguments($method, $arguments);
        } catch (\Exception $e) {
            throw new \Exception('Missing required argument(s): '.$e->getMessage());
        }

        // filter unwanted arguments
        return http_build_query($this->filterArguments($method, $arguments));
    }

    /**
     * @param $response
     *
     * @throws \Exception
     *
     * @return mixed
     */
    private function processResponse($response)
    {
        $response = json_decode($response->getBody()->getContents(), true);

        if (!is_array($response)) {
            throw new \Exception('Failed to process response from the Slack API');
        }

        return $response;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function getArgs()
    {
        return [
            'token'    => $this->getToken(),
            'username' => $this->getConfig()->get('botUsername'),
            'as_user'  => $this->getConfig()->get('asUser'),
            'icon_url' => $this->getConfig()->get('iconURL'),
        ];
    }

    /**
     * @param $arguments
     *
     * @return mixed
     */
    public function chatPostMessage($arguments)
    {
        return $this->apiCall('chat.postMessage', $arguments);
    }

    /**
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function rtmStart($arguments)
    {
        return $this->apiCall('rtm.start', $arguments);
    }

    /**
     * @throws \Exception
     *
     * @return array
     * @return Team
     */
    public function teamInfo()
    {
        $teamInfo = $this->apiCall('team.info');

        if (!isset($teamInfo['team'])) {
            return [];
        }

        return $teamInfo['team'];
    }

    /**
     * @return null|\Botonomous\AbstractBaseSlack
     */
    public function teamInfoAsObject()
    {
        $teamInfo = $this->teamInfo();

        if (empty($teamInfo)) {
            /* @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        // return as object
        return (new Team())->load($teamInfo);
    }

    /**
     * List all the Slack users in the team.
     *
     * @return array
     */
    public function usersList()
    {
        $result = $this->apiCall('users.list');

        if (!isset($result['members'])) {
            return [];
        }

        return $result['members'];
    }

    /**
     * Return a user by Slack user id.
     *
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function userInfo($arguments)
    {
        $result = $this->apiCall('users.info', $arguments);

        if (!isset($result['user'])) {
            /* @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        return $result['user'];
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function test()
    {
        return $this->apiCall('api.test');
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function imList()
    {
        $result = $this->apiCall('im.list');

        if (!isset($result['ims'])) {
            return [];
        }

        return $result['ims'];
    }

    /**
     * @return array
     */
    public function imListAsObject()
    {
        $imChannels = $this->imList();

        $imChannelObjects = [];
        if (empty($imChannels)) {
            return $imChannelObjects;
        }

        foreach ($imChannels as $imChannel) {
            $imChannelObjects[$imChannel['id']] = (new ImChannel())->load($imChannel);
        }

        return $imChannelObjects;
    }

    /**
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function oauthAccess($arguments)
    {
        return $this->apiCall('oauth.access', $arguments);
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return bool
     */
    private function validateRequiredArguments($method, $arguments)
    {
        $validArguments = $this->getArguments($method);

        if (empty($validArguments['required'])) {
            return true;
        }

        foreach ($validArguments['required'] as $argument) {
            if ($this->getArrayUtility()->arrayKeyValueExists($argument, $arguments) !== true) {
                throw new \Exception("{$argument} must be provided for {$method}");
            }
        }

        return true;
    }

    /**
     * @param null $method
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function getArguments($method = null)
    {
        if ($method !== null) {
            if (!isset($this->arguments[$method])) {
                /* @noinspection PhpInconsistentReturnPointsInspection */
                return;
            }

            return $this->arguments[$method];
        }

        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param       $method
     * @param array $arguments
     *
     * @return array
     */
    public function filterArguments($method, array $arguments)
    {
        $validArguments = $this->getArguments($method);

        if (empty($validArguments)) {
            return $arguments;
        }

        if (!isset($validArguments['optional'])) {
            $validArguments['optional'] = [];
        }

        $extractedArguments = array_merge($validArguments['required'], $validArguments['optional']);

        return $this->getArrayUtility()->filterArray($arguments, $extractedArguments);
    }

    /**
     * @return string
     */
    public function getToken()
    {
        // fall back to config
        if (empty($this->token)) {
            $this->setToken($this->getConfig()->get('botUserToken'));
        }

        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}
