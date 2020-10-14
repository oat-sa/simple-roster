#!/bin/bash

MODULO=$1

echo "Killing detached screens"
screen -ls | grep Detached | cut -d. -f1 | awk '{print $1}' | xargs kill
for i in {0..MODULO-1}; do
    echo "Launching session: cache.$i.warmup"
    screen -dmS "cache.$i.warmup" bash -c "sudo -u www-data bin/console roster:doctrine-result-cache:warmup -m MODULO -r $i -b 10000"
    sleep 2
done
