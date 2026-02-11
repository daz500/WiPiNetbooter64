#!/bin/bash

SERVICE="host_debug_server"
BASE_DIR="/sbin/netboot"
VENV_PY="/opt/netboot-venv/bin/python"
PORT="8080"

# Environment variables to silence Flask / Werkzeug logs (production mode)
export FLASK_ENV=production
export FLASK_DEBUG=0
export WERKZEUG_RUN_MAIN=false
export WERKZEUG_LOG_LEVEL=error

if [[ "$1" == "start" ]]; then

    # Check if the service is already running
    if pgrep -f "$SERVICE" > /dev/null; then
        echo -e "\n\tDragonMinded Netboot Web server is already \e[32mRUNNING\e[0m"
        echo -e "\thttp://netbooter.local:$PORT\n"
        exit 0
    fi

    echo -e "\n\tStarting Netboot Web Server..."

    # Ensure correct working directory (relative paths depend on this)
    cd "$BASE_DIR" || {
        echo -e "\t\e[31mERROR\e[0m: cannot cd to $BASE_DIR"
        exit 1
    }

    # Start the server using the virtualenv Python
    "$VENV_PY" "$SERVICE" -p "$PORT" --config "$BASE_DIR/config.yaml" >/dev/null 2>&1 &
    PID=$!

    # Give the process time to fail fast if something is wrong
    sleep 1

    # Verify the process is still running
    if ! ps -p "$PID" > /dev/null 2>&1; then
        echo -e "\n\t\e[31mERROR\e[0m: process failed to start"
        exit 1
    fi

    # Verify the TCP port is actually listening
    if ss -ltn | grep -q ":$PORT "; then
        echo -e "\n\tDragonMinded Netboot Web server is now \e[32mRUNNING\e[0m"
        echo -e "\thttp://netbooter.local:$PORT\n"
        exit 0
    else
        echo -e "\n\t\e[31mERROR\e[0m: process is running but port $PORT is not listening"
        exit 1
    fi

elif [[ "$1" == "stop" ]]; then

    # Stop the service if running
    if pgrep -f "$SERVICE" > /dev/null; then
        echo -e "\n\tStopping DragonMinded Netboot Web server..."

        pkill -f "$SERVICE"

        # Give the process time to exit cleanly
        sleep 1

        if pgrep -f "$SERVICE" > /dev/null; then
            echo -e "\t\e[31mERROR\e[0m: failed to stop the service\n"
            exit 1
        else
            echo -e "\tDragonMinded Netboot Web server is \e[31mSTOPPED\e[0m\n"
            exit 0
        fi
    else
        echo -e "\n\tDragonMinded Netboot Web server is already \e[31mSTOPPED\e[0m\n"
        exit 0
    fi

else
    echo -e "\nUsage: $0 <start|stop>\n"
    exit 1
fi
#!/bin/bash
