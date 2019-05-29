<?php

/**
 * Created by PhpStorm.
 * User: Asmodai
 * Date: 29/5/2019
 * Time: 2:21 μμ
 */

abstract class AbstractChatHandler{
    protected $aseco;

    function __construct($aseco){
        $this->aseco = $aseco;
    }

    abstract public function handle($chat);
}