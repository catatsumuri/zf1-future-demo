#!/usr/bin/env bash
# check_session_sharing.sh
# ALB配下でのセッション共有可否をワンショット検証
set -uo pipefail

# ===== 設定 =====
ALB_HOST="${ALB_HOST:-zf1fut-alb16-e9zzpn2t7g0v-172811615.ap-northeast-1.elb.amazonaws.com}"
EMAIL="${EMAIL:-test@example.com}"
PASSWORD="${PASSWORD:-password}"

LOGIN_PATH="${LOGIN_PATH:-/auth/login}"   # ログインフォームの POST 先
CHECK_PATH="${CHECK_PATH:-/}"             # 認証状態を確認するURL（/whoami等にしてもOK）
ITER="${ITER:-30}"                         # 試行回数
OK_PATTERN="${OK_PATTERN:-Log out|ようこそ}" # 本文で「ログイン中」と判定するパターン

# ===== 実装 =====
if ! command -v curl >/dev/null; then
  echo "[FATAL] curl not found" >&2; exit 127
fi

JAR="$(mktemp /tmp/sessionjar.XXXXXX)"
trap 'rm -f "$JAR"' EXIT

echo "== START: ALB=$ALB_HOST / EMAIL=$EMAIL / ITER=$ITER =="

# 1) セッション開始（Cookie取得）
curl -sS -c "$JAR" -D - "http://$ALB_HOST$LOGIN_PATH" >/dev/null

# 2) フォームPOSTでログイン（Cookie再利用＆再発行を保存）
curl -sS \
  -b "$JAR" -c "$JAR" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Connection: close' \
  -X POST \
  -d "email=${EMAIL}&password=${PASSWORD}" \
  -L -D - \
  "http://$ALB_HOST$LOGIN_PATH" \
  -o /dev/null

# 3) 本当にログインできたか軽く確認
first="$(curl -sS -b "$JAR" -H 'Accept-Encoding: identity' "http://$ALB_HOST$CHECK_PATH")"
if ! echo "$first" | grep -qE "$OK_PATTERN"; then
  echo "[FATAL] ログイン失敗：EMAIL/PASSWORD もしくは hidden/CSRF の不足を疑ってください" >&2
  echo "       （必要なら LOGIN_PATH/OK_PATTERN を調整）" >&2
  exit 2
fi

echo "== LOGIN OK。タスク跨ぎ検証を ${ITER} 回実行 =="

# 4) ループで判定
ok=0; ng=0
declare -A per_task_ok
declare -A per_task_ng

for i in $(seq 1 "$ITER"); do
  out="$(curl -sS -b "$JAR" \
             -H 'Connection: close' \
             -H 'Accept-Encoding: identity' \
             -i "http://$ALB_HOST$CHECK_PATH")"

  task="$(echo "$out" | awk '/^X-Task:/{print $2}' | sed 's/%//')"
  [[ -z "$task" ]] && task="(no-X-Task)"

  if echo "$out" | grep -qE "$OK_PATTERN"; then
    state="AUTH=OK"; ok=$((ok+1)); per_task_ok["$task"]=$(( ${per_task_ok["$task"]:-0} + 1 ))
  else
    state="AUTH=NG"; ng=$((ng+1)); per_task_ng["$task"]=$(( ${per_task_ng["$task"]:-0} + 1 ))
  fi

  printf "%3d  %-60s  %s\n" "$i" "$task" "$state"
done

echo "== SUMMARY =="
echo "TOTAL: OK=$ok  NG=$ng"
echo "-- per task (OK) --"
for k in "${!per_task_ok[@]}"; do printf "  %-60s %d\n" "$k" "${per_task_ok[$k]}"; done | sort
echo "-- per task (NG) --"
for k in "${!per_task_ng[@]}"; do printf "  %-60s %d\n" "$k" "${per_task_ng[$k]}"; done | sort

# 5) 退出コード：混ざったら失敗（共有できていない）
if (( ng > 0 )); then
  echo "== RESULT: セッションは共有『されていない』（NG混入） =="
  exit 1
else
  echo "== RESULT: セッションは共有『されている』 =="
  exit 0
fi
