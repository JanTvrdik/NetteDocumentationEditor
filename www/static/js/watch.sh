#!/bin/sh
DIR=`dirname $0`
tsc "$DIR/init" --target ES5 --allowbool --out "$DIR/main.js" --watch