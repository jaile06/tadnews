<?php
use XoopsModules\Tadnews\Cate;

require_once 'header.php';

Cate::add_tad_news_cate($_POST['value']);

echo $_POST['value'];
