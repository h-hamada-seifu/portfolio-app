<?php
/**
 * lib/work_form.php
 * 作品の「追加（new.php）」と「編集（edit.php）」で共通に使う関数。
 *
 * public/ の外に置いてあるので、ブラウザからは開けない（db.php と同じ考え方）。
 *
 *   empty_work_values()   … フォームの入力欄の初期値
 *   read_work_input()     … 送られてきた値を受け取り、チェックする（テキスト 17.4）
 *   work_params()         … INSERT / UPDATE に渡す値を作る（空欄は NULL にする）
 *   save_technologies()   … 使った技術を technologies・work_technologies に入れる（テキスト 17.5.1）
 *   show_error()          … 入力欄の下にエラーを出す
 */

declare(strict_types=1);

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
function empty_work_values(): array
{
    return [
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
}

/**
 * 送られてきた値（$_POST）を受け取って、チェックする。
 *   $values      … 受け取った値（前後の空白を落としたもの）。エラーのときはフォームに戻して表示する
 *   $technologies … 使った技術の名前（1行に1つ。空行と重複を除いたもの）
 *   $errors      … 入力欄の名前 => エラーの文。空ならチェックを通った
 *
 * $checkSlug が false のとき（編集）は、slug を受け取らない・チェックしない。
 * slug は URL なので、編集では変えさせない（テキスト 18.5）。
 *
 * @return array{0: array<string, string>, 1: array<string, string>, 2: array<string, string>}
 */
function read_work_input(array $post, array $values, bool $checkSlug): array
{
    // ---- 受け取る：前後の空白を落とす（配列で送られてきたら空にする） ----
    foreach (array_keys($values) as $key) {
        if ($key === 'slug' && !$checkSlug) {
            continue;   // 編集では、送られてきた slug を使わない
        }
        $input = $post[$key] ?? '';
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

    $errors = [];

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
    if (!$checkSlug) {
        unset($labels['slug']);
    }
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
    if ($checkSlug && !isset($errors['slug']) && !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $values['slug'])) {
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
    // javascript: などで始まる URL を入れさせない（テキスト 18.3.3。h() では防げない）
    if ($values['source_url'] !== '' && !preg_match('#\Ahttps?://#', $values['source_url'])) {
        $errors['source_url'] = 'ソースコードの URL は https:// か http:// で始めてください。';
    }
    $maxLengths = ['slug' => 100, 'title' => 200];   // CREATE TABLE の varchar(100)・varchar(200) と同じ
    foreach ($maxLengths as $key => $max) {
        if ($key === 'slug' && !$checkSlug) {
            continue;
        }
        if (!isset($errors[$key]) && mb_strlen($values[$key]) > $max) {
            $errors[$key] = "{$max}文字以内で入力してください。";
        }
    }
    foreach ($technologies as $name) {
        if (mb_strlen($name) > 50) {   // technologies.name は varchar(50)
            $errors['technologies'] = '技術の名前は、1つ50文字以内で入力してください。';
        }
    }

    return [$values, $technologies, $errors];
}

/**
 * works の INSERT / UPDATE に渡す値（slug 以外）を作る。
 * 空の欄は NULL にする（「無い」は NULL で表す。テキスト 17.5）。
 */
function work_params(array $values): array
{
    return [
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
    ];
}

/**
 * 使った技術を1つずつ入れる（テキスト 17.5.1）。
 *   ① technologies に同じ名前があればその番号を使い、無ければ足して番号をもらう
 *   ② work_technologies に「この作品の番号 ＋ その技術の番号」を1行入れる
 *
 * 呼ぶ側（new.php・edit.php）がトランザクションを始めてから呼ぶこと。
 *
 * @param array<string, string> $technologies 例：['php' => 'PHP', 'laravel' => 'Laravel']
 */
function save_technologies(PDO $pdo, int $workId, array $technologies): void
{
    // prepare() は foreach の外で1回だけ。SQL の形は技術ごとに変わらないので、値だけを execute() で渡す
    $findTechnology = $pdo->prepare('SELECT id FROM technologies WHERE lower(name) = lower(:name)');
    $addTechnology  = $pdo->prepare('INSERT INTO technologies (name) VALUES (:name) RETURNING id');
    $linkTechnology = $pdo->prepare(
        'INSERT INTO work_technologies (work_id, technology_id, sort_order)
         VALUES (:work_id, :technology_id, :sort_order)'
    );

    $sortOrder = 1;
    foreach ($technologies as $name) {
        $findTechnology->execute([':name' => $name]);
        $technologyId = $findTechnology->fetchColumn();   // 無ければ false
        if ($technologyId === false) {
            $addTechnology->execute([':name' => $name]);
            $technologyId = $addTechnology->fetchColumn();
        }
        $linkTechnology->execute([
            ':work_id'       => $workId,
            ':technology_id' => $technologyId,
            ':sort_order'    => $sortOrder,
        ]);
        $sortOrder++;
    }
}
