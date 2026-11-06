<?php
/**
 * index.php
 * 作品一覧。works テーブルから作品を読み、1件ずつ並べる。
 *
 * 第7回の演習⑥で作ったものに、第8回で使うカラムを足した状態です。
 * 自分で作った index.php がある人は、こちらで上書きせず、
 * SELECT のカラムと ORDER BY だけを自分のものに写してください。
 *
 * TODO（演習⑤・5分）:
 *   - 作品名を、詳細ページへのリンクにする
 */

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$works = [];
$hasError = false;

try {
    $pdo = get_pdo();
    $works = $pdo->query(
        'SELECT slug, title, summary, period_start, period_end, duration_weeks, team_size
         FROM works
         ORDER BY sort_order, id'
    )->fetchAll();
} catch (Throwable $e) {
    // 原因（ホスト名やユーザー名を含むことがある）は画面に出さず、サーバーのログにだけ残す。
    // 第2引数の 4 は「サーバーのログに直接送る」。php -S なら起動したターミナルに表示される。
    error_log('[index.php] ' . get_class($e) . ': ' . $e->getMessage(), 4);
    $hasError = true;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>作品一覧｜作品ギャラリー</title>
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
    <h2>作品</h2>

    <?php if ($hasError): ?>
        <p>データベースに接続できませんでした。</p>
    <?php elseif (count($works) === 0): ?>
        <p>作品がまだありません。</p>
    <?php else: ?>
        <ul class="work-list">
            <?php foreach ($works as $work): ?>
                <li class="work-card">
                    <?php /* TODO（演習⑤）: 下の <h3> の中の作品名を、detail.php へのリンクにする。
                            ・リンク先は detail.php?slug=... の形
                            ・slug は URL に入れるので urlencode() を通し、さらに h() も通す
                            ・テキスト 15.11 を見ること */ ?>
                    <h3><?= h($work['title']) ?></h3>
                    <p class="meta"><?= h(format_period($work['period_start'], $work['period_end'], $work['duration_weeks'])) ?>／<?= h((string) $work['team_size']) ?>人で制作</p>
                    <p class="summary"><?= h($work['summary']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

</body>
</html>
