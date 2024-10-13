# djlint.sh
#/bin/sh
./.venv/bin/djlint - --reformat --configuration ./.djlintrc
#
# Install
# > python3 -m venv .venv
# > source .venv/bin/activate
# > pip install djlint
# > deactivate
#
# .vscode/settings.json
#
#   "customLocalFormatters.formatters": [
#    {
#      "command": "./djlint.sh",
#      "languages": [
#                "twig"
#            ]
#        }
#    ],
#
