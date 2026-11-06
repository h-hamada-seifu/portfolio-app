# 第8回の雛形 — 詳細ページと管理画面

この `starter-week08/` の中身を、**自分の `portfolio-app` に入れて**使います。

## 置き場所

**`starter-week08` というフォルダごとドラッグしないでください。中身だけ**を出します。

| 入れるもの | どこに置くか | すでにある場合 |
|---|---|---|
| `db.php` | `portfolio-app/db.php` | **上書きしてよい**（第7回のものに `format_period()` を足しただけ） |
| `public/detail.php` | `portfolio-app/public/detail.php` | 新しいファイル |
| `public/admin/new.php` | `portfolio-app/public/admin/new.php` | 新しいファイル（`admin` フォルダも作る） |
| `public/images/` | `portfolio-app/public/images/` | 新しいフォルダ |
| `public/index.php` | `portfolio-app/public/index.php` | **自分のものがある人は上書きしない。** `SELECT` のカラムと `ORDER BY sort_order, id` だけを写す |
| `public/css/style.css` | `portfolio-app/public/css/style.css` | **自分のものがある人は上書きしない** |
| `.env.example` / `.gitignore` | `portfolio-app/` の直下 | **上書きしない**（第7回に作ったものがある） |

> **`.env` は入っていません。**第7回に自分で作ったものを、そのまま使います。
> **`.env` を Git に入れないこと。**`.gitignore` に `.env` の1行があるか、もう一度確かめてください。

## 動かす

`portfolio-app` のフォルダで、次を実行します（第7回と同じ）。

```
php -S localhost:8000 -t public
```

| URL | 何が出るか |
|---|---|
| `http://localhost:8000/` | 作品の一覧 |
| `http://localhost:8000/detail.php?slug=library-system` | 作品の詳細 |
| `http://localhost:8000/admin/new.php` | 作品を追加するフォーム |

## 自分で書くところ

**`TODO` と `★ ここを書く` を探してください。**ここが今日の演習です。

| ファイル | 書くところ | 演習 |
|---|---|---|
| `public/detail.php` | 一番上の PHP（1件取り出す・404 を返す） | 演習②（15分） |
| `public/detail.php` | 静的な文章を `<?= h($work['カラム名']) ?>` に置き換える | 演習③（15分） |
| `public/index.php` | 作品名を詳細ページへのリンクにする | 演習⑤（5分） |
| `public/admin/new.php` | 技術を「探して、無ければ足す」ところ | 演習⑦（20分） |

> **演習②は AI を使わずに書いてください。**今日の中心はここの十数行です。
> 書き終わったあとに AI にレビューさせるのは、いつものとおり OK です。

## 最初に開いたときは、まだ静的です

`detail.php` を入れてすぐ開くと、**作例「図書の貸出管理システム」の中身が固定で**出ます。
データベースを見ていないので、`?slug=shift-app` にしても同じものが出ます。

**これを、データベースの値に差し替えていくのが演習②と③です。**
