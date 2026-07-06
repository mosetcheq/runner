<?php
namespace Rnr\Core;

/**
 * Dependency Injection class
 * 
 * @author Jakub Svozil <jakub@quitko.cz>
 * @version 1.0.0
 * 
 */
class DI
{
    /**
     * Statická instance DI
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Pole služeb
     *
     * @var array
     */
    private array $services = [];


    /**
     * Uložení instance DI do statické vlastnosti třídy
     *
     * @param self $container
     * @return void
     */
    public static function setInstance(self $container): void
    {
        self::$instance = $container;
    }


    /**
     * Vráti instanci ze statické vlastnosti
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException("DI container not initialized.");
        }

        return self::$instance;
    }


    /**
     * Uloží službu do DI kontejneru
     *
     * @param string $id
     * @param object $service
     * @return void
     */
    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }


    /**
     * Vrátí služby z DI kontejneru
     *
     * @param string $id
     * @return object
     */
    public function get(string $id): object
    {
        if (!isset($this->services[$id])) {
            throw new \RuntimeException("Service '{$id}' not found.");
        }

        return $this->services[$id];
    }


    /**
     * Otestuje zda se služba nachází v DI kontejneru
     *
     * @param string $id
     * @return boolean
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}