<?php
namespace manguto\manguto\mvc\view;

class ViewDev extends View
{

    static function load(string $tplName, array $parameters = [], bool $toString = false)
    {
        return self::PageDev($tplName, $parameters, $toString);
    }
}