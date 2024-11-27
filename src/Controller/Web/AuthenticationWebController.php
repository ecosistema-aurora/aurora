<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Service\Interface\UserServiceInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use League\OAuth2\Client\Token\AccessToken as AccessTokenLib;
use Mainick\KeycloakClientBundle\DTO\KeycloakAuthorizationCodeEnum;
use Mainick\KeycloakClientBundle\DTO\UserRepresentationDTO;
use Mainick\KeycloakClientBundle\Interface\AccessTokenInterface;
use Mainick\KeycloakClientBundle\Token\AccessToken;
use Mainick\KeycloakClientBundle\Token\KeycloakResourceOwner;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Mainick\KeycloakClientBundle\Interface\IamClientInterface;
use Mainick\KeycloakClientBundle\Annotation\ExcludeTokenValidationAttribute;
use function dump;


class AuthenticationWebController extends AbstractWebController implements IamClientInterface
{
    private Keycloak $keycloakProvider;
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserServiceInterface $userService,
        private readonly IamClientInterface $iamClient
    ) {
    }

    #[ExcludeTokenValidationAttribute]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('web_home_homepage');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('authentication/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    public function register(Request $request): Response
    {
        $error = null;

        if (false === $request->isMethod('POST')) {
            return $this->render('authentication/register.html.twig', [
                'error' => $error,
            ]);
        }

        $firstName = $request->request->get('first_name');
        $lastName = $request->request->get('last_name');
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        try {
            $this->userService->create([
                'id' => Uuid::v4(),
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $email,
                'password' => $password,
            ]);

            return $this->redirectToRoute('web_auth_login');
        } catch (UniqueConstraintViolationException $exception) {
            $error = $this->translator->trans('view.authentication.error.email_in_use');
        } catch (Exception $exception) {
            $error = $this->translator->trans('view.authentication.error.error_message').$exception->getMessage();
        }

        return $this->render('authentication/register.html.twig', [
            'error' => $error,
        ]);
    }

    #[ExcludeTokenValidationAttribute]
    public function authenKeycloak(Request $request): ?Response
    {
//        $token = $tokenStorage;
//        $accessToken = $this->iamClient;
        dump($request->server->get('IAM_CLIENT_SECRET'));
        $session = $request->getSession();
        $codeKey = $session->get('keycloak-code');
        dump($codeKey);
        $client = HttpClient::create();
        $tokenEndpoint = 'http://172.19.18.235:8080/realms/secultce/protocol/openid-connect/token';
        $clientId = $request->server->get('IAM_CLIENT_ID');
        $clientSecret = $request->server->get('IAM_CLIENT_SECRET');
        $redirectUri = 'http://localhost:8082/auth/keycloak/check';

        try {
            $response = $client->request('POST', $tokenEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $codeKey,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ],
            ]);
           dump($response->getContent());die;
        }catch (Exception $exception){
            dump($exception->getMessage());
        }



        dump($this->iamClient->getAuthorizationUrl()); die;
        // Garantir que o token seja do tipo KeycloakToken
        if (!$token instanceof \Keycloak\Bundle\Security\User\KeycloakToken) {
            throw new \LogicException('Token não é do tipo esperado.');
        }

        // Access Token
        $accessToken = $token->getAccessToken();

        // ID Token (opcional, se precisar)
        $idToken = $token->getIdToken();

        // Dados do usuário
        $userInfo = $token->getUser();
        dump($userInfo); die;
        return $this->json([
            'access_token' => $accessToken,
            'id_token' => $idToken,
            'user' => $userInfo,
        ]);

    }
    public function refreshToken(AccessTokenInterface $token): ?AccessTokenInterface
    {
        return null;
    }

    public function verifyToken(AccessTokenInterface $token): ?UserRepresentationDTO
    {
        return null;
    }

    public function userInfo(AccessTokenInterface $token): ?UserRepresentationDTO
    {
        return null;
    }

    public function fetchUserFromToken(AccessTokenInterface $token): ?KeycloakResourceOwner
    {
        return null;
    }

    /**
     * @param array<string,string> $options
     */
    public function getAuthorizationUrl(array $options = []): string
    {
        return '';
    }

    /**
     * @param array<string,string> $options
     */
    public function logoutUrl(array $options = []): string
    {
        return '';
    }


    /**
     * @param array<string,string> $options
     */
    public function authorize(array $options, ?callable $redirectHandler = null): never
    {
        echo '';
        exit();
    }


    public function authenticate(string $username, string $password): ?AccessTokenInterface
    {
        return null;
    }

    public function getState(): string
    {
        return '';
    }


    public function authenticateCodeGrant(string $code): ?AccessTokenInterface
    {
        try {
            $token = $this->keycloakProvider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
            dump($token); die;
            $accessToken = new AccessToken();
            $accessToken->setToken($token->getToken())
                ->setExpires($token->getExpires())
                ->setRefreshToken($token->getRefreshToken())
                ->setValues($token->getValues());

            $this->keycloakClientLogger->info('KeycloakClient::authenticateCodeGrant', [
                'token' => $accessToken->getToken(),
                'expires' => $accessToken->getExpires(),
                'refresh_token' => $accessToken->getRefreshToken(),
            ]);

            return $accessToken;
        }
        catch (\Exception $e) {
            $this->keycloakClientLogger->error('KeycloakClient::authenticateCodeGrant', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string> $roles
     */
    public function hasAnyRole(AccessTokenInterface $token, array $roles): bool
    {
        return false;
    }


    /**
     * @param array<string> $roles
     */
    public function hasAllRoles(AccessTokenInterface $token, array $roles): bool
    {
        return false;
    }

    public function hasRole(AccessTokenInterface $token, string $role): bool
    {
        return false;
    }

    /**
     * @param array<string> $scopes
     */
    public function hasAnyScope(AccessTokenInterface $token, array $scopes): bool
    {
        return false;
    }

    /**
     * @param array<string> $scopes
     */
    public function hasAllScopes(AccessTokenInterface $token, array $scopes): bool
    {
        return false;
    }

    public function hasScope(AccessTokenInterface $token, string $scope): bool
    {
        return false;
    }

    /**
     * @param array<string> $groups
     */
    public function hasAnyGroup(AccessTokenInterface $token, array $groups): bool
    {
        return false;
    }

    /**
     * @param array<string> $groups
     */
    public function hasAllGroups(AccessTokenInterface $token, array $groups): bool
    {
        return false;
    }

    public function hasGroup(AccessTokenInterface $token, string $group): bool
    {
        return false;
    }
}
