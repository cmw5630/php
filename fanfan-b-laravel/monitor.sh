#!/bin/bash

if [ -z "$(which inotifywait)" ]; then
    echo "inotifywait not installed."
    echo "In most distros, it is available in the inotify-tools package."
    exit 1
fi

counter=0;

function execute() {
    counter=$((counter+1))
    echo "Detected change n. $counter" 
    eval "$@"
}


# inotifywait --recursive --monitor -r "${PWD}" --format "%e %w%f" \
# --event create ./ \
inotifywait --recursive --monitor -r --format "%e %w%f" \
--event create ./ \
| while read changed; do
    echo $changed
    #execute "$@"
    execute "chown nginx:nginx ${PWD}/*.log"
    execute "chown nginx:nginx ${PWD}/* -R"
done
