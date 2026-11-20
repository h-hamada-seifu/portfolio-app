<?php
/**
 * admin/edit.php
 * 管理画面：作品を1件編集する（第9回の課題で使う。完成済み）。
 *   GET  ?id=…  … データベースの値を入れたフォームを出す
 *   POST        … 入力をチェック → works を UPDATE、技術のつながりを入れ直す → 詳細ページへ移動
 *
 * new.php との違いは3つ（テキスト 18.5）。
 *   ① 最初にフォームに入れておく値が、空ではなく「いまデータベースに入っている値」
 *   ② INSERT ではなく UPDATE。WHERE id = :id で「どの作品を」を必ず決める
 *   ③ 技術は「つなぐ表の行を全部消して、入れ直す」。updated_at も今の時刻にする
 *
 * slug は URL なので、編集では変えさせない（変えると、どこかに貼ったリンクが 404 になる）。
 *
 * ★ この画面には認証（ログイン）が無い。12月にインターネットに公開する前に必ず扱う。
 */

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../lib/work_form.php';

// どの作品を編集するか。GET は URL の ?id=、POST はフォームの hidden から受け取る
$idText = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$id = (is_string($idText) && ctype_digit($idText)) ? (int) $idText : 0;

$values = empty_work_values();
$errors = [];
$isNew = false;
$work = false;
$hasError = false;

try {
    $pdo = get_pdo();

    // いまデータベースに入っている値（GET でも POST でも、作品があるかを確かめるために読む）
    $stmt = $pdo->prepare('SELECT * FROM works WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $work = $stmt->fetch();

    if ($work !== false) {
        $stmt = $pdo->prepare(
            'SELECT t.name
             FROM work_technologies AS wt
             JOIN technologies AS t ON t.id = wt.technology_id
             WHERE wt.work_id = :work_id
             ORDER BY wt.sort_order, t.name'
        );
        $stmt->execute([':work_id' => $id]);
        $technologyNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // フォームに入れておく値。NULL は空欄、数は文字にする
        foreach (array_keys($values) as $key) {
            if ($key !== 'technologies') {
                $values[$key] = (string) ($work[$key] ?? '');
            }
        }
        $values['technologies'] = implode("\n", $technologyNames);
    }

    if ($work !== false && $_SERVER['REQUEST_METHOD'] === 'POST') {
        [$values, $technologies, $errors] = read_work_input($_POST, $values, false);

        if (count($errors) === 0) {
            $pdo->beginTransaction();

            // ② UPDATE。WHERE id = :id が無いと、全部の作品が同じ中身になる
            $stmt = $pdo->prepare(
                'UPDATE works SET
                    title = :title, summary = :summary,
                    period_start = :period_start, period_end = :period_end, duration_weeks = :duration_weeks,
                    team_size = :team_size, source_url = :source_url,
                    purpose = :purpose, my_role = :my_role, challenge = :challenge,
                    highlight = :highlight, next_step = :next_step,
                    updated_at = now()
                 WHERE id = :id'
            );
            $stmt->execute([':id' => $id] + work_params($values));

            // ③ 技術のつながりを全部消して、フォームに書いたとおりに入れ直す
            //    （1つずつ「増えた・減った・順番が変わった」を比べるより、確実で短い）
            $stmt = $pdo->prepare('DELETE FROM work_technologies WHERE work_id = :work_id');
            $stmt->execute([':work_id' => $id]);
            save_technologies($pdo, $id, $technologies);

            $pdo->commit();

            header('Location: ../detail.php?slug=' . urlencode($values['slug']), true, 303);
            exit;
        }
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[admin/edit.php] ' . get_class($e) . ': ' . $e->getMessage(), 4);
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
    <title>作品を編集｜管理画面</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main>
    <h1>作品を編集</h1>
    <p><a href="index.php">← 管理画面の一覧に戻る</a></p>

<?php if ($hasError): ?>
    <p class="error">データベースに接続できなかったか、保存できませんでした。ターミナルのメッセージを確認してください。</p>
<?php elseif ($work === false): ?>
    <p>作品が見つかりません。</p>
<?php else: ?>
    <form method="post" class="admin-form">
        <input type="hidden" name="id" value="<?= h((string) $id) ?>">

        <?php require __DIR__ . '/../../lib/work_fields.php'; ?>

        <p><button type="submit">保存する</button></p>
    </form>
<?php endif; ?>
</main>
</body>
</html>
