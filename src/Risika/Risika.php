<?php

namespace Risika;

use GuzzleHttp\Client;

class Risika
{

    private $refreshToken;
    private $accessToken;
    private $expirationMargin = 20;
    private $tokenExpires;
    private $version;
    private $lang;
    private $baseUrl = "https://api.risika.dk/";
    private $url;
    private $client;



    public function __construct($refreshToken, $version, $lang) {
        $this->refreshToken = $refreshToken;
        $this->version = $version;
        $this->lang = $lang;
        $this->url = $this->baseUrl.$this->version;

        return $this->refresh();
    }

    private function initializeClient() {

        $options = [
            'base_uri'    => $this->url,
            'http_errors' => true,
        ];
        return $this->client = new Client($options);
    }

    public function refresh() {
        $headers = ['headers' => ['Authorization' => $this->refreshToken, 'Content-Type' => 'application/json', 'Accept-Language' => $this->lang]];
        $response = json_decode($this->initializeClient()->get($this->version.'/access/refresh_token', $headers)->getBody(),1);

        $this->accessToken = $response['token'];

        $token = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $response['token'])[1]))));

        $this->tokenExpires = $token->exp - $this->expirationMargin;

    }

    private function token() {
        if($this->tokenExpires < time()) {
            $this->refresh();
        }

        return $this->accessToken;
    }

    public function get($locale, $path) {
        $options = ['headers' => ['Authorization' => $this->token(), 'Content-Type' => 'application/json', 'Accept-Language' => $this->lang]];
        return json_decode($this->initializeClient()->get($this->version.'/'.$locale.$path, $options)->getBody(), 1);
    }


    public function search($locale, $search, $mode = 'full', $method = 'id') {
        $options = ['headers' => ['Authorization' =>
                                      $this->token(), 'Content-Type' => 'application/json', 'Accept-Language' => $this->lang],
                    'json' => [
                        'mode' => $mode,
                        'filters' => ['free_search' => $search]
                    ]];
        return json_decode($this->initializeClient()->post($this->version.'/'.$locale.'/search/company', $options)->getBody(), 1)['search_result'];
    }

    public function searchPerson($locale, $search, $mode = 'full', $method = 'id') {
        $options = ['headers' => ['Authorization' =>
                                      $this->token(), 'Content-Type' => 'application/json', 'Accept-Language' => $this->lang],
                    'json' => [
                        'query' => $search
                    ]];
        return json_decode($this->initializeClient()->post($this->version.'/'.$locale.'/search/person', $options)->getBody(), 1)['search_result'];
    }

    public function getBasicCompanyInfo($locale, $companyId) {
        return $this->get($locale, '/company/basics/'.$companyId);
    }

    public function getCompanyStatus($locale, $companyId) {
        return $this->get($locale, '/company/basics/'.$companyId)['status'];
    }

    public function getCompanyRelations($locale, $companyId) {
        return $this->get($locale, '/company/relations/'.$companyId)['relations'];
    }

    public function getCompanyPowerToBind($locale, $companyId) {
        return $this->get($locale, '/company/basics/'.$companyId)['powers_to_bind'];
    }

    public function getCompanyHighlight($locale, $companyId) {
        return $this->get($locale, '/highlights/'.$companyId);
    }

    public function getCurrentLegalOwners($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentCurrentLegalOwners = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {
                if($function['function'] == "LEGAL OWNER" && $function['valid_to'] == null) {
                    if(!in_array($relation['name'], $currentCurrentLegalOwners)) {
                        array_push($currentCurrentLegalOwners, $relation['name']);
                    }
                }
            }
        }

        return $currentCurrentLegalOwners;
    }

    public function getCurrentRealOwners($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentCurrentRealOwners = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {
                if($function['function'] == "BENEFICIAL OWNER" && $function['valid_to'] == null) {
                    if(!in_array($relation['name'], $currentCurrentRealOwners)) {
                        array_push($currentCurrentRealOwners, $relation['name']);
                    }
                }
            }
        }

        return $currentCurrentRealOwners;
    }

    public function getCurrentRealOwnersOver25Shares($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentCurrentRealOwners = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {
                if($function['function'] == "BENEFICIAL OWNER" && $function['valid_to'] == null && $function['shares'] >= 25.0) {
                    if(!in_array($relation['name'], $currentCurrentRealOwners)) {
                        array_push($currentCurrentRealOwners, $relation['name']);
                    }
                }
            }
        }

        return $currentCurrentRealOwners;
    }

    public function getDirectors($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentDirectors = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {
                if($function['function'] == "MANAGEMENT" && $function['valid_to'] == null OR $function['function'] == "CHIEF EXECUTIVE OFFICER" && $function['valid_to'] == null) {
                    if(!in_array($relation['name'], $currentDirectors)) {
                        $currentDirectors[] = $relation['name'];
                    }
                }
            }
        }

        return $currentDirectors;
    }

    public function getCEO($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentDirectors = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {
                if($function['function'] == "CHIEF EXECUTIVE OFFICER" && $function['valid_to'] == null) {
                    if(!in_array($relation['name'], $currentDirectors)) {
                        $currentDirectors[] = $relation['name'];
                    }
                }
            }
        }

        return $currentDirectors[0];
    }

    public function getCEOOrDirector($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentDirectors = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {
                if($function['function'] == "CHIEF EXECUTIVE OFFICER" && $function['valid_to'] == null) {
                    if(!in_array($relation['name'], $currentDirectors)) {
                        $currentDirectors = $relation['name'];
                    }
                }
            }
        }

        if(empty($currentDirectors)){
            foreach ($result as $relation) {
                foreach ($relation['functions'] as $function) {
                    if($function['function'] == "MANAGEMENT" && $function['valid_to'] == null) {
                        if(!in_array($relation['name'], $currentDirectors)) {
                            $currentDirectors[] = $relation['name'];
                        }
                    }
                }
            }

        }

        return $currentDirectors[0];
    }

    /*
     * Get either the CEO or the first director found
     * */
    public function getCEOOrDirectorInfo($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentDirectors = [];
        $currentCEO = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {

                if($function['valid_to'] != null) {
                    continue;
                }

                if($function['function'] == "CHIEF EXECUTIVE OFFICER" OR $function['function'] == "MANAGEMENT") {
                    if(!in_array($relation['personal_id'], $currentDirectors)) {
                        if($function['function'] == "CHIEF EXECUTIVE OFFICER") {
                            $currentCEO[] = $relation['personal_id'];
                        }
                        if($function['function'] == "MANAGEMENT") {
                            $currentDirectors[] = $relation['personal_id'];
                        }
                    }
                }

            }
        }

        return !empty($currentCEO) ? $currentCEO : $currentDirectors[0];
    }



    public function getFounders($locale, $companyId) {
        $result = $this->getCompanyRelations($locale, $companyId);

        $currentDirectors = [];

        foreach ($result as $relation) {
            foreach ($relation['functions'] as $function) {
                if($function['function'] == "FOUNDER" && $function['valid_to'] == null) {
                    if(!in_array($relation['name'], $currentDirectors)) {
                        $currentDirectors[] = $relation['name'];
                    }
                }
            }
        }

        return $currentDirectors;
    }


    public function getFinancialRatios($locale, $companyId) {
        return $this->get($locale, '/financial/ratios/'.$companyId);
    }

    public function getFinancialStats($locale, $companyId) {
        return $this->get($locale, '/financial/stats/'.$companyId);
    }

    public function getFinancialPerformance($locale, $companyId) {
        return $this->get($locale, '/financial/performance/'.$companyId);
    }

}
