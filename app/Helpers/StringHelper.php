<?php

namespace App\Helpers;

trait StringHelper
{
    protected function generateUniqueSlug()
    {
        return strtoupper(substr(str_shuffle(MD5(microtime())), 0, 8));
    }
}
