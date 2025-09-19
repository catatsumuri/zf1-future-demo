# Repository Guidelines

## プロジェクト構成とモジュール配置
- `application/` は MVC の中心です。`controllers/` にアクションクラス、`views/` に phtml テンプレート、`layouts/` に共通レイアウト、`configs/application.ini` に環境設定を置きます。
- `public/` にはフロントコントローラ (`index.php`) と Rewrite ルールを配置しています。
- `vendor/` は Composer 管理ディレクトリです。クローン後は `docker run --rm -v $(pwd):/app composer install` で再生成してください。
- ルート直下の `Dockerfile` と `docker-compose.yml` が PHP 8.1 + Apache スタックを定義し、ローカル実行に利用します。

## ビルド・テスト・開発コマンド
- `docker run --rm -v $(pwd):/app composer install` : 依存ライブラリをホスト PHP なしでインストールします。
- `docker-compose up --build` : Web コンテナを `http://localhost:8000` で起動し、依存が変わった際は再ビルドします。
- `docker-compose exec -T web curl -sSf http://localhost` : コンテナ内部からの簡易ヘルスチェックです。

## コーディングスタイルと命名規則
- PHP 8.1 を対象とし、PSR-12（4 スペースインデント、波括弧は改行）に従います。可能な箇所では strict types を検討してください。
- コントローラは `*Controller` で終える命名とし（例: `IndexController.php`）、`*Action()` メソッドを `void` で実装します。
- ビュースクリプトは `application/views/scripts/{controller}/{action}.phtml` というパスに揃えます。
- 設定キーは小文字 + ドットで統一します（例: `resources.frontController.params`）。

## テスト指針
- テストは PHPUnit を推奨し、`tests/` 以下に PSR-4 に準拠した構成（例: `tests/Controller/IndexControllerTest.php`）で配置します。
- テストメソッドは挙動を説明する `testCan...` や `testShould...` 形式で命名します。
- テスト実行用スクリプトを `composer.json` に追加したら、`docker run --rm -v $(pwd):/app composer test` で走らせます。

## コミットとプルリクエストの指針
- コミットメッセージは命令形で簡潔に（例: `Add health check command`）。関連 issue があれば 1 行目で参照し、本文で主な変更点（設定、エンドポイント追加など）を記述します。
- 各コミットには短いサマリー行に加え、英語での詳細説明（本文）を必ず添えてください。本文には背景、影響範囲、テスト結果を記載します。
- プルリクエストでは目的、確認手順（実行したコマンド、UI 変更ならスクリーンショット）を記載し、マイグレーションや手動作業があれば明記してください。
- レビュー依頼前にブランチを `master` へリベースし、`docker-compose up --build` が成功することを確認します。

## Docker と環境設定のヒント
- Apache ログに `ServerName` 警告が出る場合は、コンテナ内の Apache 設定へ `ServerName localhost` を追加してください。
- 環境を初期化したいときは `docker-compose down --volumes` 後に依存を再インストールします。
