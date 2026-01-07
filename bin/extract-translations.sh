#!/bin/bash
# Use --clean for messages domain to remove unused translations
bin/console trans:extract nl_NL --force --clean --domain=messages
bin/console trans:extract en_GB --force --clean --domain=messages

# Extract validators without --clean to preserve vendor translations
bin/console trans:extract nl_NL --force --domain=validators
bin/console trans:extract en_GB --force --domain=validators
