<?php

namespace manguto\manguto\lib\cms;

class CMSPageSite extends CMSPage
{

    public function __construct($opts = array(), $tpl_dir = 'site/')
    {   
        parent::__construct($opts,$tpl_dir);
    }
}

?>