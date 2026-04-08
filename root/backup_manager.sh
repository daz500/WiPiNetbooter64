#!/bin/bash

# ============================================
# WiPi64 Backup & Restore Script
# Version: v0.1
# Date: 2026-04-06
# ============================================

set -e

# ================================
# CONFIGURATION
# ================================

SOURCE_PATHS=(
    "/etc/openjvs/"
    "/etc/openffb/"
    "/sbin/piforce/activedimm.txt"
    "/sbin/piforce/hatenabled.txt"
    "/sbin/piforce/hatserial.txt"
    "/sbin/piforce/openmode.txt"
    "/sbin/piforce/ffbmode.txt"
    "/sbin/piforce/nfcmode.txt"
    "/sbin/piforce/emumode.txt"
    "/sbin/piforce/bootfile.txt"
    "/sbin/piforce/filtermode.txt"
    "/sbin/piforce/menumode.txt"
    "/sbin/piforce/navmode.txt"
    "/sbin/piforce/powerfile.txt"
    "/sbin/piforce/relaymode.txt"
    "/sbin/piforce/rotarymode.txt"
    "/sbin/piforce/servermode.txt"
    "/sbin/piforce/soundmode.txt"
    "/sbin/piforce/zeromode.txt"
    "/var/www/html/csv/dimms.csv"
    "/boot/firmware/config.txt"
    "/boot/firmware/cmdline.txt"
    "/boot/firmware/config/cards/"
    "/var/www/html/cards/"
)

HAT_ENABLED_FILE="/sbin/piforce/hatenabled.txt"
HAT_SERIAL_FILE="/sbin/piforce/hatserial.txt"
OPEN_MODE_FILE="/sbin/piforce/openmode.txt"
FFB_MODE_FILE="/sbin/piforce/ffbmode.txt"
EMU_MODE_FILE="/sbin/piforce/emumode.txt"
NFC_MODE_FILE="/sbin/piforce/nfcmode.txt"

BOOT_FILE="/sbin/piforce/bootfile.txt"
FILTER_MODE_FILE="/sbin/piforce/filtermode.txt"
MENU_MODE_FILE="/sbin/piforce/menumode.txt"
NAV_MODE_FILE="/sbin/piforce/navmode.txt"
POWER_FILE="/sbin/piforce/powerfile.txt"
RELAY_MODE_FILE="/sbin/piforce/relaymode.txt"
ROTARY_MODE_FILE="/sbin/piforce/rotarymode.txt"
SERVER_MODE_FILE="/sbin/piforce/servermode.txt"
SOUND_MODE_FILE="/sbin/piforce/soundmode.txt"
ZERO_MODE_FILE="/sbin/piforce/zeromode.txt"

HAT_UPDATE_SCRIPT="/root/openjvs-hat/hatupdate.sh"
SWITCH_SCRIPT="/sbin/piforce/switchmode.py"
REBOOT_SCRIPT="/sbin/piforce/reboot.py"

# ================================
# FUNCTIONS
# ================================

show_help() {
    echo "Usage:"
    echo "  $0 create"
    echo "  $0 restore <backup_file.tar.gz>"
    echo "  $0 -h | --help"
    echo
    echo "Commands:"
    echo "  create                   Create a backup file in /tmp"
    echo "  restore <file.tar.gz>    Restore a backup file"
    echo
    echo "Description:"
    echo "  This script creates or restores backups for predefined"
    echo "  directories and files."
    echo
    echo "Backup file name format:"
    echo "  /tmp/backup_<hostname>_YYYYMMDD_HHMMSS.tar.gz"
    echo
    echo "Examples:"
    echo "  $0 create"
    echo "  $0 restore /tmp/backup_netbooter_20260402_183000.tar.gz"
}

check_dependencies() {
    if ! command -v tar >/dev/null 2>&1; then
        echo "tar is not installed. Installing..."
        sudo apt update
        sudo apt install -y tar
    fi
}

create_backup() {
    local hostname
    local timestamp
    local backup_file
    local valid_paths=()

    hostname=$(hostname)
    timestamp=$(date +"%Y%m%d_%H%M%S")
    backup_file="/tmp/backup_${hostname}_${timestamp}.tar.gz"

    for path in "${SOURCE_PATHS[@]}"; do
        if [ -e "$path" ]; then
            valid_paths+=("$path")
        else
            echo "Warning: Path not found, skipping: $path"
        fi
    done

    if [ ${#valid_paths[@]} -eq 0 ]; then
        echo "No valid paths to back up. Exiting."
        exit 1
    fi

    echo "Creating backup..."
    sudo tar -czpf "$backup_file" \
        --ignore-failed-read \
        "${valid_paths[@]}"

    if [ -f "$backup_file" ]; then
        echo "Backup successfully created:"
        echo "$backup_file"
    else
        echo "Backup failed."
        exit 1
    fi
}

schedule_reboot() {
    echo
    echo "=================================================="
    echo "!!! SYSTEM WILL RESTART IN 15 SECONDS !!!"
    echo "=================================================="
    echo "The reboot was scheduled in background."
    echo

    nohup bash -c "sleep 15; sudo python \"$REBOOT_SCRIPT\"" >/dev/null 2>&1 &
}

apply_switchmode_from_file() {
    local mode_file="$1"
    local label="$2"
    local mode_value

    echo "$label"

    if [ -f "$mode_file" ]; then
        mode_value=$(tr -d '\r\n' < "$mode_file")

        if [ -n "$mode_value" ]; then
            echo "Applying: $mode_value"

            if [ ! -f "$SWITCH_SCRIPT" ]; then
                echo "Error: Script not found: $SWITCH_SCRIPT"
                exit 1
            fi

            sudo python "$SWITCH_SCRIPT" "$mode_value"
            echo "Applied successfully."
        else
            echo "File is empty, skipping."
        fi
    else
        echo "File not found, skipping."
    fi

    echo
}

run_post_restore_actions() {
    local hat_status
    local nfc_mode_status

    echo
    echo "Running post-restore actions..."
    echo

    echo "[1/16] OpenJVS mode"
    if [ -f "$OPEN_MODE_FILE" ]; then
        local open_mode_status
        open_mode_status=$(tr -d '\r\n' < "$OPEN_MODE_FILE")

        if [ -n "$open_mode_status" ]; then
            echo "Applying OpenJVS mode: $open_mode_status"

            if [ ! -f "$SWITCH_SCRIPT" ]; then
                echo "Error: Script not found: $SWITCH_SCRIPT"
                exit 1
            fi

            python "$SWITCH_SCRIPT" "$open_mode_status"
            echo "OpenJVS mode applied successfully."
        else
            echo "Open mode file is empty, skipping."
        fi
    else
        echo "Open mode file not found, skipping."
    fi
    echo

    echo "[2/16] FFB mode"
    if [ -f "$FFB_MODE_FILE" ]; then
        local ffb_mode_status
        ffb_mode_status=$(tr -d '\r\n' < "$FFB_MODE_FILE")

        if [ -n "$ffb_mode_status" ]; then
            echo "Applying FFB mode: $ffb_mode_status"

            if [ ! -f "$SWITCH_SCRIPT" ]; then
                echo "Error: Script not found: $SWITCH_SCRIPT"
                exit 1
            fi

            sudo python "$SWITCH_SCRIPT" "$ffb_mode_status"
            echo "FFB mode applied successfully."
        else
            echo "FFB mode file is empty, skipping."
        fi
    else
        echo "FFB mode file not found, skipping."
    fi
    echo

    echo "[3/16] HAT serial mode"
    if [ -f "$HAT_SERIAL_FILE" ]; then
        local serial_status
        serial_status=$(tr -d '\r\n' < "$HAT_SERIAL_FILE")

        if [ -n "$serial_status" ]; then
            echo "Applying HAT serial mode: $serial_status"

            if [ ! -f "$SWITCH_SCRIPT" ]; then
                echo "Error: Script not found: $SWITCH_SCRIPT"
                exit 1
            fi

            sudo python "$SWITCH_SCRIPT" "$serial_status"
            echo "HAT serial mode applied successfully."
        else
            echo "HAT serial file is empty, skipping."
        fi
    else
        echo "HAT serial file not found, skipping."
    fi
    echo

    echo "[4/16] Card Emulator mode"
    if [ -f "$EMU_MODE_FILE" ]; then
        local emu_mode_status
        emu_mode_status=$(tr -d '\r\n' < "$EMU_MODE_FILE")

        if [ -n "$emu_mode_status" ]; then
            echo "Applying Card Emulator mode: $emu_mode_status"

            if [ ! -f "$SWITCH_SCRIPT" ]; then
                echo "Error: Script not found: $SWITCH_SCRIPT"
                exit 1
            fi

            sudo python "$SWITCH_SCRIPT" "$emu_mode_status"
            echo "Card Emulator mode applied successfully."
        else
            echo "Card Emulator mode file is empty, skipping."
        fi
    else
        echo "Card Emulator mode file not found, skipping."
    fi
    echo

    echo "[5/16] Boot file mode"
    apply_switchmode_from_file "$BOOT_FILE" ""

    echo "[6/16] Filter mode"
    apply_switchmode_from_file "$FILTER_MODE_FILE" ""

    echo "[7/16] Menu mode"
    apply_switchmode_from_file "$MENU_MODE_FILE" ""

    echo "[8/16] Navigation mode"
    apply_switchmode_from_file "$NAV_MODE_FILE" ""

    echo "[9/16] Power file mode"
    apply_switchmode_from_file "$POWER_FILE" ""

    echo "[10/16] Relay mode"
    apply_switchmode_from_file "$RELAY_MODE_FILE" ""

    echo "[11/16] Rotary mode"
    apply_switchmode_from_file "$ROTARY_MODE_FILE" ""

    echo "[12/16] Server mode"
    apply_switchmode_from_file "$SERVER_MODE_FILE" ""

    echo "[13/16] Sound mode"
    apply_switchmode_from_file "$SOUND_MODE_FILE" ""

    echo "[14/16] Zero mode"
    apply_switchmode_from_file "$ZERO_MODE_FILE" ""

    echo "[15/16] NFC mode"
    if [ -f "$NFC_MODE_FILE" ]; then
        nfc_mode_status=$(tr -d '\r\n' < "$NFC_MODE_FILE")
        if [ -n "$nfc_mode_status" ]; then
            nohup sudo python "$SWITCH_SCRIPT" "$nfc_mode_status" > /dev/null 2>&1 &
            echo "NFC mode started in background."
        else
            echo "NFC mode file is empty, skipping."
        fi
    else
        echo "NFC mode file not found, skipping."
    fi
    echo

    echo "[16/16] OpenJVS HAT activation"
    if [ -f "$HAT_ENABLED_FILE" ]; then
        hat_status=$(tr -d '\r\n' < "$HAT_ENABLED_FILE")
        if [ "$hat_status" = "hatenabled" ]; then
            echo "OpenJVS HAT will be enabled."

            if [ ! -x "$HAT_UPDATE_SCRIPT" ]; then
                echo "Error: HAT update script not found or not executable: $HAT_UPDATE_SCRIPT"
                exit 1
            fi

            sudo "$HAT_UPDATE_SCRIPT"

            if [ ! -f "$REBOOT_SCRIPT" ]; then
                echo "Error: Reboot script not found: $REBOOT_SCRIPT"
                exit 1
            fi

            echo "OpenJVS HAT enabled successfully."
            schedule_reboot
        else
            echo "OpenJVS HAT activation not required."
        fi
    else
        echo "HAT enabled file not found, skipping."
    fi
}

restore_backup() {
    local backup_file="$1"

    if [ ! -f "$backup_file" ]; then
        echo "Error: File not found: $backup_file"
        exit 1
    fi

    if [[ "$backup_file" != *.tar.gz ]]; then
        echo "Error: File must be a .tar.gz file"
        exit 1
    fi

    echo "Restoring from: $backup_file"
    echo "Preserving ownership, group, permissions, and timestamps..."

    sudo tar -xzpf "$backup_file" -C /

    echo "Restore completed successfully."
}

# ================================
# PROGRAM LOGIC
# ================================

if [ $# -eq 0 ]; then
    show_help
    exit 1
fi

case "$1" in
    -h|--help)
        show_help
        ;;
    create)
        check_dependencies
        create_backup
        ;;
    restore)
        check_dependencies
        restore_backup "$2"
        run_post_restore_actions
        ;;
    *)
        show_help
        exit 1
        ;;
esac
