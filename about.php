<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$beers  = all_beers();
$makers = all_makers();
$styles = styles_used();
$title = 'このサイトについて';
$desc  = 'Darth Beer.com に何が載っていて、どうやって作っているかについて。';
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap doc">
  <div class="eyebrow">About</div>
  <h1 class="doc-h1">このサイトについて</h1>
  <p class="doc-lead">Darth Beer.com は、クラフトビールを「味わいの座標」で並べて探せるようにしたサイトです。私が個人で作っています。飲んだ人が次の一杯を選べるようにすること、それだけを目的にしています。</p>

  <h2>何が載っているか</h2>
  <div class="stat-row" style="margin:18px 0 26px">
    <div class="stat"><b class="grad"><?= count($beers) ?></b><span>ビール</span></div>
    <div class="stat"><b class="grad"><?= count($makers) ?></b><span>ブリュワリー</span></div>
    <div class="stat"><b class="grad"><?= count($styles) ?></b><span>掲載中のスタイル</span></div>
  </div>
  <p>それぞれのビールに、度数・苦味（IBU）・色・透明度・フルーティーさの5つの数値を持たせています。トップページの「味覚銀河」は、この数値をそのまま座標にして全銘柄を配置したものです。似た位置にある星は、似た味わいのビールです。</p>

  <h2>紹介文の書き方</h2>
  <p>ビール・ブリュワリー・スタイルの紹介文は、各ブリュワリーが公開している情報を読んだうえで、<strong>私が自分の言葉で書き下ろしています</strong>。どこかの文章をそのまま写すことはしていません。書くときに守っていることは次のとおりです。</p>
  <ul>
    <li><strong>事実と表現を分けています。</strong> 数値・原材料・発祥地といった事実は公開情報から取りますが、文章は自分で組み立てます。</li>
    <li><strong>書いたあとに、出典へ当たり直しています。</strong> 書いた事実が確認できるか、隣り合うスタイルときちんと書き分けられているかを点検してから公開しています。</li>
    <li><strong>確認できないことは書きません。</strong> 裏が取れなかった年号・人名・ホップの品種名は、書かずに落としています。もっともらしい話でも、確かめられなければ載せません。</li>
    <li><strong>推定した数値には印を付けます。</strong> 公表値が見つからない苦味などは、スタイルの一般的な値から推定したうえで「※」を付けています。公表されている値と混ぜません。</li>
    <li><strong>未計測は空欄にします。</strong> 数値が無いことを「0」とは書きません。未計測の項目は「—」と表示します。苦味がゼロのビールと、苦味を測っていないビールは別のものだからです。</li>
  </ul>

  <h2>写真を載せていない理由</h2>
  <p>商品の写真は取得も掲載もしていません。ビールの画像に見えるものは、そのビール自身の数値からブラウザ上で描いた図であって、実物の写真ではありません。</p>
  <p>他の方が撮った写真を、権利関係を確かめないまま載せたくないからです。ロゴについても、公式に利用条件が示されているもの以外は文字で表記しています。</p>

  <h2>情報の集め方</h2>
  <p>公開されている情報を参照するときは、各サイトの robots.txt と利用規約を尊重し、収集を禁じているサイトからは取得していません。ログインが必要なページからも取得していません。アクセスの間隔をあけ、相手のサーバに負担をかけないようにしています。</p>

  <h2>掲載されている方へ</h2>
  <p>ブリュワリーや商品の関係者の方で、記載内容の訂正や削除をご希望の場合は、<a href="https://docs.google.com/forms/d/e/1FAIpQLSc59D4Xn78uL0XDJ9Ztfu_Mp2yY2XJuBZtzbZqmY-7YN0XPcw/viewform?usp=header" target="_blank" rel="noopener nofollow">お問い合わせフォーム</a>からご連絡ください。確認のうえ対応します。</p>

  <h2>サイトの情報</h2>
  <div class="tablewrap">
  <table class="doc-table">
    <tbody>
      <tr><th>サイト名</th><td>Darth Beer.com</td></tr>
      <tr><th>運営</th><td>個人で運営しています。会社や団体ではありません。</td></tr>
      <tr><th>お問い合わせ</th><td><a href="https://docs.google.com/forms/d/e/1FAIpQLSc59D4Xn78uL0XDJ9Ztfu_Mp2yY2XJuBZtzbZqmY-7YN0XPcw/viewform?usp=header" target="_blank" rel="noopener nofollow">お問い合わせフォーム</a><br>
        <span class="doc-note">Google フォームが開きます。返信が必要な場合はメールアドレスをご記入ください。</span></td></tr>
      <tr><th>販売</th><td>商品の販売は行っていません。</td></tr>
    </tbody>
  </table>
  </div>

  <h2>お酒について</h2>
  <p>20歳未満の方の飲酒は法律で禁止されています。妊娠中・授乳期の飲酒、飲酒運転もおやめください。このサイトは、飲み比べ競争や一気飲みのような、無理な飲み方を勧めるものではありません。</p>

  <p class="doc-note"><a href="/privacy.php">プライバシーポリシー</a></p>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
