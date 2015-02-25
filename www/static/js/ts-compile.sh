#!/bin/sh
DIR=`dirname $0`
tsc "$DIR/init" --target ES6 --out "$DIR/main.js"
