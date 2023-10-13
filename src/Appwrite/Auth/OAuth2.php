<?php

namespace Appwrite\Auth;

use Appwrite\Auth\OAuth2\Exception;

/**
 * OAuth2 Abstract Class
 * This abstract class serves as a foundation for OAuth2 authentication providers. It defines common methods and properties that OAuth2 adapters should implement.
 */
abstract class OAuth2
{
    /**
     * @var string The OAuth2 application ID.
     */
    protected string $appID;

    /**
     * @var string The OAuth2 application secret.
     */
    protected string $appSecret;

    /**
     * @var string The callback URL.
     */
    protected string $callback;

    /**
     * @var array An array to store state information.
     */
    protected array $state;

    /**
     * @var array An array to store OAuth2 scopes.
     */
    protected array $scopes;

    /**
     * OAuth2 constructor.
     *
     * @param string $appId The OAuth2 application ID.
     * @param string $appSecret The OAuth2 application secret.
     * @param string $callback The callback URL.
     * @param array $state An array to store state information.
     * @param array $scopes An array to store OAuth2 scopes.
     */
    public function __construct(string $appId, string $appSecret, string $callback, array $state = [], array $scopes = [])
    {
        $this->appID = $appId;
        $this->appSecret = $appSecret;
        $this->callback = $callback;
        $this->state = $state;
        foreach ($scopes as $scope) {
            $this->addScope($scope);
        }
    }

    /**
     * @return string Returns the name of the OAuth2 provider.
     */
    abstract public function getName(): string;

    /**
     * @return string Returns the URL for user login.
     */
    abstract public function getLoginURL(): string;

    /**
     * @param string $code The OAuth2 code to exchange for tokens.
     * @return array Returns an array of OAuth2 tokens.
     */
    abstract protected function getTokens(string $code): array;

    /**
     * @param string $refreshToken The refresh token to obtain new tokens.
     * @return array Returns an array of refreshed tokens.
     */
    abstract public function refreshTokens(string $refreshToken): array;

    /**
     * @param string $accessToken The access token for which to get the user ID.
     * @return string Returns the user ID associated with the access token.
     */
    abstract public function getUserID(string $accessToken): string;

    /**
     * @param string $accessToken The access token for which to get the user's email.
     * @return string Returns the user's email associated with the access token.
     */
    abstract public function getUserEmail(string $accessToken): string;

    /**
     * Check if the OAuth email is verified.
     * @param string $accessToken The access token to check for email verification.
     * @return bool Returns true if the email is verified; otherwise, false.
     */
    abstract public function isEmailVerified(string $accessToken): bool;

    /**
     * @param string $accessToken The access token for which to get the user's name.
     * @return string Returns the user's name associated with the access token.
     */
    abstract public function getUserName(string $accessToken): string;

    /**
     * Add a scope to the OAuth2 provider.
     * @param string $scope The scope to be added.
     * @return OAuth2 Returns the current instance for method chaining.
     */
    protected function addScope(string $scope): OAuth2
    {
        if (!in_array($scope, $this->scopes)) {
            $this->scopes[] = $scope;
        }

        return $this;
    }

    /**
     * Get the OAuth2 provider's scopes.
     *
     * @return array Returns an array of OAuth2 scopes.
     */
    protected function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Get the access token for a given OAuth2 code.
     *
     * @param string $code The OAuth2 code to exchange for an access token.
     *
     * @return string Returns the access token.
     */
    public function getAccessToken(string $code): string
    {
        $tokens = $this->getTokens($code);

        return $tokens['access_token'] ?? '';
    }

    /**
     * Get the refresh token for a given OAuth2 code.
     *
     * @param string $code The OAuth2 code to exchange for a refresh token.
     *
     * @return string Returns the refresh token.
     */
    public function getRefreshToken(string $code): string
    {
        $tokens = $this->getTokens($code);

        return $tokens['refresh_token'] ?? '';
    }

    /**
     * Get the access token's expiry time for a given OAuth2 code.
     *
     * @param string $code The OAuth2 code to exchange for tokens.
     *
     * @return int Returns the access token's expiry time.
     */
    public function getAccessTokenExpiry(string $code): int
    {
        $tokens = $this->getTokens($code);

        return $tokens['expires_in'] ?? 0;
    }

    /**
     * Parse state information.
     * @param string $state The state information to parse.
     * @return array Returns an array of parsed state information.
     */
    public function parseState(string $state)
    {
        return json_decode($state, true);
    }

    /**
     * Make an HTTP request to an OAuth2 provider.
     *
     * @param string $method The HTTP method (GET, POST, etc.).
     * @param string $url The URL for the request.
     * @param array $headers An array of HTTP headers.
     * @param string $payload The request payload.
     *
     * @return string Returns the response from the HTTP request.
     * @throws Exception If the request fails.
     */
    protected function request(string $method, string $url = '', array $headers = [], string $payload = ''): string
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Appwrite OAuth2');

        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $headers[] = 'Content-length: ' . strlen($payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($code >= 400) {
            throw new Exception($response, $code);
        }

        return (string) $response;
    }
}

