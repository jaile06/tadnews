<?php

namespace XoopsModules\Tadnews;

use XoopsModules\Tadtools\MColorPicker;
use XoopsModules\Tadtools\SweetAlert;
use XoopsModules\Tadtools\Utility;

/**
 * 標籤：tad_news_tags 的 CRUD、啟用狀態與使用次數統計
 * 由 admin/tag.php 搬入（2026-08-14），行為未變更。
 */
class Tag
{
    //tad_news_tagss編輯表單
    public static function list_tad_news_tags($def_tag_sn = '')
    {
        global $xoopsDB, $xoopsTpl, $Tadnews;

        $sql    = 'SELECT * FROM `' . $xoopsDB->prefix('tad_news_tags') . '`';
        $result = $xoopsDB->query($sql) or Utility::web_error($sql, __FILE__, __LINE__);

        $i                = 0;
        $tags_used_amount = self::tags_used_amount();
        while (list($tag_sn, $tag, $font_color, $color, $enable) = $xoopsDB->fetchRow($result)) {
            $tag_amount = isset($tags_used_amount[$tag_sn]) ? (int) $tags_used_amount[$tag_sn] : 0;

            $tagarr[$i]['tag_sn']     = $tag_sn;
            $tagarr[$i]['prefix_tag'] = $Tadnews->mk_prefix_tag($tag_sn, 'all');
            $tagarr[$i]['enable']     = $enable;
            $tagarr[$i]['tag_amount'] = $tag_amount;
            $tagarr[$i]['tag']        = $tag;
            $tagarr[$i]['font_color'] = $font_color;
            $tagarr[$i]['color']      = $color;
            $tagarr[$i]['enable_txt'] = ('1' == $enable) ? _YES : _NO;
            $tagarr[$i]['mode']       = ($def_tag_sn == $tag_sn) ? 'edit' : 'show';
            $tagarr[$i]['checked']    = ($def_tag_sn == $tag_sn) ? 1 : '';
            $tagarr[$i]['amount']     = sprintf(_MA_TADNEWS_TAG_AMOUNT, $tag_amount);
            $i++;
        }

        $xoopsTpl->assign('tag_sn', $def_tag_sn);
        $xoopsTpl->assign('tagarr', $tagarr);
        $xoopsTpl->assign('jquery', Utility::get_jquery());
        $xoopsTpl->assign('tag', $tag);
        $xoopsTpl->assign('font_color', $font_color);
        $xoopsTpl->assign('color', $color);
        $xoopsTpl->assign('XOOPS_TOKEN', Utility::token_form('return'));
        $MColorPicker = new MColorPicker('.color-picker');
        $MColorPicker->render('bootstrap');

        $SweetAlert = new SweetAlert();
        $SweetAlert->render('delete_tag', 'tag.php?op=del_tag&tag_sn=', 'tag_sn');
    }

    public static function insert_tad_news_tags()
    {
        global $xoopsDB;
        //安全判斷
        if ($_SERVER['SERVER_ADDR'] != '127.0.0.1' && !$GLOBALS['xoopsSecurity']->check()) {
            $error = implode('<br>', (Array) $GLOBALS['xoopsSecurity']->getErrors());
            redirect_header('index.php', 3, $error);
        }

        $sql = 'INSERT INTO `' . $xoopsDB->prefix('tad_news_tags') . '` (`tag`, `font_color`, `color`, `enable`) VALUES (?, ?, ?, ?)';
        Utility::query($sql, 'ssss', [$_POST['tag'], $_POST['font_color'], $_POST['color'], $_POST['enable']]) or Utility::web_error($sql, __FILE__, __LINE__);

    }

    public static function update_tad_news_tags($tag_sn)
    {
        global $xoopsDB;
        $sql = 'UPDATE `' . $xoopsDB->prefix('tad_news_tags') . '` SET `tag`=?, `font_color`=?, `color`=?, `enable`=? WHERE `tag_sn`=?';
        Utility::query($sql, 'ssssi', [$_POST['tag'], $_POST['font_color'], $_POST['color'], $_POST['enable'], $tag_sn]) or Utility::web_error($sql, __FILE__, __LINE__);

    }

    public static function tad_news_tags_stat($enable, $tag_sn)
    {
        global $xoopsDB;

        $sql = 'UPDATE `' . $xoopsDB->prefix('tad_news_tags') . '` SET `enable` = ? WHERE `tag_sn` = ?';
        Utility::query($sql, 'si', [$enable, $tag_sn]) or Utility::web_error($sql, __FILE__, __LINE__);

    }

    public static function del_tag($tag_sn = '')
    {
        global $xoopsDB;

        $sql = 'DELETE FROM `' . $xoopsDB->prefix('tad_news_tags') . '` WHERE `tag_sn`=?';
        Utility::query($sql, 'i', [$tag_sn]) or Utility::web_error($sql, __FILE__, __LINE__);

    }

    public static function tags_used_amount()
    {
        global $xoopsDB;

        $sql    = 'SELECT `prefix_tag`, COUNT(`prefix_tag`) FROM `' . $xoopsDB->prefix('tad_news') . '` GROUP BY `prefix_tag`';
        $result = $xoopsDB->query($sql) or Utility::web_error($sql, __FILE__, __LINE__);

        $main = [];
        while (list($prefix_tag, $count) = $xoopsDB->fetchRow($result)) {
            $main[$prefix_tag] = $count;
        }

        return $main;
    }
}
