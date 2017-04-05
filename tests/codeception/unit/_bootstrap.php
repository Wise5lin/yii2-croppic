<?php

namespace yii\web {

    /**
     * {@inheritdoc}
     */
    function move_uploaded_file($from, $to)
    {
        return rename($from, $to);
    }

    /**
     * {@inheritdoc}
     */
    function is_uploaded_file($file)
    {
        return true;
    }
}
