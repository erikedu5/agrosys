#!/usr/bin/env bash
set -euo pipefail

mkdir -p build/ci-logs
adb -s emulator-5554 logcat -c
adb -s emulator-5554 logcat -v threadtime > build/ci-logs/android-logcat.txt 2>&1 &
logcat_pid=$!
trap 'kill "$logcat_pid" 2>/dev/null || true' EXIT

# pipefail keeps a failing Flutter test from being hidden by tee.
for test_name in offline_reopen sync_recovery; do
  flutter test "integration_test/${test_name}_test.dart" \
    -d emulator-5554 --no-pub --reporter expanded --verbose \
    2>&1 | tee "build/ci-logs/${test_name}.txt"
done
