<?php namespace LucaDegasperi\OAuth2Server\Repositories;

use League\OAuth2\Server\Storage\SessionInterface;
use DB;
use Carbon\Carbon;

class FluentSession implements SessionInterface, SessionManagementInterface
{

    public function createSession($clientId, $ownerType, $ownerId)
    {
        return DB::table('oauth_sessions')->insertGetId(array(
            'client_id'  => $clientId,
            'owner_type' => $ownerType,
            'owner_id'   => $ownerId,
            'created_at' => Carbon::now()->getTimestamp(),
            'updated_at' => Carbon::now()->getTimestamp()
        ));
    }

    public function deleteSession($clientId, $ownerType, $ownerId)
    {
        DB::table('oauth_sessions')
            ->where('client_id', $clientId)
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->delete();
    }

    public function associateRedirectUri($sessionId, $redirectUri)
    {
        DB::table('oauth_session_redirects')->insert(array(
            'session_id'   => $sessionId,
            'redirect_uri' => $redirectUri,
            'created_at' => Carbon::now()->getTimestamp(),
            'updated_at' => Carbon::now()->getTimestamp()
        ));
    }

    public function associateAccessToken($sessionId, $accessToken, $expireTime)
    {
        return DB::table('oauth_session_access_tokens')->insertGetId(array(
            'session_id'           => $sessionId,
            'access_token'         => $accessToken,
            'access_token_expires' => $expireTime,
            'created_at' => Carbon::now()->getTimestamp(),
            'updated_at' => Carbon::now()->getTimestamp()
        ));
    }

    public function associateRefreshToken($accessTokenId, $refreshToken, $expireTime, $clientId)
    {
        DB::table('oauth_session_refresh_tokens')->insert(array(
            'session_access_token_id' => $accessTokenId,
            'refresh_token'           => $refreshToken,
            'refresh_token_expires'   => $expireTime,
            'client_id'               => $clientId,
            'created_at' => Carbon::now()->getTimestamp(),
            'updated_at' => Carbon::now()->getTimestamp()
        ));
    }

    public function associateAuthCode($sessionId, $authCode, $expireTime)
    {
        $id = DB::table('oauth_session_authcodes')->insertGetId(array(
            'session_id'        => $sessionId,
            'auth_code'         => $authCode,
            'auth_code_expires' => $expireTime,
            'created_at' => Carbon::now()->getTimestamp(),
            'updated_at' => Carbon::now()->getTimestamp()
        ));

        return $id;
    }

    public function removeAuthCode($sessionId)
    {
        DB::table('oauth_session_authcodes')
            ->where('session_id', $sessionId)
            ->delete();
    }

    public function validateAuthCode($clientId, $redirectUri, $authCode)
    {
        $result = DB::table('oauth_sessions')
                    ->select(array('oauth_sessions.id as session_id', 'oauth_session_authcodes.id as authcode_id'))
                    ->join('oauth_session_authcodes', 'oauth_sessions.id', '=', 'oauth_session_authcodes.session_id')
                    ->join('oauth_session_redirects', 'oauth_sessions.id', '=', 'oauth_session_redirects.session_id')
                    ->where('oauth_sessions.client_id', $clientId)
                    ->where('oauth_session_authcodes.auth_code', $authCode)
                    ->where('oauth_session_authcodes.auth_code_expires', '>=', time())
                    ->where('oauth_session_redirects.redirect_uri', $redirectUri)
                    ->first();

        return (is_null($result)) ? false : (array) $result;
    }

    public function validateAccessToken($accessToken)
    {
        $result = DB::table('oauth_session_access_tokens')
                    ->join('oauth_sessions', 'oauth_session_access_tokens.session_id', '=', 'oauth_sessions.id')
                    ->where('access_token', $accessToken)
                    ->where('access_token_expires', '>=', time())
                    ->first();

        return (is_null($result)) ? false : (array) $result;
    }

    public function validateRefreshToken($refreshToken, $clientId)
    {
        $result = DB::table('oauth_session_refresh_tokens')
                    ->where('refresh_token', $refreshToken)
                    ->where('client_id', $clientId)
                    ->where('refresh_token_expires', '>=', time())
                    ->first();

        return (is_null($result)) ? false : $result['session_access_token_id'];
    }

    public function getAccessToken($accessTokenId)
    {
        $result = DB::table('oauth_session_access_tokens')
                    ->where('id', $accessTokenId)
                    ->first();

        return (is_null($result)) ? false : (array) $result;
    }

    public function associateScope($accessTokenId, $scopeId)
    {
        DB::table('oauth_session_token_scopes')->insert(array(
            'session_access_token_id' => $accessTokenId,
            'scope_id'                => $scopeId,
            'created_at' => Carbon::now()->getTimestamp(),
            'updated_at' => Carbon::now()->getTimestamp()
        ));
    }

    public function getScopes($accessToken)
    {
        $scopeResults = DB::table('oauth_session_token_scopes')
	    	->select('oauth_scopes.*')
            ->join('oauth_session_access_tokens', 'oauth_session_token_scopes.session_access_token_id', '=', 'oauth_session_access_tokens.id')
            ->join('oauth_scopes', 'oauth_session_token_scopes.scope_id', '=', 'oauth_scopes.id')
            ->where('access_token', $accessToken)
            ->get();
        
        $scopes = array();
        if(is_object($scopesResults)){
	        foreach($scopesResults as $key=>$scope)
	        {
				$scopes[$key] = get_object_vars($scope);
	
			}
        }else{
        	$scopes = $scopesResults;
        }
		
        return $scopes;
    }

    public function associateAuthCodeScope($authCodeId, $scopeId)
    {
        DB::table('oauth_session_authcode_scopes')->insert(array(
            'oauth_session_authcode_id' => $authCodeId,
            'scope_id'                  => $scopeId,
            'created_at' => Carbon::now()->getTimestamp(),
            'updated_at' => Carbon::now()->getTimestamp()
        ));
    }

    public function getAuthCodeScopes($oauthSessionAuthCodeId)
    {
        $scopesResults = DB::table('oauth_session_authcode_scopes')
                ->where('oauth_session_authcode_id', '=', $oauthSessionAuthCodeId)
                ->get();

        $scopes = array();
        if(is_object($scopesResults)){
	        foreach($scopesResults as $key=>$scope)
	        {
				$scopes[$key] = get_object_vars($scope);
	
			}
        }else{
        	$scopes = $scopesResults;
        }
        
        return $scopes;
        
    }

    public function removeRefreshToken($refreshToken)
    {
        DB::table('oauth_session_refresh_tokens')
            ->where('refresh_token', '=', $refreshToken)
            ->delete();
    }
    
    public function removeSessionByAccessToken($accessToken)
    {
    	$sess_at =  DB::table('oauth_session_access_tokens')
            ->where('access_token', '=', $accessToken)
            ->first();
        $sess_id = $sess_at ? $sess_at['session_id'] : false;
        if($sess_id===false) return true;
    	DB::table('oauth_session_access_tokens')
            ->where('access_token', '=', $accessToken)
            ->delete();
        DB::table('oauth_sessions')
            ->where('id','=',$sess_id)
            ->delete();
    }

    public function deleteExpired()
    {
        $time = time();
        $expiredSessions = DB::table('oauth_sessions')
                            ->select(array('oauth_sessions.id as session_id'))
                            ->join('oauth_session_access_tokens', 'oauth_session_access_tokens.session_id', '=', 'oauth_sessions.id')
                            ->leftJoin('oauth_session_refresh_tokens', 'oauth_session_refresh_tokens.session_access_token_id', '=', 'oauth_session_access_tokens.id')
                            ->where('oauth_session_access_tokens.access_token_expires', '<', $time)
                            ->where(function ($query) use ($time) {
                                $query->where('oauth_session_refresh_tokens.refresh_token_expires', '<', $time)
                                      ->orWhereRaw('oauth_session_refresh_tokens.refresh_token_expires IS NULL');
                            })
                            ->get();
        if (count($expiredSessions) == 0) {
            return 0;
        } else {
            foreach ($expiredSessions as $session) {
                DB::table('oauth_sessions')
                    ->where('id', '=', $session->session_id)
                    ->delete();
            }

            return count($expiredSessions);
        }
    }
}
