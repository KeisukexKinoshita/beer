-- Darth Beer.com データ修正
-- 目的     : 通販サイトへの直リンクを、ブランドの公式トップに差し替える
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 巻き戻し : 013_official_url_20260830_rollback.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--
-- なぜ変えるか:
--   Google の「パブリッシャー制限コンテンツ」は、**アルコール飲料のオンライン販売を
--   促進するコンテンツ**を制限対象としている(support.google.com/adsense/answer/10437795)。
--   制限されると入札が減り、極端な場合は広告が出なくなる。
--
--   pr0088〜pr0117 の30件は `official_url`(詳細ページの「公式サイト」欄)が
--   カート付きの通販サイト uchubrew.shop-pro.jp を直接指しており、
--   「購入への導線」と読まれうる位置にあった。他の84件はブランドや商品紹介のページで、
--   ここだけが例外だった。
--
--   `source_url`(「出典」欄)は変えない。あれは事実の出どころを示す引用で、
--   仕様9-6 が持たせている透明性の担保だから。表示側で rel="nofollow" を付ける。
--
-- 差し替え先について:
--   https://uchubrewing.com は実在する公式サイトだが、2026-08-30 時点で
--   「WEBサイトリニューアル作業中」の表示が出る。**それでも公式のトップである**ため
--   ここを指す。リニューアル後に商品ページができたら、個別のURLに更新してよい。
--   なお maker.URL1(ブリュワリー詳細から辿れる公式サイト)は既にこのURLである。

UPDATE `products` SET `official_url` = 'https://uchubrewing.com'
 WHERE `official_url` LIKE 'https://uchubrew.shop-pro.jp/%';
