<?php
/**
 * admin/new.php
 * 管理画面：作品を1件追加する。
 *   GET  … 空のフォームを出す
 *   POST … 入力をチェック → works・technologies・work_technologies に INSERT → 詳細ページへ移動
 *
 * 入力のチェックは lib/work_form.php の read_work_input()（テキスト 17.4）、
 * 技術を入れるところは同じファイルの save_technologies()（テキスト 17.5.1）。
 *
 * ★ この画面には認証（ログイン）が無い。手元（localhost）でしか動かさないので今は可。
 *   12月にインターネットに公開する前に必ず扱う。
 */

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../lib/work_form.php';

$values = empty_work_values();
$errors = [];
$isNew = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$values, $technologies, $errors] = read_work_input($_POST, $values, true);

    // ---- 保存：3つのテーブルへの INSERT を1つのトランザクションにする ----
    if (count($errors) === 0) {
        $pdo = null;
        try {
            $pdo = get_pdo();
            $pdo->beginTransaction();

            // (1) works に1行。RETURNING id で、今入れた行の番号を返してもらう
            $stmt = $pdo->prepare(
                'INSERT INTO works
                    (slug, title, summary, period_start, period_end, duration_weeks, team_size,
                     source_url, purpose, my_role, challenge, highlight, next_step)
                 VALUES
                    (:slug, :title, :summary, :period_start, :period_end, :duration_weeks, :team_size,
                     :source_url, :purpose, :my_role, :challenge, :highlight, :next_step)
                 RETURNING id'
            );
            $stmt->execute([':slug' => $values['slug']] + work_params($values));
            $workId = (int) $stmt->fetchColumn();

            // (2) 使った技術を入れる（lib/work_form.php）
            save_technologies($pdo, $workId, $technologies);

            $pdo->commit();

            // 保存できたら、その作品の詳細ページへ移動させる（再読み込みで二重に入らないように）
            header('Location: ../detail.php?slug=' . urlencode($values['slug']), true, 303);
            exit;
        } catch (Throwable $e) {
            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof PDOException && $e->getCode() === '23505') {
                // 23505 = UNIQUE 違反。技術は先に探してから足し、重複も除いてあるので、
                // ここで 23505 になるのは works の slug だけ
                $errors['slug'] = 'この slug はもう使われています。別の名前にしてください。';
            } else {
                error_log('[admin/new.php] ' . get_class($e) . ': ' . $e->getMessage(), 4);
                $errors['form'] = '保存できませんでした。ターミナルのメッセージを確認してください。';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>作品を追加｜管理画面</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main>
    <h1>作品を追加</h1>
    <p><a href="index.php">← 管理画面の一覧に戻る</a></p>

    <?php show_error($errors, 'form'); ?>

    <form method="post" class="admin-form">
        <?php require __DIR__ . '/../../lib/work_fields.php'; ?>

        <p><button type="submit">追加する</button></p>
    </form>
</main>
</body>
</html>
