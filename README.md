# zf1-future デモアプリ

このリポジトリは Docker 上の PHP 8.1 で動作するミニマルな Zend Framework 1 Future (ZF1-Future) アプリケーションです。Bootstrap 3 と jQuery を用いたランディングページから、フレームワークやツールに関するリソースへ簡単にアクセスできます。

## セットアップ手順

1. 依存関係をインストールします（公式 Composer イメージを使うためホストに PHP を入れる必要はありません）。
   ```bash
   docker run --rm -v $(pwd):/app composer install
   ```
2. 依存関係を更新したい場合も同じ Composer コマンドを使用します。環境を初期化したいときは `docker-compose down --volumes` の後に再インストールしてください。

## 実行方法

### 開発コンテナ（dev イメージ）

- ソースをホストからマウントしたまま Apache を起動します。
  ```bash
  docker-compose up --build
  ```
- ブラウザから <http://localhost:8000> にアクセスしてください。停止時は `docker-compose down` を実行します。

### 本番コンテナのローカル検証（prod イメージ）

- prod ステージをビルドして別ポートで動作確認します。
  ```bash
  docker-compose -f docker-compose.prod.yml up --build
  ```
- アクセス先は <http://localhost:8080> です。終了する場合は `docker-compose -f docker-compose.prod.yml down` を実行します。

### 本番用イメージの個別ビルド

- 将来的に ECR へ push する際は、まずローカルで prod ステージのイメージを作成します。
  ```bash
  docker build --target prod -t local/zf1-app:prod .
  ```
- 必要に応じて `local/zf1-app:prod` を ECR 向けタグに置き換えて push してください。

## ディレクトリ構成

- `application/` – MVC の中心ディレクトリ。`controllers/`、`views/`、`layouts/`、`configs/` が含まれます。
- `public/` – フロントコントローラ (`index.php`) と Apache が利用する `.htaccess` リライトルールを配置します。
- `vendor/` – Composer による依存ディレクトリです。コミットから除外してください（`.gitignore` を参照）。
- `Dockerfile` / `docker-compose.yml` – PHP 8.1 + Apache イメージと、コンテナの 80 番ポートをホストの 8000 番にマッピングするサービス定義です。

## 開発メモ

- コーディングスタイルやテスト、コミット規約などは `AGENTS.md` に従ってください。
- コントローラは `Zend_Controller_Action` を継承し、対応するビュースクリプトを `application/views/scripts/{controller}/{action}.phtml` に配置します。
- UI コンポーネントには Bootstrap 3 を利用し、レイアウトテンプレートに含まれる CDN 経由の jQuery を活用できます。
- テストやコード品質チェックなどの Composer スクリプトは `composer.json` の `scripts` セクションに定義し、`docker run --rm -v $(pwd):/app composer <script>` で実行します。

## PHP フォーマット方針

コードレビューを容易にし、プロジェクトの一貫性を保つため、PHP は PSR-12 に基づいて整形します。特に以下を意識してください。

- インデントは 4 スペースとし、タブとの混在を避けます。
- クラス・関数・メソッドの開き波括弧は行頭に置き、Zend 1 の流儀に合わせます。
- 新規 PHP ファイルでは可能な限り `declare(strict_types=1);` を先頭に追加します。
- `use` 宣言はベンダー単位のグループ内でアルファベット順に並べます（PHP/Laminas → サードパーティ → プロジェクト独自）。
- 各ファイル末尾には空行を 1 行だけ残し、編集時に末尾の空白を削除します。

Docker ワークフローで整形を自動化する手順:

1. PHP_CodeSniffer を開発依存として一度インストールします（既に入っている場合は不要）。
   ```bash
   docker run --rm -v $(pwd):/app composer require --dev squizlabs/php_codesniffer
   ```
2. コミット前にスタイル違反を自動修正します。
   ```bash
   docker run --rm -v $(pwd):/app composer exec -- phpcbf --standard=PSR12 application
   ```

レポートのみが必要な場合は `phpcbf` の代わりに `phpcs --standard=PSR12` を実行してください。

## コミット時の注意

各コミットには以下を必ず含めます。
- 命令形で簡潔にまとめたサブジェクト行。
- 背景・主な変更点・テスト結果を英語で記載した本文段落（簡潔でも構いません）。詳細なガイドラインは `AGENTS.md` を参照してください。
