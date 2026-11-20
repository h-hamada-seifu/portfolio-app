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

// ① ?slug=... を受け取る（無いとき・配列で渡されたときは空文字にする）
$slugInput = $_GET['slug'] ?? '';
$slug = is_string($slugInput) ? $slugInput : '';

$work = false;
$technologies = [];
$screenshots = [];
$hasError = false;

try {
    $pdo = get_pdo();

    // ② works から slug で1件取り出す（値はプレースホルダで渡す）
    $stmt = $pdo->prepare(
        'SELECT id, slug, title, summary, period_start, period_end, duration_weeks, team_size,
                source_url, purpose, my_role, challenge, highlight, next_step
         FROM works
         WHERE slug = :slug'
    );
    $stmt->execute([':slug' => $slug]);
    $work = $stmt->fetch();

    // ③ 見つかったら、使った技術と画面の画像も取り出す
    if ($work !== false) {
        $stmt = $pdo->prepare(
            'SELECT t.name
             FROM work_technologies AS wt
             JOIN technologies AS t ON t.id = wt.technology_id
             WHERE wt.work_id = :work_id
             ORDER BY wt.sort_order, t.name'
        );
        $stmt->execute([':work_id' => $work['id']]);
        $technologies = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare(
            'SELECT image_path, alt_text, caption
             FROM screenshots
             WHERE work_id = :work_id
             ORDER BY sort_order, id'
        );
        $stmt->execute([':work_id' => $work['id']]);
        $screenshots = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    // 原因は画面に出さず、サーバーのログにだけ残す
    error_log('[detail.php] ' . get_class($e) . ': ' . $e->getMessage(), 4);
    $hasError = true;
}

// ④ ステータスコードは HTML を出す前に決める
if ($hasError) {
    http_response_code(500);
} elseif ($work === false) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ($work !== false) ? h($work['title']) : '作品が見つかりません' ?>｜作品ギャラリー</title>
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
<?php if ($hasError): ?>
    <p>データベースに接続できませんでした。</p>
<?php elseif ($work === false): ?>
    <h1>作品が見つかりません</h1>
    <p>URL が間違っているか、作品が削除された可能性があります。</p>
<?php else: ?>
    <h1><?= h($work['title']) ?></h1>

    <p><?= h($work['summary']) ?></p>

    <table class="fact-table">
        <tr>
            <th>制作期間</th>
            <td><?= h(format_period(
                $work['period_start'],
                $work['period_end'],
                $work['duration_weeks'] === null ? null : (int) $work['duration_weeks']
            )) ?></td>
        </tr>
        <tr>
            <th>人数</th>
            <td><?= h((string) $work['team_size']) ?>人</td>
        </tr>
        <tr>
            <th>使った技術</th>
            <td>
                <ul class="tech-list">
                    <?php foreach ($technologies as $technology): ?>
                        <li><?= h($technology) ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
        <?php if ($work['source_url'] !== null): ?>
        <tr>
            <th>ソースコード</th>
            <td><a href="<?= h($work['source_url']) ?>"><?= h($work['source_url']) ?></a></td>
        </tr>
        <?php endif; ?>
    </table>

    <h2>何のために作ったか</h2>
    <p><?= nl2br(h($work['purpose'])) ?></p>

    <h2>自分の担当</h2>
    <p><?= nl2br(h($work['my_role'])) ?></p>

    <h2>一番詰まったところと、どう解いたか</h2>
    <p><?= nl2br(h($work['challenge'])) ?></p>

    <h2>工夫したところ</h2>
    <p><?= nl2br(h($work['highlight'])) ?></p>

    <h2>次に直すなら</h2>
    <p><?= nl2br(h($work['next_step'])) ?></p>

    <?php if (count($screenshots) > 0): ?>
    <h2>画面</h2>
    <div class="screenshots">
        <?php foreach ($screenshots as $screenshot): ?>
        <figure>
            <img src="<?= h($screenshot['image_path']) ?>" alt="<?= h($screenshot['alt_text']) ?>">
            <figcaption><?= h($screenshot['caption']) ?></figcaption>
        </figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

    <p><a href="index.php">← 作品の一覧に戻る</a></p>
</main>

</body>
</html>
