#!/usr/bin/env bash

skip_decode=(
	# Invalid UTF-8 strings are not rejected
	-skip='invalid/encoding/bad-utf8-*'

	# Certain invalid UTF-8 codepoints are not rejected
	-skip='invalid/encoding/bad-codepoint'
	-skip='invalid/string/bad-uni-esc-6'

	# JS* doesn't reject invalid dates, but interprets extra days such as "Feb 30 2023" as "Feb 28 2023 +2d" gracefully.
	#
	# *This is true for V8, SpiderMonkey, and JavaScriptCore. Note that this behavior is implementation specific and
	# certain flavors of engines may behave differently.
	#
	# While smol-toml could implement additional checks, this has not been done for performance reasons
	-skip='invalid/local-date/feb-29'
	-skip='invalid/local-datetime/feb-29'
	-skip='invalid/datetime/feb-29'
	-skip='invalid/local-date/feb-30'
	-skip='invalid/local-datetime/feb-30'
	-skip='invalid/datetime/feb-30'
	-skip='invalid/datetime/offset-overflow-hour'

	# smol-toml does not support the entire 64-bit integer range
	# This is not required by the specification, and smol-toml throws an appropriate error
	# https://github.com/toml-lang/toml-test/issues/154
	-skip='valid/integer/long'
)

skip_encode=(
	# smol-toml does not support sub-millisecond time precision
	# This is not required by the specification, and smol-toml performs appropriate *truncation*, not rounding
	# https://github.com/toml-lang/toml-test/issues/155
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

	# smol-toml does not support the entire 64-bit integer range
	# This is not required by the specification, and smol-toml throws an appropriate error
	# https://github.com/toml-lang/toml-test/issues/154
	-skip='valid/integer/long'
)

e=0
# -int-as-float as there is no way to distinguish between them at this time.
# For the encoder, distinction is made between floats and integers using JS bigint, however
# due to the lack of option to always serialize plain numbers as floats, some tests fail (and are therefore skipped)
toml-test -int-as-float          ${skip_decode[@]} ./toml-test-parse.php  || e=1
#toml-test               -encoder ${skip_encode[@]} ./toml-test-encode.bash || e=1
exit $e
