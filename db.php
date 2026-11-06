<?php
// 第7回で使った db.php と同じものに、第8回で使う format_period() を末尾に足してあります。
// すでに portfolio-app/db.php がある人は、これで上書きしてかまいません（接続の部分は同じです）。
/**
 * db.php
 * データベース（Supabase の PostgreSQL）に接続するための関数
 *
 * このファイルは public/ の外に置く。ブラウザから直接開かれないようにするため。
 *
 * 使い方（public/ の中の PHP から）:
 *   require_once __DIR__ . '/../db.php';
 *   $pdo = get_pdo();
 *
 * 接続情報の読み方:
 *   1. 公開先の「環境変数」に値があれば、それを使う（12月の Cloud Run など）
 *   2. 無ければ、このファイルと同じ場所にある .env から読む（手元の PC）
 *   コードはどちらでも同じ。値がどこから来るかを、コードは知らなくてよい。
 */

declare(strict_types=1);

/**
 * .env を読み、「KEY=VALUE」の組を配列にして返す（前期の db.php と同じ考え方）
 *   # で始まる行と空行は無視する。値を "..." や '...' で囲っている場合は外す。
 */
function load_env_file(string $path): array
{
    $values = [];
    if (!is_readable($path)) {
        return $values;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2
            && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        $values[$key] = $value;
    }
    return $values;
}

/**
 * 設定値を1つ返す。環境変数を優先し、無ければ .env の値を使う。
 * どちらにも無ければ、何が足りないかを書いた例外を投げる（値そのものはメッセージに含めない）。
 */
function env(string $key): string
{
    static $fileValues = null;
    if ($fileValues === null) {
        $fileValues = load_env_file(__DIR__ . '/.env');
    }

    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $fileValues[$key] ?? '';
    }
    if ($value === '') {
        throw new RuntimeException("設定 {$key} がありません。.env（または公開先の環境変数）を確認してください。");
    }
    return $value;
}

function get_pdo(): PDO
{
    // libpq は SSL 接続のとき、クライアント証明書を決まった場所に探しに行く。
    // macOS 版 XAMPP ではその場所が読めずに接続が止まることがあるため、
    // 存在しないパスを明示して「証明書なし」で続けさせる（前期の db.php と同じ回避策）。
    $nullCert = (PHP_OS_FAMILY === 'Windows') ? 'C:/nosuchfile' : '/tmp/nosuchfile';

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require;sslcert=%s;sslkey=%s',
        env('DB_HOST'),
        env('DB_PORT'),
        env('DB_NAME'),
        $nullCert,
        $nullCert
    );

    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

/**
 * 画面に出す値を、HTML として解釈されない文字に置き換える（htmlspecialchars の短縮形）
 * データベースから来た値は、必ずこれを通して出す。
 */
function h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// ---- ここから第8回で足したもの（テキスト 15.9.1） ----

// 制作期間を「2026年4月〜2026年7月（14週間）」の形にして返す（表示用）
//   終了日が NULL なら制作中として「2026年8月〜（制作中）」
//   週数が NULL なら（14週間）の部分を付けない
function format_period(string $start, ?string $end, ?int $weeks): string
{
    $text = date('Y年n月', strtotime($start)) . '〜';
    if ($end === null) {
        return $text . '（制作中）';
    }
    $text .= date('Y年n月', strtotime($end));
    if ($weeks !== null) {
        $text .= '（' . $weeks . '週間）';
    }
    return $text;
}
