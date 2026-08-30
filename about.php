<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$beers  = all_beers();
$makers = all_makers();
$styles = styles_used();
$title = 'このサイトについて';
$desc  = 'Darth Beer.com の運営者情報と、掲載データの作り方・編集方針です。';
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap doc">
  <div class="eyebrow">About</div>
  <h1 class="doc-h1">このサイトについて</h1>
  <p class="doc-lead">Darth Beer.com は、クラフトビールを「味わいの座標」で並べて探せるようにした、非営利の個人サイトです。売ることはせず、飲んだ人が次の一杯を選べることだけを目的にしています。</p>

  <h2>何が載っているか</h2>
  <div class="stat-row" style="margin:18px 0 26px">
    <div class="stat"><b class="grad"><?= count($beers) ?></b><span>ビール</span></div>
    <div class="stat"><b class="grad"><?= count($makers) ?></b><span>ブリュワリー</span></div>
    <div class="stat"><b class="grad"><?= count($styles) ?></b><span>掲載中のスタイル</span></div>
  </div>
  <p>それぞれのビールに、度数・苦味（IBU）・色・透明度・フルーティーさの5つの数値を持たせています。トップページの「味覚銀河」は、この数値をそのまま座標にして全銘柄を配置したものです。似た位置にある星は、似た味わいのビールです。</p>

  <h2>掲載データの作り方</h2>
  <p>紹介文は、各ブリュワリーが公開している情報を読んで<strong>当サイトが自分の言葉で書き下ろしています</strong>。他サイトの文章をそのまま写すことはしていません。</p>
  <ul>
    <li><strong>事実と表現を分けています。</strong> 数値・原材料・発祥地といった事実は公開情報から取りますが、文章は当サイトで組み立てます。</li>
    <li><strong>書いたものは必ず別の目で検証しています。</strong> 出典に当たり直し、書かれている事実が確認できるか、隣接するスタイルと区別がついているかを点検したうえで公開しています。</li>
    <li><strong>確認できないことは書きません。</strong> 出典で裏が取れない年号・人名・品種名は、書かずに落としています。</li>
    <li><strong>推定した数値には印を付けます。</strong> 公表値が見つからない苦味などは、スタイルの一般的な値から推定して「※」を付けています。推定していない値と混ぜません。</li>
    <li><strong>未計測は空欄にします。</strong> 数値がないことを「0」とは書きません。未計測の項目は「—」と表示します。</li>
  </ul>

  <h2>写真を載せていない理由</h2>
  <p>当サイトは商品の写真を取得・掲載していません。ビールの画像に見えるものは、そのビール自身の数値からブラウザ上で描いた図で、実物の写真ではありません。</p>
  <p>他者が撮った写真の権利関係を確定できないまま掲載することを避けるためで、ロゴについても、公式に利用条件が示されているもの以外は文字で表記しています。</p>

  <h2>情報の集め方について</h2>
  <p>公開されている情報を参照する際は、各サイトの robots.txt と利用規約を尊重し、収集を禁じているサイトからは取得していません。ログインが必要なページからも取得していません。アクセスの間隔をあけ、相手のサーバに負担をかけない方法で参照しています。</p>

  <h2>運営者</h2>
  <div class="tablewrap">
  <table class="doc-table">
    <tbody>
      <tr><th>サイト名</th><td>Darth Beer.com</td></tr>
      <tr><th>運営者</th><td><!-- TODO(公開前): 表示名を入れる -->（準備中）</td></tr>
      <tr><th>お問い合わせ</th><td><a href="https://docs.google.com/forms/d/e/1FAIpQLScgFCJ3dqGIYd5M2V3rX_mEuOQ8g34xY9siXF7OOLAQ4wj4RA/viewform?usp=sf_link" target="_blank" rel="noopener nofollow">お問い合わせフォーム</a><br>
        <span class="doc-note">Google フォームが開きます。返信が必要な場合はメールアドレスをご記入ください。</span></td></tr>
      <tr><th>営利性</th><td>非営利の個人サイトです。商品の販売は行っていません。</td></tr>
    </tbody>
  </table>
  </div>

  <h2>お酒に関するお願い</h2>
  <p>20歳未満の方の飲酒は法律で禁止されています。妊娠中・授乳期の飲酒、飲酒運転もおやめください。当サイトは、飲み比べ競争や一気飲みなど、無理な飲み方を勧めるものではありません。</p>

  <p class="doc-note"><a href="/privacy.php">プライバシーポリシー</a></p>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
