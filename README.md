Unicode to binary convertor
===========================

This repository provides an implementation converting common text in UTF-8 encoding to its binary representation, and vice versa.

> Unicode is a variable-length character encoding and is compatible with ASCII. The original specification allowed for sequences of up to six bytes but it was reduced by RFC to four later. The bits of a Unicode character are distributed into the lower bit positions inside the UTF-8 bytes, with the lowest bit going into the last bit of the last byte.

## Examples

	Text:        Hello world!
	Unicode:     U+0048 U+0065 U+006C U+006C U+006F U+0020 U+0077 U+006F U+0072 U+006C U+0064 U+0021
	Hexadecimal: 0x48 0x65 0x6C 0x6C 0x6F 0x20 0x77 0x6F 0x72 0x6C 0x64 0x21
	Binary:      01001000 01100101 01101100 01101100 01101111 00100000 01110111 01101111 01110010 01101100 01100100 00100001

	Text:        Žluťoučký kůň
	Unicode:     U+017D U+006C U+0075 U+0165 U+006F U+0075 U+010D U+006B U+00FD U+0020 U+006B U+016F U+0148
	Hexadecimal: 0xC5 0xBD 0x6C 0x75 0xC5 0xA5 0x6F 0x75 0xC4 0x8D 0x6B 0xC3 0xBD 0x20 0x6B 0xC5 0xAF 0xC5 0x88
	Binary:      11000101 10111101 01101100 01110101 11000101 10100101 01101111 01110101 11000100 10001101 01101011 11000011 10111101 00100000 01101011 11000101 10101111 11000101 10001000

## Notice

The code was originally written quite a long time ago.
