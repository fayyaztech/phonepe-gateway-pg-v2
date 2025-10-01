<?php

namespace Fayyaztech\PhonePgV2;

class Environment
{
    const PRODUCTION = "PRODUCTION";
    const DEVELOPMENT = "DEVELOPMENT";

    private $environment;
    private $baseUrl;
    private $isProduction;

    /**
     * Environment constructor
     * 
     * @param string $environment Use Environment::PRODUCTION or Environment::DEVELOPMENT
     */
    public function __construct($environment = self::DEVELOPMENT)
    {
        $this->environment = $environment;
        $this->isProduction = ($environment === self::PRODUCTION);
        $this->baseUrl = $this->setBaseUrl();
    }

    /**
     * Set base URL based on environment
     * 
     * @return string
     */
    private function setBaseUrl()
    {
        return $this->isProduction
            ? "https://api.phonepe.com/apis/hermes"
            : "https://api-preprod.phonepe.com/apis/hermes";
    }

    /**
     * Check if environment is production
     * 
     * @return bool
     */
    public function isProduction()
    {
        return $this->isProduction;
    }

    /**
     * Get current environment
     * 
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Get base URL
     * 
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
