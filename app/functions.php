<?php

/**
 * Отобразим дебаг инфу пряму боту в ответ
 * @return string
 */
function bot_debug($var) {
    ob_start();
    //var_dump($this->request);
    print_r($var);
    $debug_out = ob_get_contents();
    ob_end_clean();
    app('log')->debug($debug_out);
}