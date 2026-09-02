<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$title = 'プライバシーポリシー';
$desc  = 'Darth Beer.com のプライバシーポリシーと、利用者情報の外部送信についてのお知らせです。';
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap doc">
  <div class="eyebrow">Privacy</div>
  <h1 class="doc-h1">プライバシーポリシー</h1>
  <p class="doc-lead">Darth Beer.com（以下「当サイト」）における、利用者の情報の取り扱いについて定めたものです。</p>

  <h2>1. 当サイトが集める情報</h2>
  <p>当サイトは、閲覧のために会員登録を求めておらず、氏名・住所・電話番号といった情報を入力していただく仕組みを持ちません。</p>
  <p>お問い合わせフォーム（Google フォーム）をご利用いただいた場合は、そこに入力された内容が Google 社のサービス上に保存されます。当サイトはその内容を、お問い合わせへの対応にのみ使います。</p>

  <h2>2. 利用者情報の外部送信について</h2>
  <p>当サイトのページを表示すると、次の外部事業者へ利用者の情報が送信されます。電気通信事業法の外部送信規律にもとづき、送信先・送信される情報・利用目的を個別に示します。</p>

  <div class="tablewrap">
  <table class="doc-table">
    <thead><tr><th>送信先</th><th>送信されるページ</th><th>送信される情報</th><th>利用目的</th></tr></thead>
    <tbody>
      <tr>
        <td>Google LLC<br>（Google Fonts）</td>
        <td>すべてのページ</td>
        <td>IPアドレス、ブラウザの種類とバージョン、OSの種類、リファラ（直前に見ていたページのURL）</td>
        <td>ページで使用しているウェブフォントの配信</td>
      </tr>
      <tr>
        <td>Cloudflare, Inc.<br>（unpkg）</td>
        <td>ビール詳細・ブリュワリー詳細のうち、地図を表示するページ</td>
        <td>IPアドレス、ブラウザの種類とバージョン、OSの種類、リファラ</td>
        <td>地図表示に使うプログラム（Leaflet）の配信</td>
      </tr>
      <tr>
        <td>CARTO<br>（basemaps.cartocdn.com）</td>
        <td>ビール詳細・ブリュワリー詳細のうち、地図を表示するページ</td>
        <td>IPアドレス、ブラウザの種類とバージョン、OSの種類、リファラ、表示している地図の範囲</td>
        <td>地図画像（タイル）の配信</td>
      </tr>
      <tr>
        <td>Google LLC<br>（Google AdSense）</td>
        <td>すべてのページ</td>
        <td>IPアドレス、ブラウザの種類とバージョン、OSの種類、リファラ、Cookie に保存された識別子、閲覧したページのURL</td>
        <td>広告の配信と表示回数の計測、パーソナライズド広告の表示</td>
      </tr>
    </tbody>
  </table>
  </div>

  <p class="doc-note">これらの送信を望まない場合は、ブラウザの設定やアドオンで外部への通信を遮断することで停止できます。その場合、フォントや地図が正しく表示されないことがあります。</p>

  <h2>3. 広告について</h2>
  <p>当サイトは、第三者配信の広告サービス（Google AdSense）を利用しています。</p>
  <ul>
    <li>Google などの第三者配信事業者が Cookie を使用して、利用者が当サイトや他のウェブサイトに過去にアクセスした際の情報にもとづいて広告を配信します。</li>
    <li>Google およびそのパートナーは、この情報をもとにパーソナライズド広告を表示することがあります。</li>
    <li>パーソナライズド広告は、<a href="https://www.google.com/settings/ads" target="_blank" rel="noopener">Google の広告設定</a>で無効にできます。</li>
    <li>第三者配信事業者による Cookie の使用については、<a href="https://policies.google.com/technologies/ads?hl=ja" target="_blank" rel="noopener">Google の広告に関するポリシー</a>をご覧ください。</li>
  </ul>


  <h2>4. アクセス解析について</h2>
  <p>当サイトは、現時点でアクセス解析ツールを導入していません。導入した場合は、この項目と上の外部送信の表を更新します。</p>

  <h2>5. 掲載している情報について</h2>
  <p>当サイトのビール・ブリュワリー・スタイルの紹介文は、公開されている情報にもとづいて当サイトが書き下ろしたものです。他サイトの文章をそのまま転載していません。</p>
  <p>商品の写真は掲載していません。ビールの画像に見えるものは、そのビールの数値（色・度数・苦味・透明度）からブラウザ上で描いているもので、実物の写真ではありません。</p>
  <p>アルコール度数や苦味（IBU）の数値には、公表値が確認できず<strong>スタイルの一般的な値から推定したもの</strong>が含まれます。推定した値には画面上に「※」の印を付け、どの項目を推定したかを記録しています。</p>

  <h2>6. 年齢について</h2>
  <p>当サイトは酒類に関する情報を扱っています。20歳未満の方の飲酒は法律で禁止されています。また、妊娠中・授乳期の飲酒や、飲酒運転もおやめください。</p>

  <h2>7. 免責事項</h2>
  <p>掲載内容には正確を期していますが、内容の完全性・最新性を保証するものではありません。商品の仕様・販売状況は変わることがありますので、購入の判断は各ブリュワリーの公式情報をご確認ください。</p>
  <p>当サイトからリンクした外部サイトの内容について、当サイトは責任を負いません。</p>

  <h2>8. お問い合わせ</h2>
  <p>このポリシーに関するお問い合わせ、掲載内容の訂正のご依頼は、
     <a href="https://docs.google.com/forms/d/e/1FAIpQLSc59D4Xn78uL0XDJ9Ztfu_Mp2yY2XJuBZtzbZqmY-7YN0XPcw/viewform?usp=header" target="_blank" rel="noopener nofollow">お問い合わせフォーム</a>よりお寄せください。</p>
  <p>掲載しているブリュワリー・商品の関係者の方で、記載の修正や削除をご希望の場合も、
     同じフォームからご連絡ください。確認のうえ対応します。</p>

  <h2>9. 改定</h2>
  <p>このポリシーは必要に応じて改定します。改定した場合は、このページに最終更新日を示します。</p>
  <p class="doc-note">最終更新日: 2026年9月2日</p>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
