#!/bin/bash

# NVM needs the ability to modify your current shell session's env vars,
# which is why it's a sourced function

# found in the current user's .bashrc - update [user] below with your user!
export NVM_DIR="/home/deployer/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"  # This loads nvm


#export NVM_DIR="$([ -z "${XDG_CONFIG_HOME-}" ] && printf %s "${HOME}/.nvm" || printf %s "${XDG_CONFIG_HOME}/nvm")"
#[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" # This loads nvm


# uncomment the line below if you need a specific version of node
# other than the one specified as `default` alias in NVM (optional)
# nvm use 4 1> /dev/null
