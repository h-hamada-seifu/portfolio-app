<?php
/**
 * detail.php
 * 作品の詳細ページ。URL の ?slug=... で、どの作品を出すかを決める。
 *
 * いまは作例「図書の貸出管理システム」の中身が、HTML に直接書いてあります。
 * ブラウザで http://localhost:8000/detail.php を開くと、静的なページとして出ます。
 * ここから、中身をデータベースの値に差し替えていきます。
 *
 * TODO（演習②・15分）: このコメントの下に、データベースから1件取り出す PHP を書く。
 *   ① ?slug=... を受け取る（無いとき・配列で渡されたときは空文字にする）
 *   ② works から slug で1件取り出す。値は SQL に混ぜず、プレースホルダで渡す
 *   ③ 見つかったら、使った技術（work_technologies と technologies を JOIN）と
 *      画面の画像（screenshots）も取り出す
 *   ④ 接続できなかったら 500、見つからなかったら 404 を返す。
 *      ステータスコードは HTML を1文字でも出す前に決める
 *   → テキスト 15.3〜15.6 と 15.8 を見ること。AI は使わないこと
 *
 * TODO（演習③・15分）: 下の HTML の静的な文章を、<?= h($work['カラム名']) ?> に置き換える。
 *   置き換える場所は「★ 演習③」と書いてある行。
 *   第7回ワークシートの「7. 次回の準備」の表が、どこを何に置き換えるかの一覧になっている。
 */

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

// ここから下に演習②のコードを書く

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ★ 演習③：作品名にする（見つからなかったときは「作品が見つかりません」） -->
    <title>図書の貸出管理システム｜作品ギャラリー</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="inner">
        <h1>作品ギャラリー</h1>
        <nav>
            <a href="index.php">作品</a>
        </nav>
    </div>
</header>

<main>
    <!-- ★ 演習③：接続できなかったとき・見つからなかったときの表示を、ここで分ける -->

    <!-- ★ 演習③：title -->
    <h1>図書の貸出管理システム</h1>

    <!-- ★ 演習③：summary -->
    <p>部活の本棚の貸出を記録し、返却が遅れている本を一覧で出す Web アプリ。</p>

    <table class="fact-table">
        <tr>
            <th>制作期間</th>
            <!-- ★ 演習③：format_period($work['period_start'], $work['period_end'], $work['duration_weeks']) -->
            <td>2026年4月〜2026年7月（14週間）</td>
        </tr>
        <tr>
            <th>人数</th>
            <!-- ★ 演習③：team_size -->
            <td>1人</td>
        </tr>
        <tr>
            <th>使った技術</th>
            <td>
                <!-- ★ 演習③：$technologies を foreach で回す -->
                <ul class="tech-list">
                    <li>PHP</li>
                    <li>MySQL</li>
                    <li>Bootstrap</li>
                </ul>
            </td>
        </tr>
        <!-- ★ 演習③：source_url が NULL の作品（shift-app）では、この行ごと出さない -->
        <tr>
            <th>ソースコード</th>
            <td><a href="https://example.com/mihon-taro/library-system">https://example.com/mihon-taro/library-system</a></td>
        </tr>
    </table>

    <h2>何のために作ったか</h2>
    <!-- ★ 演習③：purpose（改行を残すので nl2br(h(...)) を使う） -->
    <p>
        所属している部の本棚には本が420冊あり、貸出はノートに手書きで記録していました。
        書いた字が読めなかったり、書き忘れたりして、誰が借りたか分からなくなることがよくありました。
        年度末にノートを Excel に打ち直して確認していましたが、毎年3〜4冊が行方不明のままでした。
        借りた人と返却期限をその場で記録して、遅れている本をいつでも一覧で見られるようにしたいと思って作りました。
    </p>

    <h2>自分の担当</h2>
    <!-- ★ 演習③：my_role -->
    <p>
        1人で作ったので全部です。テーブルの設計、画面、貸出と返却の処理の順に作りました。
        見た目は Bootstrap をそのまま使い、自分では CSS をほとんど書いていません。
    </p>

    <h2>一番詰まったところと、どう解いたか</h2>
    <!-- ★ 演習③：challenge -->
    <p>
        同じ本を2冊持っている場合に、2冊目を登録すると1冊目の記録が消えました。
        本の ISBN を主キーにしていたので、2冊目を登録すると
        <code>Duplicate entry '978-4-0000-0000-0' for key 'PRIMARY'</code>
        というエラーが出ます。これを避けるために、同じ ISBN のときは上書きする書き方にしていたことが原因でした。
        前期の授業で「1つの行が1つのものを表すようにする」と習ったことを思い出して、
        本の情報を入れる <code>books</code> と、1冊ごとの在庫を入れる <code>book_copies</code> にテーブルを分けました。
        貸出の記録は <code>book_copies</code> の側につなぎ直しました。
        分けたあとは、同じ本が何冊あっても1冊ずつ別に扱えるようになりました。
    </p>
    <!-- ★ 演習③のあと、この <code> タグは消えます。データベースには文章だけが入っていて、
         HTML のタグは入っていないからです。なぜそうしてあるかは、演習⑨で確かめます。 -->


    <h2>工夫したところ</h2>
    <!-- ★ 演習③：highlight -->
    <p>
        ログインしたあと最初に出る画面を、貸出の登録ではなく「返却が遅れている本の一覧」にしました。
        貸出の画面を探しに行かなくても、遅れている本が目に入るようにしたかったからです。
        遅れている日数は画面で赤くして、何日遅れているかを数えなくても分かるようにしました。
    </p>

    <h2>次に直すなら</h2>
    <!-- ★ 演習③：next_step -->
    <p>
        返却期限の3日前にメールを送る処理を入れたいです。
        いまは画面を見に来ない人には何も伝わらないので、遅れに気づくのが結局返却日のあとになってしまいます。
    </p>

    <h2>画面</h2>
    <!-- ★ 演習③：$screenshots を foreach で回す。1枚も無い作品では <h2>画面</h2> ごと出さない -->
    <div class="screenshots">
        <figure>
            <img src="images/library-overdue.svg" alt="返却が遅れている本が3件並んでいる一覧画面">
            <figcaption>ログイン後の最初の画面。遅れている本と日数が出る</figcaption>
        </figure>
        <figure>
            <img src="images/library-lend.svg" alt="本を選んで借りる人と返却期限を入れる貸出登録画面">
            <figcaption>貸出の登録画面。同じ本の1冊目と2冊目を別に選べる</figcaption>
        </figure>
    </div>

    <p><a href="index.php">← 作品の一覧に戻る</a></p>
</main>

</body>
</html>
