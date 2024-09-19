#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0
apt-get update -qq \
&& apt-get install -qq git \
  # Setup SSH deploy keys
&& 'which ssh-agent || ( apt-get install -qq openssh-client )' \
&& eval $(ssh-agent -s) \
&& ssh-add <(echo "$SSH_PRIVATE_KEY") \
&& mkdir -p ~/.ssh \
&& '[[ -f /.dockerenv ]] && echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config'
