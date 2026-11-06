<?php
/**
 * admin/new.php
 * 管理画面：作品を1件追加する。
 *   GET  … 空のフォームを出す
 *   POST … 入力をチェック → works・technologies・work_technologies に INSERT → 詳細ページへ移動
 *
 * 入力のチェック（16.4）と、works への INSERT（16.5）は書いてあります。
 * 読んで、何をしているか説明できるようにしてください。ワークシートの「4」で聞きます。
 *
 * TODO（演習⑦・20分）: 「技術を1つずつ入れる」ところを書く。
 *   下の「★ ここを書く」を探してください。やることは3つです。
 *     ① technologies に同じ名前があるか探す（大文字小文字は区別しない）
 *     ② 無ければ technologies に足して、番号をもらう
 *     ③ work_technologies に「この作品の番号 ＋ その技術の番号」を1行入れる
 *   → テキスト 16.5 の図（①②の分かれ道）を見ること
 *
 * ★ この画面には認証（ログイン）が無い。手元（localhost）でしか動かさないので今は可。
 *   12月にインターネットに公開する前に必ず扱う。
 */

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';

// 'YYYY-MM-DD' の形で、実在する日付なら true（2026-02-30 などは false）
function is_valid_date(string $text): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $text);
    return $date !== false && $date->format('Y-m-d') === $text;
}

// エラーがあれば、その入力欄の下に出す
function show_error(array $errors, string $key): void
{
    if (isset($errors[$key])) {
        echo '<p class="error">' . h($errors[$key]) . '</p>';
    }
}

// フォームの入力欄。最初は空（人数だけ 1）
$values = [
    'slug'           => '',
    'title'          => '',
    'summary'        => '',
    'period_start'   => '',
    'period_end'     => '',
    'duration_weeks' => '',
    'team_size'      => '1',
    'source_url'     => '',
    'purpose'        => '',
    'my_role'        => '',
    'challenge'      => '',
    'highlight'      => '',
    'next_step'      => '',
    'technologies'   => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- 受け取る：前後の空白を落とす（配列で送られてきたら空にする） ----
    foreach (array_keys($values) as $key) {
        $input = $_POST[$key] ?? '';
        $values[$key] = is_string($input) ? trim($input) : '';
    }

    // 使った技術：1行に1つ。空行と重複を除く
    // 「PHP」と「php」は同じ技術として扱う（technologies の UNIQUE も大文字小文字を区別しない）
    $technologies = [];
    foreach (preg_split('/\R/u', $values['technologies']) as $line) {
        $name = trim($line);
        $key = mb_strtolower($name);
        if ($name !== '' && !isset($technologies[$key])) {
            $technologies[$key] = $name;
        }
    }

    // ---- チェック①：必須（終了日・週数・ソースコードの URL 以外） ----
    $labels = [
        'slug'         => 'slug',
        'title'        => '作品名',
        'summary'      => '一言の説明',
        'period_start' => '作り始めた日',
        'team_size'    => '人数',
        'purpose'      => '何のために作ったか',
        'my_role'      => '自分の担当',
        'challenge'    => '詰まったところ',
        'highlight'    => '工夫したところ',
        'next_step'    => '次に直すなら',
    ];
    foreach ($labels as $key => $label) {
        if ($values[$key] === '') {
            $errors[$key] = "{$label}を入力してください。";
        }
    }
    if (count($technologies) === 0) {
        $errors['technologies'] = '使った技術を1つ以上入力してください。';
    }

    // ---- チェック②：slug の形 ----
    // 英小文字・数字・ハイフンだけ。先頭と末尾はハイフン不可、ハイフンの連続も不可
    if (!isset($errors['slug']) && !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $values['slug'])) {
        $errors['slug'] = 'slug は半角の英小文字・数字・ハイフンだけで書いてください（例：library-system）。';
    }

    // ---- チェック③：日付・数・URL・長さ ----
    if (!isset($errors['period_start']) && !is_valid_date($values['period_start'])) {
        $errors['period_start'] = '作り始めた日は 2026-04-01 の形で入力してください。';
    }
    if ($values['period_end'] !== '') {
        if (!is_valid_date($values['period_end'])) {
            $errors['period_end'] = '作り終えた日は 2026-07-31 の形で入力してください。';
        } elseif (!isset($errors['period_start']) && $values['period_end'] < $values['period_start']) {
            $errors['period_end'] = '作り終えた日は、作り始めた日より後にしてください。';
        }
    }
    if (!isset($errors['team_size'])
        && (!ctype_digit($values['team_size']) || (int) $values['team_size'] < 1 || (int) $values['team_size'] > 999)) {
        $errors['team_size'] = '人数は1〜999の数字で入力してください。';
    }
    if ($values['duration_weeks'] !== ''
        && (!ctype_digit($values['duration_weeks']) || (int) $values['duration_weeks'] < 1 || (int) $values['duration_weeks'] > 999)) {
        $errors['duration_weeks'] = '週数は1〜999の数字で入力してください（分からなければ空のまま）。';
    }
    if ($values['source_url'] !== '' && !preg_match('#\Ahttps?://#', $values['source_url'])) {
        $errors['source_url'] = 'ソースコードの URL は https:// か http:// で始めてください。';
    }
    $maxLengths = ['slug' => 100, 'title' => 200];   // CREATE TABLE の varchar(100)・varchar(200) と同じ
    foreach ($maxLengths as $key => $max) {
        if (!isset($errors[$key]) && mb_strlen($values[$key]) > $max) {
            $errors[$key] = "{$max}文字以内で入力してください。";
        }
    }
    foreach ($technologies as $name) {
        if (mb_strlen($name) > 50) {   // technologies.name は varchar(50)
            $errors['technologies'] = '技術の名前は、1つ50文字以内で入力してください。';
        }
    }

    // ---- 保存：3つのテーブルへの INSERT を1つのトランザクションにする ----
    if (count($errors) === 0) {
        $pdo = null;
        try {
            $pdo = get_pdo();
            $pdo->beginTransaction();

            // (1) works に1行。空の欄は NULL にする（「無い」は NULL で表す）
            $stmt = $pdo->prepare(
                'INSERT INTO works
                    (slug, title, summary, period_start, period_end, duration_weeks, team_size,
                     source_url, purpose, my_role, challenge, highlight, next_step)
                 VALUES
                    (:slug, :title, :summary, :period_start, :period_end, :duration_weeks, :team_size,
                     :source_url, :purpose, :my_role, :challenge, :highlight, :next_step)
                 RETURNING id'
            );
            $stmt->execute([
                ':slug'           => $values['slug'],
                ':title'          => $values['title'],
                ':summary'        => $values['summary'],
                ':period_start'   => $values['period_start'],
                ':period_end'     => $values['period_end'] === '' ? null : $values['period_end'],
                ':duration_weeks' => $values['duration_weeks'] === '' ? null : (int) $values['duration_weeks'],
                ':team_size'      => (int) $values['team_size'],
                ':source_url'     => $values['source_url'] === '' ? null : $values['source_url'],
                ':purpose'        => $values['purpose'],
                ':my_role'        => $values['my_role'],
                ':challenge'      => $values['challenge'],
                ':highlight'      => $values['highlight'],
                ':next_step'      => $values['next_step'],
            ]);
            $workId = $stmt->fetchColumn();   // RETURNING id で返ってきた、新しい作品の番号

            // ============================================================
            // ★ ここを書く（演習⑦）
            //
            // $technologies には、フォームに書かれた技術の名前が入っています。
            //   例：['php' => 'PHP', 'laravel' => 'Laravel']
            //   （キーが小文字になっているのは、PHP と php を1つにまとめるためです）
            //
            // この1つずつについて、次をやります。
            //   ① technologies に同じ名前があるか探す
            //        SELECT id FROM technologies WHERE lower(name) = lower(:name)
            //        → 見つからなければ fetchColumn() は false を返します
            //   ② 無ければ足して、番号をもらう
            //        INSERT INTO technologies (name) VALUES (:name) RETURNING id
            //   ③ work_technologies に1行入れる
            //        INSERT INTO work_technologies (work_id, technology_id, sort_order)
            //        VALUES (:work_id, :technology_id, :sort_order)
            //        work_id には、上で受け取った $workId を使います
            //        sort_order は、フォームに書いた順（1, 2, 3, ...）にします
            //
            // prepare() は foreach の外で1回だけ呼び、execute() を中で繰り返します。
            // （同じ SQL を何度も prepare し直さないため）
            // ============================================================

            // ↓ここから

            // ↑ここまで

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
    <style>
        /* 管理画面だけの最低限の見た目（自分しか見ない画面なので、ここで済ませる） */
        .admin-form label { display: block; font-weight: bold; margin-top: 1.2rem; }
        .admin-form input, .admin-form textarea { width: 100%; padding: 0.4rem; font: inherit; }
        .admin-form textarea { min-height: 6rem; }
        .admin-form .hint { font-size: 0.8rem; color: #596573; margin: 0.2rem 0; }
        .error { color: #b00020; margin: 0.2rem 0; }
    </style>
</head>
<body>
<main>
    <h1>作品を追加</h1>
    <p><a href="../index.php">← 作品の一覧に戻る</a></p>

    <?php show_error($errors, 'form'); ?>

    <form method="post" class="admin-form">
        <label for="slug">slug（URL に使う名前）</label>
        <p class="hint">半角の英小文字・数字・ハイフンだけ。例：library-system</p>
        <input type="text" id="slug" name="slug" value="<?= h($values['slug']) ?>" required>
        <?php show_error($errors, 'slug'); ?>

        <label for="title">作品名</label>
        <input type="text" id="title" name="title" value="<?= h($values['title']) ?>" required>
        <?php show_error($errors, 'title'); ?>

        <label for="summary">一言の説明（一覧に出る）</label>
        <input type="text" id="summary" name="summary" value="<?= h($values['summary']) ?>" required>
        <?php show_error($errors, 'summary'); ?>

        <label for="period_start">作り始めた日</label>
        <p class="hint">月までしか分からなければ、その月の1日でよい（詳細ページには「2026年4月」のように月までしか出ない）</p>
        <input type="date" id="period_start" name="period_start" value="<?= h($values['period_start']) ?>" required>
        <?php show_error($errors, 'period_start'); ?>

        <label for="period_end">作り終えた日（制作中なら空のまま）</label>
        <input type="date" id="period_end" name="period_end" value="<?= h($values['period_end']) ?>">
        <?php show_error($errors, 'period_end'); ?>

        <label for="duration_weeks">制作にかかった週数（分からなければ空のまま）</label>
        <input type="number" id="duration_weeks" name="duration_weeks" min="1" value="<?= h($values['duration_weeks']) ?>">
        <?php show_error($errors, 'duration_weeks'); ?>

        <label for="team_size">人数</label>
        <input type="number" id="team_size" name="team_size" min="1" value="<?= h($values['team_size']) ?>" required>
        <?php show_error($errors, 'team_size'); ?>

        <label for="technologies">使った技術</label>
        <p class="hint">1行に1つ。カンマで並べない</p>
        <textarea id="technologies" name="technologies" required><?= h($values['technologies']) ?></textarea>
        <?php show_error($errors, 'technologies'); ?>

        <label for="source_url">ソースコードの URL（無ければ空のまま）</label>
        <input type="url" id="source_url" name="source_url" value="<?= h($values['source_url']) ?>">
        <?php show_error($errors, 'source_url'); ?>

        <label for="purpose">何のために作ったか</label>
        <textarea id="purpose" name="purpose" required><?= h($values['purpose']) ?></textarea>
        <?php show_error($errors, 'purpose'); ?>

        <label for="my_role">自分の担当</label>
        <textarea id="my_role" name="my_role" required><?= h($values['my_role']) ?></textarea>
        <?php show_error($errors, 'my_role'); ?>

        <label for="challenge">一番詰まったところと、どう解いたか</label>
        <textarea id="challenge" name="challenge" required><?= h($values['challenge']) ?></textarea>
        <?php show_error($errors, 'challenge'); ?>

        <label for="highlight">工夫したところ</label>
        <textarea id="highlight" name="highlight" required><?= h($values['highlight']) ?></textarea>
        <?php show_error($errors, 'highlight'); ?>

        <label for="next_step">次に直すなら</label>
        <textarea id="next_step" name="next_step" required><?= h($values['next_step']) ?></textarea>
        <?php show_error($errors, 'next_step'); ?>

        <p><button type="submit">追加する</button></p>
    </form>
</main>
</body>
</html>
