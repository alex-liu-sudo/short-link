<?php
/**
 * Created by PhpStorm.
 * User: AlexLiu
 * Date: 2019-09-02
 * Time: 16:27
 */
namespace Alex\ShortLink;

use Alex\ShortLink\Contract\ClientInterface;
use GuzzleHttp\Client as RequestClient;
use Predis\Client as Redis;
use Illuminate\Support\Arr;
use function GuzzleHttp\json_decode;

class Client implements ClientInterface
{
    /**
     * 短链接.
     *
     * @var
     */
    public $shortUrl = '';

    /**
     * 源链接.
     *
     * @var
     */
    public $sourceUrl;

    /**
     * @var string
     */
    protected $accessKey = 'wechat.short_link.access_token';

    /**
     * 请求实例.
     *
     * @var
     */
    protected $requestClient;

    /**
     * @var array
     */
    protected $allowType = ['wx', 'sina'];

    /**
     * 配置.
     *
     * @var
     */
    private $config;

    /**
     * Client constructor.
     *
     * @param $config
     */
    public function __construct($config = null)
    {
        $this->config = is_array($config) ? $config : config('short-link');
    }

    /**
     * @param $sourceUrl
     * @return mixed
     * @throws \Exception
     */
    public function getShortUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;

        $this->setShortUrl();

        return $this->shortUrl;
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function setShortUrl()
    {
        $method = $this->getMethod();

        $this->$method();
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setWxShortUrl()
    {
        $apiUrl = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token=%s";
        $accessToken = $this->getAccessToken();

        $url = sprintf($apiUrl, $accessToken);

        $client = $this->getRequestClient();

        $response = $client->request('POST', $url, [
            'json' => [
                'access_token' => $accessToken,
                'action' => 'long2short',
                'long_url' => $this->sourceUrl
            ]
        ]);

        $urlJson = $response->getBody()->getContents();

        $shortUrl = json_decode($urlJson, 1);

        return $this->shortUrl = Arr::get($shortUrl, 'short_url');
    }

    /**
     * 获取微信AccessToken.
     *
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccessToken()
    {
        $redisClient = $this->getRedisClient();

        if ($redisClient->exists($this->accessKey)) return $redisClient->get($this->accessKey);

        $apiUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s";

        $client = $this->getRequestClient();

        $url = sprintf($apiUrl, $this->config['wx_appid'], $this->config['wx_secret']);

        try {
            $response = $client->request('get', $url);

            if ($response->getStatusCode() != 200) {
                throw new \Exception('异常请求');
            }

            $json = $response->getBody()->getContents();

            $result = json_decode($json, 1);

            if (isset($result['access_token'])) {

                $redisClient->setex($this->accessKey, 7100, $result['access_token']);

                return $result['access_token'];
            }

            return false;

        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }

    }

    /**
     * @return RequestClient
     */
    private function getRequestClient()
    {
        if ($this->requestClient) {
            return $this->requestClient;
        }

        return $this->requestClient = new RequestClient();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getMethod()
    {
        $type = $this->config['type'];

        if (!in_array($type, $this->allowType)) {
            throw new \Exception('不支持的短链接类型.');
        }
        switch ($type) {
            case 'wx':
                $method = 'setWxShortUrl';
                break;
            case 'sina':
                $method = 'setSinaShortUrl';
                break;
            default:
                $method = 'setWxShortUrl';
        }

        return $method;
    }

    /**
     * @return Redis
     */
    public function getRedisClient()
    {
        return new Redis();
    }
}
