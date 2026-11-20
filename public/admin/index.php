<?php
/**
 * admin/index.php
 * 管理画面の一覧：作品を並べ、それぞれに「詳細・編集・削除」のリンクを付ける（完成済み）。
 *
 * http://localhost:8000/admin/ で開く。見る人のための一覧（public/index.php）からはリンクしない。
 *
 * ★ この画面には認証（ログイン）が無い。12月にインターネットに公開する前に必ず扱う。
 */

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';

$works = [];
$hasError = false;

try {
    $pdo = get_pdo();
    $works = $pdo->query(
        'SELECT id, slug, title, updated_at
         FROM works
         ORDER BY sort_order, id'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('[admin/index.php] ' . get_class($e) . ': ' . $e->getMessage(), 4);
    $hasError = true;
}

// 削除のあと、delete.php から ?deleted=1 で戻ってくる
$deletedCount = $_GET['deleted'] ?? null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理画面</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main>
    <h1>管理画面</h1>
    <p><a href="new.php">＋ 作品を追加する</a>　／　<a href="../index.php">見る人のための一覧を開く</a></p>

    <?php if ($deletedCount === '1'): ?>
        <p class="notice">作品を1件削除しました。</p>
    <?php elseif ($deletedCount === '0'): ?>
        <p class="error">削除する作品が見つかりませんでした（もう削除されていた可能性があります）。</p>
    <?php endif; ?>

    <?php if ($hasError): ?>
        <p class="error">データベースに接続できませんでした。ターミナルのメッセージを確認してください。</p>
    <?php elseif (count($works) === 0): ?>
        <p>作品がまだありません。</p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th>id</th>
                <th>作品名</th>
                <th>slug</th>
                <th>最後に更新した日時</th>
                <th>操作</th>
            </tr>
            <?php foreach ($works as $work): ?>
                <tr>
                    <td><?= h((string) $work['id']) ?></td>
                    <td><?= h($work['title']) ?></td>
                    <td><?= h($work['slug']) ?></td>
                    <td><?= h(date('Y-m-d H:i', strtotime($work['updated_at']))) ?></td>
                    <td>
                        <a href="../detail.php?slug=<?= h(urlencode($work['slug'])) ?>">詳細</a>
                        ／ <a href="edit.php?id=<?= h((string) $work['id']) ?>">編集</a>
                        ／ <a href="delete.php?id=<?= h((string) $work['id']) ?>">削除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
