<?php
namespace Wx\Grant;

use App\Criteria\HasFieldCriteria;
use App\Repositories\UserRepository;
use Faker\Provider\Uuid;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Wx\Code;
use Wx\WxAuth;

class WeChatOpenIdGrant extends AbstractGrant{
    protected $userRepository;

    /**
     * WeChatOpenIdGrant constructor.
     * @param UserRepository $userRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(UserRepository $userRepository,RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->userRepository = $userRepository;
        $this->setRefreshTokenRepository($refreshTokenRepository);
    }


    /**
     * Return the grant identifier that can be used in matching up requests.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'wechat_openid';
    }

    /**
     * Respond to an incoming request.
     *
     * @param ServerRequestInterface $request
     * @param ResponseTypeInterface $responseType
     * @param \DateInterval $accessTokenTTL
     *
     * @return ResponseTypeInterface
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        \DateInterval $accessTokenTTL
    )
    {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));
        $user = $this->validateUser($request, $client);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getKey());

        // Issue and persist new tokens
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getKey(), $finalizedScopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        // Inject tokens into response
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        return $responseType;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface  $client
     *
     * @throws OAuthServerException
     *
     * @return UserEntityInterface
     */
    //验证用户数据
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        //请求验证
        $code = $this->getRequestParameter('code', $request);
        if (is_null($code)) {
            throw OAuthServerException::invalidRequest('code');
        }

        $ed = $this->getRequestParameter('ed', $request);
        if (is_null($ed)) {
            throw OAuthServerException::invalidRequest('ed');
        }

        $iv = $this->getRequestParameter('iv', $request);
        if (is_null($iv)) {
            throw OAuthServerException::invalidRequest('iv');
        }

        //获取微信用户信息
        $userData=WxAuth::getUserInfo($code,$ed,$iv,$data);
        if ($userData !==Code::$OK){    //如果没有获取到成功代码，抛出异常
            throw OAuthServerException::invalidCredentials();
        }

        //判断该微信用户是否存在于用户数据表中
        $user = $this->userRepository
            ->pushCriteria(new HasFieldCriteria('weixin_open_id',$data->openId))
            ->first();

        //如果该用户不存在，创建用户信息
        if (!$user){
            $user=$this->userRepository->create([
                'name'=>Uuid::uuid(),     //得到一个随机的用户名
                'nickname'=>$data->nickName,
                'weixin_open_id'=>$data->openId,
                'password'=>'$10$mHQxuoZa9Qec7b5Qdk1UyuVAARRFWNFTBDzom97BSNsUa6o/NR8SK',
                'head'=>$data->avatarUrl,
                'gender'=>$data->gender
            ]);
        }

        return $user;
    }
}