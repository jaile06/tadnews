# tadnews 模組專屬規範

> XOOPS 通用規則（核心物件、安全性、架構鐵則、TadTools、CodeGraph 邊界）
> 由全域 Skill `xoops-core-dev` 提供，此處不重複。

---

## 1. 模組基本資訊

| 項目 | 值 |
|---|---|
| 模組名稱 | `tadnews`|
| 命名空間 | `XoopsModules\Tadnews` |
| 架構書 | `.agents/tadnews_architecture.md` |
| 管理員 Session | 無專屬 key，權限為每次請求即時計算的全域變數 `$tadnews_adm`（`interface.php:18`、`class/Tools.php:207`） |
| 自有資料表 | `tad_news`, `tad_news_cate`, `tad_news_paper`, `tad_news_paper_setup`, `tad_news_paper_email`, `tad_news_paper_send_log`, `tad_news_sign`, `tad_news_tags`, `tadnews_files_center`, `tadnews_rank`, `tadnews_data_center`（共 11 張，見 `xoops_version.php` 的 `$modversion['tables']`，全為 MyISAM / utf8mb4） |
| 外部資料表 | `topics`, `stories`, `newblocks`, `tplfile`, `tplsource`, `tad_news_files`, `tad_themes_menu` — **非本模組所有**，僅 `admin/import.php` 匯入舊 news 模組資料時讀取。改碼時不得新增對這些表的寫入 |

---

## 2. 檔案結構速查

```text
入口分發（無 SQL）：admin/about.php, admin/admin_function.php, admin/admin_header.php, admin/import.php, admin/index.php, admin/main.php, admin/menu.php, admin/newspaper.php, admin/page.php, admin/save_cate_sort.php, admin/save_drag.php, admin/tag.php, ajax_list_content.php, ajax_table_content.php, app_api.php, archive.php, backend.php, demo_upload.php, email.php, function.php, index.php, mk_rss.php, my_news.php, newspaper.php, page.php, post.php, rss.php, save.php, save_sort.php
業務邏輯：class/Constants.php, class/Helper.php, class/TadNewsRest.php, class/Tadnews.php, class/Tools.php, class/Update.php
樣板：templates/blocks/searchbar.tpl, templates/blocks/tadnews_block_cate.tpl, templates/blocks/tadnews_block_cate_news.tpl, templates/blocks/tadnews_block_content_block.tpl, templates/blocks/tadnews_block_covered.tpl, templates/blocks/tadnews_block_focus_news.tpl, templates/blocks/tadnews_block_list_content_block.tpl, templates/blocks/tadnews_block_marquee.tpl, templates/blocks/tadnews_block_my_page.tpl, templates/blocks/tadnews_block_newspaper.tpl, templates/blocks/tadnews_block_newspaper_list.tpl, templates/blocks/tadnews_block_page.tpl, templates/blocks/tadnews_block_page_list.tpl, templates/blocks/tadnews_block_page_menu.tpl, templates/blocks/tadnews_block_slidernews.tpl, templates/blocks/tadnews_block_slidernews2.tpl, templates/blocks/tadnews_block_tab_news.tpl, templates/blocks/tadnews_block_table_content_block.tpl, templates/blocks/tadnews_block_tag_news.tpl, templates/fragment1.tpl, templates/fragment2.tpl, templates/fragment3.tpl, templates/fragment4.tpl, templates/op_archive.tpl, templates/op_list_sign.tpl, templates/op_list_tad_all_pages.tpl, templates/op_list_user_sign.tpl, templates/op_modify_page_cate.tpl, templates/op_show_page.tpl, templates/op_tabs_sort.tpl, templates/op_tad_news_cate_form.tpl, templates/op_tad_news_form.tpl, templates/op_tadnews_cate.tpl, templates/op_tadnews_list.tpl, templates/op_tadnews_show.tpl, templates/op_tadnews_summary.tpl, templates/sub_cate_pic.tpl, templates/sub_rss.tpl, templates/sub_summary.tpl, templates/tadnews_adm_import.tpl, templates/tadnews_adm_main.tpl, templates/tadnews_adm_newspaper.tpl, templates/tadnews_adm_page.tpl, templates/tadnews_adm_tag.tpl, templates/tadnews_index.tpl, templates/tadnews_my_news.tpl, templates/tadnews_newspaper.tpl, templates/tadnews_page.tpl, templates/tadnews_rss.tpl
前端 JS：class/jQuery.Marquee/jquery.marquee.js, class/jquery.upload-1.0.2.min.js, class/slider/jquery.touchslider.min.js
（2026-08-14 已刪：nicEdit.js + nicEditorIcons.gif 改用 tadtools CkEditor；tmt_core.js、tmt_spry_linkedselect.js 為零引用死檔）
語言包：language/english/admin.php, language/english/blocks.php, language/english/global.php, language/english/main.php, language/english/modinfo.php, language/tchinese_utf8/admin.php, language/tchinese_utf8/blocks.php, language/tchinese_utf8/global.php, language/tchinese_utf8/main.php, language/tchinese_utf8/modinfo.php
```

---

## 3. 模組特有注意事項

以下皆為 `xoops-lint` 實測或讀碼確認過的事實，非推測。

**授權模型（動它之前務必先讀）**
- 本模組**沒有** `$_SESSION['tadnews_adm']`。管理權限是每次請求即時計算：`$tadnews_adm = $xoopsUser->isAdmin()`（`interface.php:18`、`class/Tools.php:207`）。
- 一般使用者的發文權限走**分類層級**授權：`Tools::chk_user_cate_power($kind)` 回傳可操作的分類陣列，`$kind` 為 `'post'`（預設）或 `'pass'`（審核）。判斷「有無權限」要看回傳陣列是否非空，不是看布林值。
- 不要為了省一次 `isAdmin()` 呼叫而改成 Session 快取——群組異動後快取不會失效，是拿正確性換效能。

**單表雙用途**
- `tad_news` 同時存「新聞」與「單頁」，靠 `news_kind` 欄位區分（`'news'` / `'page'`）。任何 `tad_news` 的查詢都必須帶 `news_kind` 條件，漏掉會讓單頁混進新聞列表。

**已知技術債（重構優先序見 `.agents/tadnews_architecture.md`）**
- `class/Tadnews.php` 2567 行，採 Fluent Setter 鏈（`set_xxx()->get_news()`）而非參數傳入，`xoops-lint` 在此單檔報 67 條 `NON-STATIC` + 57 條 `RAW-SUPERGLOBAL`。**新功能一律不得再新增 `set_xxx()`**。
- 有 19 個 blocks 呼叫 `class/Tadnews.php`，改動其公開方法前務必用 CodeGraph 查完整呼叫端。
- `function.php`、`admin/admin_function.php` 為無命名空間的全域函式且內含 SQL，屬 `ENTRY-SQL` 違規來源。

**驗收方式**
- 每次改碼後跑 `php D:/workspace/02_Tools/xoops-lint/xoops-lint.php .`，只要 error 數沒比改碼前多就算過關。
