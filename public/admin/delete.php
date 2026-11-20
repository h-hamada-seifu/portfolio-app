<?php
/**
 * admin/delete.php
 * 管理画面：作品を1件削除する。
 *   GET  ?id=…  … 確認画面を出す（まだ消さない）
 *   POST        … 削除する → 管理画面の一覧へ移動
 *
 * 消すのは「データを変える操作」なので、POST のときだけ消す（テキスト 17.3）。
 * GET（リンクを踏んだだけ）で消すと、ブラウザの先読みや検索エンジンの巡回でも消える。
 *
 * TODO（演習⑩）: 削除の SQL を書く。「★ ここを書く」を探すこと。
 *
 * ★ この画面には認証（ログイン）が無い。12月にインターネットに公開する前に必ず扱う。
 */

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';

// どの作品を消すか。GET は URL の ?id=、POST はフォームの hidden から受け取る
$idText = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$id = (is_string($idText) && ctype_digit($idText)) ? (int) $idText : 0;

$work = false;
$technologyCount = 0;
$screenshotCount = 0;
$hasError = false;

try {
    $pdo = get_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ============================================================
        // ★ ここを書く（演習⑩）
        //
        // works から、この作品（$id）の行を1行だけ消す。2行で書けます。
        //   ・prepare() に DELETE の SQL を書く。消す行は WHERE id = :id で決める
        //   ・execute() で :id に $id を渡す
        //   ・変数名は $stmt にする（すぐ下の rowCount() で使う）
        //
        // ★ WHERE を書き忘れると、全部の作品が消えます（テキスト 18.4）。
        // ============================================================

        // ↓ここから
        $stmt = $pdo->prepare('DELETE FROM works WHERE id = :id');
        $stmt->execute([':id' => $id]);
        // ↑ここまで

        // rowCount() … 今の DELETE で消えた行の数。もう消えていた作品なら 0
        header('Location: index.php?deleted=' . $stmt->rowCount(), true, 303);
        exit;
    }

    // ---- 確認画面に出すもの ----
    $stmt = $pdo->prepare('SELECT id, slug, title FROM works WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $work = $stmt->fetch();

    if ($work !== false) {
        // 一緒に消えるもの（ON DELETE CASCADE）の数
        $stmt = $pdo->prepare('SELECT count(*) FROM work_technologies WHERE work_id = :id');
        $stmt->execute([':id' => $id]);
        $technologyCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT count(*) FROM screenshots WHERE work_id = :id');
        $stmt->execute([':id' => $id]);
        $screenshotCount = (int) $stmt->fetchColumn();
    }
} catch (Throwable $e) {
    error_log('[admin/delete.php] ' . get_class($e) . ': ' . $e->getMessage(), 4);
    $hasError = true;
}

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
    <title>作品を削除｜管理画面</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main>
    <h1>作品を削除</h1>
    <p><a href="index.php">← 管理画面の一覧に戻る（削除しない）</a></p>

<?php if ($hasError): ?>
    <p class="error">データベースに接続できませんでした。ターミナルのメッセージを確認してください。</p>
<?php elseif ($work === false): ?>
    <p>作品が見つかりません。</p>
<?php else: ?>
    <p class="danger">次の作品を削除します。<strong>元に戻せません。</strong></p>
    <table class="admin-table">
        <tr><th>作品名</th><td><?= h($work['title']) ?></td></tr>
        <tr><th>slug</th><td><?= h($work['slug']) ?></td></tr>
        <tr><th>一緒に消えるもの</th>
            <td>技術のつながり <?= h((string) $technologyCount) ?> 行・画像 <?= h((string) $screenshotCount) ?> 行
                （技術の名前そのものは残ります）</td></tr>
    </table>

    <form method="post">
        <input type="hidden" name="id" value="<?= h((string) $work['id']) ?>">
        <p><button type="submit" class="danger-button">削除する</button></p>
    </form>
<?php endif; ?>
</main>
</body>
</html>
