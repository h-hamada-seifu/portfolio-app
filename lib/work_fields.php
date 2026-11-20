<?php
/**
 * lib/work_fields.php
 * 作品のフォームの入力欄（new.php と edit.php で共通）。
 *
 * new.php・edit.php の <form> の中で require して使う。次の3つの変数を使う。
 *   $values … 入力欄に入れておく値（最初は空。エラーで戻ったときは、送られてきた値）
 *   $errors … 入力欄の名前 => エラーの文
 *   $isNew  … true なら追加（slug を入力させる）、false なら編集（slug は変えさせない）
 *
 * 入力欄の name は、$values のキー・INSERT / UPDATE の列名と同じ名前にしてある。
 * 値を出すところは、すべて h() を通す。
 */

declare(strict_types=1);
?>
<?php if ($isNew): ?>
    <label for="slug">slug（URL に使う名前）</label>
    <p class="hint">半角の英小文字・数字・ハイフンだけ。例：library-system</p>
    <input type="text" id="slug" name="slug" value="<?= h($values['slug']) ?>" required>
    <?php show_error($errors, 'slug'); ?>
<?php else: ?>
    <p><strong>slug：</strong><?= h($values['slug']) ?>（URL なので、編集では変えられません）</p>
<?php endif; ?>

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
