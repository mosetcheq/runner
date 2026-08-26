<?php
namespace Rnr\Http;

/**
 * Class FormData - data z formularu
 */
class FormData
{

    private array $data;

    /**
     * Konstruktor
     * @param array $data;
     */
    public function __construct(array $data)
    {
        unset($data['_rnrform']);
        $this->data = $data;
    }


    /**
     * getter
     * @param string $key Nazev pole
     * @return string
     */
    public function __get(string $key) : mixed
    {
        return $this->data[$key] ?? null;
    }


    /**
     * Otestování zda pole existuje a případně má uvedenou hodnotu
     * @param string $key název pole
     * @param string $value hodnota pole
     * @return bool
     */
    public function check($key, $value = null) : bool
    {
        if($value == null) {
            return isset($this->data[$key]);
        }
        elseif(isset($this->data[$key])) {
            return($this->data[$key] == $value);
        }
        else return(false);
    }


    /**
     * Vrátí všechny data z formuláře jako pole
     * @return object
     */
    public function getAll(): \stdClass
    {
        return (object) $this->data;
    }


    /**
     * Provede php funkci TRIM na všechny pole formuláře
     * @param string $characters znaky na ořez
     */
    public function trim($characters = false) : self
    {
        foreach ($this->data as $key => $val) {
            $this->data[$key] = (is_array($val) ? array_filter($val) : trim($val, $characters));
        }
        return $this;
    }
}
