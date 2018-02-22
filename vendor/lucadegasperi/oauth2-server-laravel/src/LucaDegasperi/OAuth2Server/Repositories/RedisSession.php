<?php

namespace LucaDegasperi\OAuth2Server\Repositories;

use League\OAuth2\Server\Storage\SessionInterface;
use Redis;
use Carbon\Carbon;

class RedisSession implements SessionInterface, SessionManagementInterface
{
	public function createSession($clientId, $ownerType, $ownerId)
	{
		
	}
	
	public function deleteSession($clientId, $ownerType, $ownerId)
	{
		
	}
	
	public function associateRedirectUri($sessionId, $redirectUri)
	{
		
	}
	
	public function associateAccessToken($sessionId, $accessToken, $expireTime)
	{
		
	}
	
	public function associateRefreshToken($accessTokenId, $refreshToken, $expireTime, $clientId)
	{
		
	}
	
	public function associateAuthCode($sessionId, $authCode, $expireTime)
	{
		
	}
	
	public function removeAuthCode($sessionId)
	{
		
	}
	
	public function validateAuthCode($clientId, $redirectUri, $authCode)
	{
		
	}
	
	public function validateAccessToken($accessToken)
	{
		
	}
	
	public function validateRefreshToken($refreshToken, $clientId)
	{
		
	}
	
	public function getAccessToken($accessTokenId)
	{
		
	}
	
	public function associateScope($accessTokenId, $scopeId)
	{
		
	}
	
	public function getScopes($accessToken)
	{
		
	}
	
	public function associateAuthCodeScope($authCodeId, $scopeId)
	{
		
	}
	
	public function getAuthCodeScopes($oauthSessionAuthCodeId)
	{
		
	}
	
	public function removeRefreshToken($refreshToken)
	{
		
	}
	
	public function removeSessionByAccessToken($accessToken)
	{
		
	}
	
	public function deleteExpired()
	{
		
	}
}