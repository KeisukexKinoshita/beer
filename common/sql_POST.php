<?php
require_once dirname(__FILE__).'/../db_config.local.php';
parse_str($value_http, $value);

$dbh = new PDO($db_dsn, $db_user, $db_pass, array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES   => false,
));

/**
 * 空文字を NULL にする。
 *
 * **0 を「未入力」の意味で使わない。** 旧実装は IBU の未入力を 0 として記録し、
 * 「苦味がゼロのビール」という存在しない事実をDBに残していた(旧データ15件が
 * 該当し、db/seeds/008_fix_ibu_zero_20260829.sql で NULL に直した)。
 * 未入力は NULL、0 は「本当に0」を意味する、という区別を守る。
 */
function nz($v) {
    return (isset($v) && $v !== '') ? $v : null;
}

if (isset($value['new_update']) && $value['new_update'] === 'new') {

    //------------------------------ 次の ProductID ------------------------------
    // 採番は MAX(ID) からの PHP 文字列インクリメント(件数からではない。欠番があるため)
    $res = $dbh->query("SELECT MAX(ProductID) AS m FROM products");
    $MAX_ProductID  = $res->fetch(PDO::FETCH_ASSOC)['m'];
    $NEXT_ProductID = ++$MAX_ProductID;
    $value['productid'] = $NEXT_ProductID; // 後段の rate_user で使う

    //------------------------------ スタイルの基準IBU ---------------------------
    // 旧実装は $value['styleid'] をクエリに直接連結しており、認証の無い公開
    // エンドポイント(post/check/thank/thank.php)から届く値をそのまま実行していた。
    // プレースホルダに置き換えてある。
    $stmt = $dbh->prepare("SELECT IBU FROM style WHERE StyleID = :styleid");
    $stmt->execute(array(':styleid' => $value['styleid']));
    $IBU_Style = $stmt->fetchColumn();
    if ($IBU_Style === false) { $IBU_Style = null; }

    //------------------------------ IBU の決め方 --------------------------------
    // 入力があれば実測値として IBU / IBU_all の両方に入れる。
    // 入力が無ければ IBU は NULL(未計測)、IBU_all はスタイルの基準値からの
    // **推定**とし、estimated_fields にその旨を必ず記録する。
    //
    // 旧実装は「IBU=0 の行すべて」に対して
    //   UPDATE products SET IBU_all=(select IBU_Style) where IBU=0.000
    // を ProductID の指定なしに走らせていた。投稿のたびに既存行の IBU_all が
    // 上書きされるうえ、推定したことがどこにも残らなかった。
    $ibu_in = nz(isset($value['ibu']) ? $value['ibu'] : null);
    if ($ibu_in !== null) {
        $IBU     = $ibu_in;
        $IBU_all = $ibu_in;
        $estimated_fields = null;
    } else {
        $IBU     = null;
        $IBU_all = $IBU_Style;
        $estimated_fields = ($IBU_Style !== null) ? 'IBU_all' : null;
    }

    //------------------------------ INSERT INTO products ------------------------
    // **列名を明示する。** 旧実装は列名を省いた位置依存の INSERT で 14 値しか
    // 渡しておらず、マイグレーション003で products が18列になった時点で
    // 列数不一致で失敗する状態だった(=フォームは既に動いていなかった)。
    $sql = "INSERT INTO products
              (ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU,
               Color, Clarity, Fruity, Favorite, ProductExplain, IBU_Style, Comment,
               source_url, image_rights, official_url, estimated_fields)
            VALUES
              (:ProductID, :MakerID, :ProductName, :StyleID, :Alcohol, :IBU_all, :IBU,
               :Color, :Clarity, :Fruity, :Favorite, :ProductExplain, :IBU_Style, :Comment,
               :source_url, :image_rights, :official_url, :estimated_fields)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
        ':ProductID'      => $NEXT_ProductID,
        ':MakerID'        => nz(isset($value['makerid'])        ? $value['makerid']        : null),
        ':ProductName'    => nz(isset($value['productname'])    ? $value['productname']    : null),
        ':StyleID'        => nz(isset($value['styleid'])        ? $value['styleid']        : null),
        ':Alcohol'        => nz(isset($value['alcohol'])        ? $value['alcohol']        : null),
        ':IBU_all'        => $IBU_all,
        ':IBU'            => $IBU,
        ':Color'          => nz(isset($value['color'])          ? $value['color']          : null),
        ':Clarity'        => nz(isset($value['clarity'])        ? $value['clarity']        : null),
        ':Fruity'         => nz(isset($value['fruity'])         ? $value['fruity']         : null),
        ':Favorite'       => nz(isset($value['favorite'])       ? $value['favorite']       : null),
        ':ProductExplain' => nz(isset($value['productexplain']) ? $value['productexplain'] : null),
        ':IBU_Style'      => $IBU_Style,
        ':Comment'        => nz(isset($value['comment'])        ? $value['comment']        : null),
        ':source_url'     => nz(isset($value['source_url'])     ? $value['source_url']     : null),
        // TODO(仕様9-1): このフォームは投稿者がアップロードした画像を img/product/ に
        //   移している。仕様9-1 は商品写真を扱わず生成プレースホルダーを標準とし、
        //   規律§2 は「区分が確定できない画像は載せない」と定めている。
        //   投稿者の画像は権利区分を確定できないため、ここでは NULL を入れる。
        //   画像アップロードそのものの扱いは別途判断が要る。
        ':image_rights'   => null,
        ':official_url'   => nz(isset($value['official_url'])   ? $value['official_url']   : null),
        ':estimated_fields' => $estimated_fields,
    ));

    //------------------------------ 画像の移動 ----------------------------------
    if (!empty($_POST['image_name'])) {
        $image_name_http = $_POST['image_name'];
        $image_rename    = $NEXT_ProductID.'.png';
        // 呼び出し元ページの階層に依存しないよう、このファイル基準の絶対パスで移動する
        // (旧実装は cwd 依存の '../img/...' で、post/check/thank/ 経由の投稿では常に失敗していた)
        $img_root = dirname(__FILE__).'/../img';
        // ディレクトリトラバーサル対策: ファイル名部分だけを使う
        $safe_name = basename($image_name_http);
        $src = $img_root.'/tmp/'.$safe_name;
        if (is_file($src)) {
            rename($src, $img_root.'/product/'.$image_rename);
        }
    }
}

//------------------------------ 次の Rate_userID --------------------------------
$res = $dbh->query("SELECT MAX(Rate_userID) AS m FROM rate_user");
$MAX_Rate_userID  = $res->fetch(PDO::FETCH_ASSOC)['m'];
$NEXT_Rate_userID = ++$MAX_Rate_userID;

//------------------------------ INSERT INTO rate_user ---------------------------
// こちらも列名を明示する。旧実装は位置依存で 9 値しか渡しておらず、
// rate_user は11列(Created_at / Updated_at を含む)なので同じく失敗していた。
// Created_at / Updated_at は既定値に任せるため列に含めない。
$sql = "INSERT INTO rate_user
          (Rate_userID, ProductID, UserID, Color_user, Clarity_user,
           Fruity_user, Favorite_user, New_Update, Comment)
        VALUES
          (:Rate_userID, :ProductID, :UserID, :Color_user, :Clarity_user,
           :Fruity_user, :Favorite_user, :New_Update, :Comment)";
$stmt = $dbh->prepare($sql);
$stmt->execute(array(
    ':Rate_userID'   => $NEXT_Rate_userID,
    ':ProductID'     => nz(isset($value['productid']) ? $value['productid'] : null),
    ':UserID'        => nz(isset($value['userid'])    ? $value['userid']    : null),
    ':Color_user'    => nz(isset($value['color'])     ? $value['color']     : null),
    ':Clarity_user'  => nz(isset($value['clarity'])   ? $value['clarity']   : null),
    ':Fruity_user'   => nz(isset($value['fruity'])    ? $value['fruity']    : null),
    ':Favorite_user' => nz(isset($value['favorite'])  ? $value['favorite']  : null),
    ':New_Update'    => nz(isset($value['new_update']) ? $value['new_update'] : null),
    ':Comment'       => nz(isset($value['comment'])   ? $value['comment']   : null),
));
?>
