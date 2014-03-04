#!/bin/sh
DIR=`dirname $0`
tsc "$DIR/init" --target ES5 --out "$DIR/main.js"
