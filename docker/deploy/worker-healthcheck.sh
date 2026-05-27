#!/bin/sh
# Vérifie qu'un processus messenger:consume tourne (sans procps/ps).
for f in /proc/[0-9]*/cmdline; do
    tr '\0' ' ' < "$f" | grep -q 'messenger:consume' && exit 0
done
exit 1
