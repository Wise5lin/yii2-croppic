<?php

namespace yii\web {

    function move_uploaded_file($from, $to)
    {
        return rename($from, $to);
    }
    function is_uploaded_file($file)
    {
        return true;
    }
}
