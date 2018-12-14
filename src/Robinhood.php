<?php

namespace MichaelDrennen\Robinhood;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use MichaelDrennen\Robinhood\Responses\Accounts\Accounts;
use MichaelDrennen\Robinhood\Responses\Positions\Positions;

class Robinhood {
    protected $guzzle;

    // Returned from login attempt with Robinhood
    protected $accessToken;
    protected $expiresIn;
    protected $tokenType;
    protected $scope;
    protected $refreshToken;
    protected $mfaCode;
    protected $backupCode;


    public function __construct() {
        $dotenv = new Dotenv( __DIR__ );
        $dotenv->load();
        $this->guzzle = $this->createGuzzleClient();
    }

    /**
     * @param string|NULL $token
     * @return \GuzzleHttp\Client
     */
    protected function createGuzzleClient( string $token = NULL ): Client {

        $headers             = [];
        $headers[ 'Accept' ] = 'application/json';
        if ( $token ):
            $headers[ 'Authorization' ] = 'Bearer ' . $token;
        endif;

        $options = [
            'base_uri' => 'https://api.robinhood.com',
            'headers'  => $headers ];
        return new Client( $options );
    }


    /**
     * @param string $username
     * @param string $password
     * @param string $clientId
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login( string $username, string $password, string $clientId ) {
        $url = '/oauth2/token/';

        $formParams = [
            'form_params' => [
                'username'   => $username,
                'password'   => $password,
                'grant_type' => 'password',
                'client_id'  => $clientId,
            ],
        ];

        $response = $this->guzzle->request( 'POST', $url, $formParams );

        $body = $response->getBody();

        $robinhoodResponse = \GuzzleHttp\json_decode( $body->getContents(), TRUE );

        $this->accessToken  = $robinhoodResponse[ 'access_token' ];
        $this->expiresIn    = $robinhoodResponse[ 'expires_in' ];
        $this->tokenType    = $robinhoodResponse[ 'token_type' ];
        $this->scope        = $robinhoodResponse[ 'scope' ];
        $this->refreshToken = $robinhoodResponse[ 'refresh_token' ];
        $this->mfaCode      = $robinhoodResponse[ 'mfa_code' ];
        $this->backupCode   = $robinhoodResponse[ 'backup_code' ];

        $this->guzzle = $this->createGuzzleClient( $this->accessToken );
    }

    /**
     * @return \MichaelDrennen\Robinhood\Responses\Accounts\Accounts
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function accounts(): Accounts {
        $url               = '/accounts/';
        $response          = $this->guzzle->request( 'GET', $url );
        $body              = $response->getBody();
        $robinhoodResponse = \GuzzleHttp\json_decode( $body->getContents(), TRUE );
        return new Accounts( $robinhoodResponse );
    }

    /**
     * This method will return an array of Position records for every stock this account has ever owned. Even positions
     * that you have sold out of.
     * @todo Add code to handle pagination links when they get returned from Robinhood.
     *       Once the test account has had a large enough number of stocks in it, the Robinhood API will probably send
     *       paginated links to get the rest of your holdings.
     * @return \MichaelDrennen\Robinhood\Responses\Positions\Positions
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function positions() {
        $url               = '/positions/';
        $response          = $this->guzzle->request( 'GET', $url );
        $body              = $response->getBody();
        $robinhoodResponse = \GuzzleHttp\json_decode( $body->getContents(), TRUE );
        return new Positions( $robinhoodResponse );
    }

    public function instruments( string $ticker ) {
        $url               = '/instruments/';
        $response          = $this->guzzle->request( 'GET', $url, [
            'query' => [ 'query' => strtoupper( $ticker ) ],
        ] );
        $body              = $response->getBody();
        $robinhoodResponse = \GuzzleHttp\json_decode( $body->getContents(), TRUE );
        print_r( $robinhoodResponse );
        //return new Positions( $robinhoodResponse );
    }

    /**
     * Some calls to the Robinhood API will return URLs in the result set. Use this method as a one-off to request that
     * URL and see what gets returned.
     * @param string $url
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function url( string $url ): array {
        $headers             = [];
        $headers[ 'Accept' ] = 'application/json';
        if ( ! $this->accessToken ):
            throw new \Exception( "You need to login and get an access token before you can fire off this function." );
        endif;
        $headers[ 'Authorization' ] = 'Bearer ' . $this->accessToken;
        $options                    = [
            'headers' => $headers,
        ];
        $guzzleClient               = new Client( $options );
        $response                   = $guzzleClient->request( 'GET', $url );
        $body                       = $response->getBody();
        $robinhoodResponse          = \GuzzleHttp\json_decode( $body->getContents(), TRUE );
        return $robinhoodResponse;
    }

    public function buy( string $ticker, int $shares ) {

    }

    public function sellPosition( $ticker ) {

    }
}