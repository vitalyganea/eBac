#!/usr/bin/env sh
#
# Garantează un Node suficient de nou, apoi execută comanda primită.
#
# De ce există: `php artisan dev` moștenește `node` din PATH-ul shell-ului. Un
# terminal deschis înainte de instalarea nvm are încă Node-ul de sistem (v18),
# iar Vite 8 cade cu un `SyntaxError` din adâncul node_modules — flagul de regex
# `/v` folosit de `string-width` există doar din Node 20.
#
# În loc să-i cerem utilizatorului să-și repare shell-ul înainte de fiecare
# comandă, găsim singuri Node-ul potrivit din nvm și îl punem în PATH. Copiii
# (npm, vite) îl moștenesc.
#
# Scris în sh POSIX, fără sintaxă modernă: rulează oriunde.

set -e

MIN_MAJOR=20
NVM_DIR="${NVM_DIR:-$HOME/.nvm}"

node_major() {
    if command -v node >/dev/null 2>&1; then
        node -p 'process.versions.node.split(".")[0]' 2>/dev/null || echo 0
    else
        echo 0
    fi
}

# Cea mai potrivită versiune instalată prin nvm: preferăm ce cere .nvmrc,
# altfel cea mai nouă.
pick_nvm_node() {
    versions_dir="$NVM_DIR/versions/node"
    [ -d "$versions_dir" ] || return 1

    wanted=''
    if [ -f .nvmrc ]; then
        wanted=$(tr -d ' \t\r\nv' < .nvmrc)
    fi

    candidate=''
    if [ -n "$wanted" ]; then
        candidate=$(ls -1 "$versions_dir" 2>/dev/null | grep "^v${wanted}" | sort -V | tail -1)
    fi
    if [ -z "$candidate" ]; then
        candidate=$(ls -1 "$versions_dir" 2>/dev/null | sort -V | tail -1)
    fi

    [ -n "$candidate" ] && [ -x "$versions_dir/$candidate/bin/node" ] || return 1
    printf '%s\n' "$versions_dir/$candidate/bin"
}

if [ "$(node_major)" -lt "$MIN_MAJOR" ]; then
    if nvm_bin=$(pick_nvm_node); then
        PATH="$nvm_bin:$PATH"
        export PATH
        printf '  Node %s (din nvm) \033[32m✓\033[0m\n' "$(node -p 'process.versions.node')"
    fi
else
    printf '  Node %s \033[32m✓\033[0m\n' "$(node -p 'process.versions.node')"
fi

if [ "$(node_major)" -lt "$MIN_MAJOR" ]; then
    printf '\n  \033[41m\033[97m  Node prea vechi  \033[0m\n\n'
    printf '  eBac necesită Node %s+, dar în PATH este Node %s.\n' "$MIN_MAJOR" "$(node -p 'process.versions.node' 2>/dev/null || echo 'niciunul')"
    printf '  Nu am găsit nicio versiune potrivită în %s\n\n' "$NVM_DIR/versions/node"
    printf '  Instalează-o:\n\n'
    printf '    nvm install 22\n\n'
    printf '  Dacă `nvm` nu e găsit, terminalul e mai vechi decât instalarea nvm:\n\n'
    printf '    source ~/.bashrc\n\n'
    exit 1
fi

exec "$@"
