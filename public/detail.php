<?php
// detail.php（★ 教員用の完成例。学生には配らない）
// 作品の詳細ページ。URL の ?slug=... で、どの作品を出すかを決める。
//
// 作例 sample-portfolio/works/library-system.html をコピーし、
// 中身をデータベースの値に置き換えたもの。テキスト 15.8〜15.9 のコードと同じ。
// テーブルは第7回の授業で作ったもの（week08/sql/create_tables.sql）に合わせてある。

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

// ?slug=... の値。無いとき・配列で渡されたとき（?slug[]=x）は空文字にする
$slug = $_GET['slug'] ?? '';
if (!is_string($slug)) {
    $slug = '';
}

$work = false;
$technologies = [];
$screenshots = [];
$hasError = false;

try {
    $pdo = get_pdo();

    // :slug がプレースホルダ。値は SQL の文字列に混ぜず、execute() で別に渡す
    $stmt = $pdo->prepare('SELECT * FROM works WHERE slug = :slug');
    $stmt->execute([':slug' => $slug]);
    $work = $stmt->fetch();   // 見つからなければ false

    if ($work !== false) {
        // 使った技術：work_technologies（つなぐ表）から technologies（名前の表）をたどる
        $stmt = $pdo->prepare(
            'SELECT t.name
             FROM work_technologies AS wt
             JOIN technologies AS t ON t.id = wt.technology_id
             WHERE wt.work_id = :work_id
             ORDER BY wt.sort_order, t.name'
        );
        $stmt->execute([':work_id' => $work['id']]);
        $technologies = $stmt->fetchAll();

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
    // 原因は画面に出さず、サーバーのログ（php -S のターミナル）にだけ残す
    error_log('[detail.php] ' . get_class($e) . ': ' . $e->getMessage(), 4);
    $hasError = true;
}

// ステータスコードは、HTML を1文字でも出す前に決める
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
    <title><?= $work !== false ? h($work['title']) : '作品が見つかりません' ?>｜作品ギャラリー</title>
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
    <h1>表示できませんでした</h1>
    <p>データベースに接続できませんでした。時間をおいて、もう一度開いてください。</p>

<?php elseif ($work === false): ?>
    <h1>作品が見つかりません</h1>
    <p>URL が間違っているか、作品が削除された可能性があります。</p>

<?php else: ?>
    <h1><?= h($work['title']) ?></h1>

    <p><?= h($work['summary']) ?></p>

    <table class="fact-table">
        <tr>
            <th>制作期間</th>
            <td><?= h(format_period($work['period_start'], $work['period_end'], $work['duration_weeks'])) ?></td>
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
                        <li><?= h($technology['name']) ?></li>
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
                <?php if ($screenshot['caption'] !== null): ?>
                    <figcaption><?= h($screenshot['caption']) ?></figcaption>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

    <p><a href="index.php">← 作品の一覧に戻る</a></p>
</main>

</body>
</html>
