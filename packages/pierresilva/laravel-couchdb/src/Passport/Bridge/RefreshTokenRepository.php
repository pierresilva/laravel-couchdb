<?php

namespace pierresilva\CouchDB\Passport\Bridge;

use Illuminate\Database\Connection;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Events\RefreshTokenCreated;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\RefreshToken;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{

  /**
   * The access token repository instance.
   *
   * @var \Laravel\Passport\Bridge\AccessTokenRepository
   */
  protected $tokens;

  /**
   * The database connection.
   *
   * @var \Illuminate\Database\Connection
   */
  protected $database;

  /**
   * The event dispatcher instance.
   *
   * @var \Illuminate\Contracts\Events\Dispatcher
   */
  protected $events;

  /**
   * Create a new repository instance.
   *
   * @param  \Laravel\Passport\Bridge\AccessTokenRepository  $tokens
   * @param  \Illuminate\Database\Connection  $database
   * @param  \Illuminate\Contracts\Events\Dispatcher  $events
   * @return void
   */
  public function __construct(AccessTokenRepository $tokens,
                              Connection $database,
                              Dispatcher $events)
  {
      $this->events = $events;
      $this->tokens = $tokens;
      $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewRefreshToken()
  {
      return new RefreshToken;
  }
  /**
   * {@inheritdoc}
   */
  public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
  {
      $this->database->table('oauth_refresh_tokens')->insert([
          '_id' => $id = $refreshTokenEntity->getIdentifier(),
          'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
          'revoked' => false,
          'expires_at' => $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
      ]);

      $this->events->fire(new RefreshTokenCreated($id, $accessTokenId));
  }

  /**
   * {@inheritdoc}
   */
  public function revokeRefreshToken($tokenId)
  {
      $this->database->table('oauth_refresh_tokens')
                  ->where('_id', $tokenId)->update(['revoked' => true]);
  }

  /**
   * {@inheritdoc}
   */
  public function isRefreshTokenRevoked($tokenId)
  {
      $refreshToken = $this->database->table('oauth_refresh_tokens')
                  ->where('_id', $tokenId)->first();

      if ($refreshToken === null || $refreshToken->revoked) {
          return true;
      }

      return $this->tokens->isAccessTokenRevoked(
          $refreshToken->access_token_id
      );
  }
}
