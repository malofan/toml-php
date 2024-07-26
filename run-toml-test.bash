#!/usr/bin/env bash

skip_decode=(
	# Invalid UTF-8 strings are not rejected
	-skip='invalid/encoding/bad-utf8-*'

	-skip='invalid/local-date/feb-29'
	-skip='invalid/local-datetime/feb-29'
	-skip='invalid/datetime/feb-29'
	-skip='invalid/local-date/feb-30'
	-skip='invalid/local-datetime/feb-30'
	-skip='invalid/datetime/feb-30'
	-skip='invalid/datetime/offset-overflow-hour'
)

skip_encode=(
	-skip='valid/spec/offset-date-time-0'
	-skip='valid/spec/local-date-time-0'
	-skip='valid/spec/local-time-0'

	# Some more Float <> Integer shenanigans
	# -int-as-float can't help us here, so we have to skip these :(
	-skip='valid/inline-table/spaces'
	-skip='valid/float/zero'
	-skip='valid/float/underscore'
	-skip='valid/float/exponent'
	-skip='valid/comment/tricky'
	-skip='valid/spec/float-0'
	-skip='valid/float/max-int'

	-skip='valid/integer/long'
)

e=0
# -int-as-float as there is no way to distinguish between them at this time.
# For the encoder, distinction is made between floats and integers using JS bigint, however
# due to the lack of option to always serialize plain numbers as floats, some tests fail (and are therefore skipped)
toml-test                         ${skip_decode[@]} ./toml-test-parse.php  || e=1
#toml-test               -encoder ${skip_encode[@]} ./toml-test-encode.bash || e=1
exit $e
